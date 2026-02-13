<?php
/**
 * Daily Payroll Calculation Script
 * Run this script every midnight to calculate payroll for the current day
 * 
 * Windows Task Scheduler Setup:
 * 1. Open Task Scheduler (taskschd.msc)
 * 2. Create Basic Task
 * 3. Name: "Daily Payroll Calculation"
 * 4. Trigger: Daily
 *    - Start: (any date) 12:00:00 AM
 *    - Recur every: 1 days
 * 5. Action: Start a program
 * 6. Program: C:\wamp64\bin\php\php8.x.x\php.exe
 * 7. Arguments: c:\wamp64\www\main\employee\cron\daily_payroll_calculation.php
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
$log_file = __DIR__ . '/daily_payroll_calculation.log';
$log_message = function($msg) use ($log_file) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $msg\n", FILE_APPEND);
    echo "$msg\n";
};

$log_message("=== Starting Daily Payroll Calculation ===");

// Get yesterday's date (since we run at midnight, we calculate for the day that just ended)
$yesterday = date('Y-m-d', strtotime('-1 day'));
$year = date('Y', strtotime('-1 day'));
$month = date('n', strtotime('-1 day'));
$day = date('j', strtotime('-1 day'));

// Calculate week number
$week_number = ceil($day / 7);
if ($week_number > 4) $week_number = 4;

$log_message("Processing date: $yesterday (Year=$year, Month=$month, Day=$day, Week=$week_number)");

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

// Load attendance for yesterday only
$attendance_query = "SELECT emp_id, date, status, branch_name, time_in, time_out, total_ot_hrs 
                     FROM attendance 
                     WHERE date = '$yesterday'
                     AND status != 'Absent'
                     ORDER BY emp_id";
$attendance_result = mysqli_query($db, $attendance_query);

if (!$attendance_result) {
    $log_message("ERROR: Failed to load attendance: " . mysqli_error($db));
    exit(1);
}

$attendance_count = mysqli_num_rows($attendance_result);
$log_message("Loaded $attendance_count attendance records for $yesterday");

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

// Calculate payroll
$total_processed = 0;
$total_saved = 0;
$employees_with_attendance = 0;

foreach ($employees as $emp_id => &$payroll) {
    if (empty($payroll['_daily'])) continue;
    
    $employees_with_attendance++;
    
    // Process daily attendance (single day)
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

$log_message("Processed $total_processed employees with attendance for $yesterday");

// Save to daily_payroll_reports table (new table for daily tracking)
$default_user_id = 1;

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
        
        // Deductions - apply based on branch_id and week timing
        // Branch 33: Monthly deduction (only on week 4)
        // Non-Branch 33: Weekly deduction on weeks 1-3 only (NOT week 4)
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
        
        // Determine if deductions should apply based on branch and week
        $apply_deductions = false;
        if ($branch_name === $primary_branch && $days_worked > 0) {
            if ($branch_id == 33) {
                // Branch 33: Apply deductions only on week 4 (monthly)
                $apply_deductions = ($week_number == 4);
            } else {
                // Non-Branch 33: Apply deductions on weeks 1-3 only
                $apply_deductions = ($week_number >= 1 && $week_number <= 3);
            }
        }
        
        if ($apply_deductions) {
            $sss_deduction = $payroll['sss_deduction'];
            $philhealth_deduction = $payroll['philhealth_deduction'];
            $pagibig_deduction = $payroll['pagibig_deduction'];
            $total_deductions = $payroll['total_deductions'];
        }
        
        $take_home_pay = $gross_plus_allowance - $total_deductions;
        $take_home_pay = max(0, $take_home_pay);
        
        // Check if record exists for this employee/date/branch
        $check_query = "SELECT id FROM daily_payroll_reports 
                       WHERE employee_id = ? AND report_date = ? AND branch_id = ?";
        $check_stmt = mysqli_prepare($db, $check_query);
        mysqli_stmt_bind_param($check_stmt, 'isi', $emp_id, $yesterday, $branch_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        $existing = mysqli_fetch_assoc($check_result);
        mysqli_stmt_close($check_stmt);
        
        if ($existing) {
            // Update existing record
            $query = "UPDATE daily_payroll_reports SET
                days_worked = ?,
                total_hours = ?,
                daily_rate = ?,
                basic_pay = ?,
                ot_hours = ?,
                ot_rate = ?,
                ot_amount = ?,
                performance_allowance = ?,
                gross_pay = ?,
                gross_plus_allowance = ?,
                ca_deduction = ?,
                sss_deduction = ?,
                philhealth_deduction = ?,
                pagibig_deduction = ?,
                sss_loan = ?,
                total_deductions = ?,
                take_home_pay = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
            
            $stmt = mysqli_prepare($db, $query);
            mysqli_stmt_bind_param($stmt, 'ddddddddddddddddddi', 
                $days_worked, $total_hours, $daily_rate, $gross_pay,
                $ot_hours, $ot_rate, $ot_amount,
                $allowance, $gross_pay, $gross_plus_allowance,
                $ca_deduction, $sss_deduction, $philhealth_deduction, $pagibig_deduction, $sss_loan, $total_deductions,
                $take_home_pay, $existing['id']
            );
        } else {
            // Insert new record
            $query = "INSERT INTO daily_payroll_reports (
                employee_id, report_date, report_year, report_month, report_day, week_number, branch_id,
                days_worked, total_hours, daily_rate, basic_pay,
                ot_hours, ot_rate, ot_amount,
                performance_allowance, gross_pay, gross_plus_allowance,
                ca_deduction, sss_deduction, philhealth_deduction, pagibig_deduction, sss_loan, total_deductions,
                take_home_pay, status, created_by
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?
            )";
            
            $stmt = mysqli_prepare($db, $query);
            mysqli_stmt_bind_param($stmt, 'isiiiiddddddddddddddddddi', 
                $emp_id, $yesterday, $year, $month, $day, $week_number, $branch_id,
                $days_worked, $total_hours, $daily_rate, $gross_pay,
                $ot_hours, $ot_rate, $ot_amount,
                $allowance, $gross_pay, $gross_plus_allowance,
                $ca_deduction, $sss_deduction, $philhealth_deduction, $pagibig_deduction, $sss_loan, $total_deductions,
                $take_home_pay, $default_user_id
            );
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $total_saved++;
            $log_message("Saved: Employee #$emp_id ($employee_name) - $branch_name - Day: $yesterday - Net: $take_home_pay");
        } else {
            $log_message("ERROR saving emp_id=$emp_id: " . mysqli_error($db));
        }
        mysqli_stmt_close($stmt);
    }
}

$log_message("Total daily records saved: $total_saved");
$log_message("=== Daily Payroll Calculation Complete ===\n");

exit(0);
