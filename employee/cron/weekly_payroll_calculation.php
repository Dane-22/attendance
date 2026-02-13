<?php
/**
 * Weekly Payroll Calculation Script
 * Run this script every Friday midnight to calculate payroll for the current week
 * 
 * Windows Task Scheduler Setup:
 * 1. Open Task Scheduler (taskschd.msc)
 * 2. Create Basic Task
 * 3. Name: "Weekly Payroll Calculation"
 * 4. Trigger: Weekly
 *    - Start: (any date)
 *    - Recur every: 1 weeks
 *    - On: Friday
 *    - At: 12:00:00 AM
 * 5. Action: Start a program
 * 6. Program: C:\wamp64\bin\php\php8.x.x\php.exe
 * 7. Arguments: c:\wamp64\www\main\employee\cron\weekly_payroll_calculation.php
 */

// Set timezone
date_default_timezone_set('Asia/Manila');

// Prevent browser access (CLI only)
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("This script can only be run from command line.\n");
}

// Database connection
require_once __DIR__ . '/../../conn/db_connection.php';

// Log file
$log_file = __DIR__ . '/weekly_payroll_calculation.log';
$log_message = function($msg) use ($log_file) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $msg\n", FILE_APPEND);
    echo "$msg\n";
};

$log_message("=== Starting Weekly Payroll Calculation ===");

// Get current date info (runs on Friday, calculate for current week)
$today = date('Y-m-d');
$year = date('Y');
$month = date('n');
$current_day = date('j');

// Calculate week number based on Friday being the end of the week
// Week starts on Monday (or Saturday/Sunday depending on your payroll cycle)
// For Friday calculation, we calculate Monday-Friday of current week
$week_number = ceil($current_day / 7);
if ($week_number > 4) $week_number = 4;

$log_message("Processing: Year=$year, Month=$month, Week=$week_number");
$log_message("Today is Friday: $today - Calculating payroll for current week");

// Calculate date range for current week (Monday to Friday)
$start_of_month = strtotime("$year-$month-01");
$days_in_month = date('t', $start_of_month);

// Calculate week boundaries
$start_day = (($week_number - 1) * 7) + 1;
$end_day = min($week_number * 7, $days_in_month);

// Adjust: If Friday is early in the month, ensure we capture the full week
if ($current_day < 7 && $week_number === 1) {
    $start_day = 1;
    $end_day = min(7, $days_in_month);
}

$start_date = sprintf('%04d-%02d-%02d', $year, $month, $start_day);
$end_date = sprintf('%04d-%02d-%02d', $year, $month, $end_day);

$log_message("Week date range: $start_date to $end_date");

// Load all active employees
$emp_query = "SELECT e.id, e.daily_rate, e.position, e.branch AS emp_branch, b.branch_name, e.fname, e.lname 
              FROM employees e 
              LEFT JOIN branches b ON e.branch = b.id 
              WHERE e.status = 'Active'
              ORDER BY e.id";
$emp_result = mysqli_query($db, $emp_query);

if (!$emp_result) {
    $log_message("ERROR: Failed to load employees: " . mysqli_error($db));
    exit(1);
}

$employees = [];
while ($row = mysqli_fetch_assoc($emp_result)) {
    $employees[$row['id']] = [
        'employee' => $row,
        'daily_rate' => floatval($row['daily_rate']),
        'days_worked' => 0,
        'total_hours' => 0,
        'total_ot_hrs' => 0,
        'gross_pay' => 0,
        'sss_deduction' => ($row['position'] !== 'Security Guard') ? 800 : 0,
        'philhealth_deduction' => ($row['position'] !== 'Security Guard') ? 300 : 0,
        'pagibig_deduction' => ($row['position'] !== 'Security Guard') ? 200 : 0,
        'total_deductions' => ($row['position'] !== 'Security Guard') ? 1300 : 0,
        'net_pay' => 0,
        '_daily' => [],
        '_branches' => []
    ];
}

$log_message("Loaded " . count($employees) . " active employees");

// Load attendance for all employees in date range
$attendance_query = "SELECT emp_id, date, status, branch_name, time_in, time_out, total_ot_hrs 
                     FROM attendance 
                     WHERE date BETWEEN '$start_date' AND '$end_date' 
                     AND status != 'Absent'
                     ORDER BY emp_id, date";
$attendance_result = mysqli_query($db, $attendance_query);

if (!$attendance_result) {
    $log_message("ERROR: Failed to load attendance: " . mysqli_error($db));
    exit(1);
}

$attendance_count = mysqli_num_rows($attendance_result);
$log_message("Loaded $attendance_count attendance records");

// Process attendance
while ($row = mysqli_fetch_assoc($attendance_result)) {
    $emp_id = $row['emp_id'];
    if (!isset($employees[$emp_id])) continue;
    
    $attendance_date = $row['date'];
    $branch_name = $row['branch_name'] ?? $employees[$emp_id]['employee']['branch_name'] ?? 'Main Branch';
    $status = $row['status'];
    $time_in = $row['time_in'] ?? null;
    $time_out = $row['time_out'] ?? null;
    
    if ($status === 'Absent') continue;
    
    // Initialize daily tracking
    if (!isset($employees[$emp_id]['_daily'][$attendance_date])) {
        $employees[$emp_id]['_daily'][$attendance_date] = [];
    }
    
    if (!isset($employees[$emp_id]['_daily'][$attendance_date][$branch_name])) {
        $employees[$emp_id]['_daily'][$attendance_date][$branch_name] = [
            'status' => $status,
            'hours' => 0,
            'ot_hours' => 0
        ];
    }
    
    // Calculate worked hours
    $worked_hours = 0;
    if ($time_in && $time_out) {
        $start_ts = strtotime($time_in);
        $end_ts = strtotime($time_out);
        if ($start_ts !== false && $end_ts !== false && $end_ts > $start_ts) {
            $worked_hours = ($end_ts - $start_ts) / 3600;
        }
    }
    
    $employees[$emp_id]['_daily'][$attendance_date][$branch_name]['status'] = $status;
    $employees[$emp_id]['_daily'][$attendance_date][$branch_name]['hours'] += $worked_hours;
    $employees[$emp_id]['_daily'][$attendance_date][$branch_name]['ot_hours'] += floatval($row['total_ot_hrs'] ?? 0);
}

// Calculate payroll totals
$total_processed = 0;
$total_saved = 0;
$employees_with_attendance = 0;

foreach ($employees as $emp_id => &$payroll) {
    if (empty($payroll['_daily'])) continue;
    
    $employees_with_attendance++;
    
    // Process daily attendance
    foreach ($payroll['_daily'] as $attendance_date => $branches) {
        if (count($branches) === 2) {
            // Split day for 2 branches (transfer scenario)
            foreach ($branches as $bName => $bData) {
                if (!isset($payroll['_branches'][$bName])) {
                    $payroll['_branches'][$bName] = ['days' => 0, 'hours' => 0, 'ot_hours' => 0];
                }
                $payroll['_branches'][$bName]['days'] += 0.5;
                $payroll['_branches'][$bName]['hours'] += floatval($bData['hours'] ?? 0);
                $payroll['_branches'][$bName]['ot_hours'] += floatval($bData['ot_hours'] ?? 0);
            }
            $payroll['days_worked'] += 1.0;
            foreach ($branches as $bData) {
                $payroll['total_hours'] += floatval($bData['hours'] ?? 0);
                $payroll['total_ot_hrs'] += floatval($bData['ot_hours'] ?? 0);
            }
        } else {
            // Full day at one or more branches
            $payroll['days_worked'] += 1.0;
            foreach ($branches as $bName => $bData) {
                if (!isset($payroll['_branches'][$bName])) {
                    $payroll['_branches'][$bName] = ['days' => 0, 'hours' => 0, 'ot_hours' => 0];
                }
                $payroll['_branches'][$bName]['days'] += 1.0;
                $payroll['_branches'][$bName]['hours'] += floatval($bData['hours'] ?? 0);
                $payroll['_branches'][$bName]['ot_hours'] += floatval($bData['ot_hours'] ?? 0);
                $payroll['total_hours'] += floatval($bData['hours'] ?? 0);
                $payroll['total_ot_hrs'] += floatval($bData['ot_hours'] ?? 0);
            }
        }
    }
    
    // Calculate financials
    $daily_rate = $payroll['daily_rate'];
    $days_worked = $payroll['days_worked'];
    $gross_pay = $daily_rate * $days_worked;
    $ot_rate = $daily_rate / 8;
    $ot_amount = $payroll['total_ot_hrs'] * $ot_rate;
    $gross_plus_allowance = $gross_pay + $ot_amount;
    $total_deductions = $payroll['total_deductions'];
    $net_pay = $gross_plus_allowance - $total_deductions;
    $net_pay = max(0, $net_pay);
    
    $payroll['gross_pay'] = $gross_pay;
    $payroll['ot_amount'] = $ot_amount;
    $payroll['gross_plus_allowance'] = $gross_plus_allowance;
    $payroll['net_pay'] = $net_pay;
    
    $total_processed++;
}
unset($payroll);

$log_message("Processed $total_processed employees with attendance");

// Save to weekly_payroll_reports table
$default_user_id = 1; // System/Admin user ID for cron jobs
$view_type = 'weekly';

foreach ($employees as $emp_id => $payroll) {
    if (empty($payroll['_branches'])) continue;
    
    $daily_rate = $payroll['daily_rate'];
    $employee_name = $payroll['employee']['fname'] . ' ' . $payroll['employee']['lname'];
    
    foreach ($payroll['_branches'] as $branch_name => $branch_data) {
        // Get branch_id
        $branch_id = null;
        $branch_lookup = mysqli_query($db, "SELECT id FROM branches WHERE branch_name = '" . mysqli_real_escape_string($db, $branch_name) . "' LIMIT 1");
        if ($branch_lookup && $row = mysqli_fetch_assoc($branch_lookup)) {
            $branch_id = $row['id'];
        }
        
        $days_worked = $branch_data['days'];
        $total_hours = floatval($branch_data['hours']);
        $ot_hours = floatval($branch_data['ot_hours']);
        $ot_rate = $daily_rate / 8;
        $ot_amount = $ot_hours * $ot_rate;
        $gross_pay = $daily_rate * $days_worked;
        $allowance = 0;
        $ca_deduction = 0;
        $sss_loan = 0;
        $gross_plus_allowance = $gross_pay + $allowance + $ot_amount;
        
        // Deductions - apply only to primary branch
        $max_days = 0;
        $primary_branch = null;
        foreach ($payroll['_branches'] as $bn => $bd) {
            if ($bd['days'] > $max_days) {
                $max_days = $bd['days'];
                $primary_branch = $bn;
            }
        }
        
        $sss_deduction = 0;
        $philhealth_deduction = 0;
        $pagibig_deduction = 0;
        $total_deductions = 0;
        
        if ($branch_name === $primary_branch && $days_worked > 0) {
            $sss_deduction = $payroll['sss_deduction'];
            $philhealth_deduction = $payroll['philhealth_deduction'];
            $pagibig_deduction = $payroll['pagibig_deduction'];
            $total_deductions = $payroll['total_deductions'];
        }
        
        $take_home_pay = $gross_plus_allowance - $total_deductions;
        $take_home_pay = max(0, $take_home_pay);
        
        // Insert/Update query with payment_status preservation
        $query = "INSERT INTO weekly_payroll_reports (
            employee_id, report_year, report_month, week_number, view_type, branch_id,
            days_worked, total_hours, daily_rate, basic_pay,
            ot_hours, ot_rate, ot_amount,
            performance_allowance, gross_pay, gross_plus_allowance,
            ca_deduction, sss_deduction, philhealth_deduction, pagibig_deduction, sss_loan, total_deductions,
            take_home_pay, status, payment_status, created_by
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'Not Paid', ?
        ) ON DUPLICATE KEY UPDATE
            days_worked = VALUES(days_worked),
            total_hours = VALUES(total_hours),
            daily_rate = VALUES(daily_rate),
            basic_pay = VALUES(basic_pay),
            ot_hours = VALUES(ot_hours),
            ot_rate = VALUES(ot_rate),
            ot_amount = VALUES(ot_amount),
            performance_allowance = VALUES(performance_allowance),
            gross_pay = VALUES(gross_pay),
            gross_plus_allowance = VALUES(gross_plus_allowance),
            ca_deduction = VALUES(ca_deduction),
            sss_deduction = VALUES(sss_deduction),
            philhealth_deduction = VALUES(philhealth_deduction),
            pagibig_deduction = VALUES(pagibig_deduction),
            sss_loan = VALUES(sss_loan),
            total_deductions = VALUES(total_deductions),
            take_home_pay = VALUES(take_home_pay),
            updated_at = CURRENT_TIMESTAMP";
        
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, 'iiiisidddddddddddddddddi', 
            $emp_id, $year, $month, $week_number, $view_type, $branch_id,
            $days_worked, $total_hours, $daily_rate, $gross_pay,
            $ot_hours, $ot_rate, $ot_amount,
            $allowance, $gross_pay, $gross_plus_allowance,
            $ca_deduction, $sss_deduction, $philhealth_deduction, $pagibig_deduction, $sss_loan, $total_deductions,
            $take_home_pay, $default_user_id
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $total_saved++;
            $log_message("Saved: Employee #$emp_id ($employee_name) - $branch_name - Days: $days_worked - Net: $take_home_pay");
        } else {
            $log_message("ERROR saving emp_id=$emp_id: " . mysqli_error($db));
        }
        mysqli_stmt_close($stmt);
    }
}

$log_message("Total records saved: $total_saved");
$log_message("=== Weekly Payroll Calculation Complete ===\n");

exit(0);
