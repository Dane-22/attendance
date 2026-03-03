<?php
/**
 * Daily Payroll Report Generator
 * 
 * This script populates daily_payroll_reports from attendance data
 * Run this to backfill or update daily payroll records from existing attendance
 * 
 * Usage: 
 * 1. Browser: http://your-server/employee/cron/generate_daily_payroll.php?start_date=2026-02-23&end_date=2026-02-28
 * 2. CLI: php generate_daily_payroll.php
 */

// Set timezone
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../../conn/db_connection.php';

// Get date range from parameters or use defaults
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Government deduction constants (monthly)
$MONTHLY_PHILHEALTH = 250.00;
$MONTHLY_SSS = 450.00;
$MONTHLY_PAGIBIG = 200.00;

header('Content-Type: text/plain');
echo "=== Daily Payroll Report Generator ===\n";
echo "Period: $start_date to $end_date\n";
echo "Started: " . date('Y-m-d H:i:s') . "\n\n";

// Fetch attendance records with time_out (completed shifts)
$attendance_query = "SELECT 
                        a.employee_id,
                        a.attendance_date,
                        a.time_in,
                        a.time_out,
                        a.total_ot_hrs,
                        a.branch_name,
                        e.daily_rate,
                        e.first_name,
                        e.last_name,
                        b.id as branch_id
                     FROM attendance a
                     JOIN employees e ON a.employee_id = e.id
                     LEFT JOIN branches b ON a.branch_name = b.branch_name
                     WHERE a.attendance_date BETWEEN ? AND ?
                     AND a.time_out IS NOT NULL
                     AND e.status = 'Active'
                     AND LOWER(e.position) = 'worker'
                     ORDER BY a.attendance_date, a.employee_id";

$stmt = mysqli_prepare($db, $attendance_query);
mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$processed = 0;
$errors = 0;
$skipped = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $employee_id = $row['employee_id'];
    $attendance_date = $row['attendance_date'];
    $branch_id = $row['branch_id'];
    
    // Check if record already exists in daily_payroll_reports
    $check_sql = "SELECT id FROM daily_payroll_reports 
                  WHERE employee_id = ? AND report_date = ? AND branch_id = ?";
    $check_stmt = mysqli_prepare($db, $check_sql);
    mysqli_stmt_bind_param($check_stmt, 'isi', $employee_id, $attendance_date, $branch_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        echo "SKIP: Record already exists for Employee $employee_id on $attendance_date\n";
        $skipped++;
        continue;
    }
    
    // Calculate worked hours
    $time_in = $row['time_in'];
    $time_out = $row['time_out'];
    $worked_hours = 0;
    
    if ($time_in && $time_out) {
        $start_ts = strtotime($time_in);
        $end_ts = strtotime($time_out);
        if ($start_ts !== false && $end_ts !== false && $end_ts > $start_ts) {
            $worked_hours = ($end_ts - $start_ts) / 3600;
        }
    }
    
    $daily_rate = floatval($row['daily_rate'] ?? 0);
    $ot_hours = floatval($row['total_ot_hrs'] ?? 0);
    
    // Calculate payroll values
    $days_worked = 1.0; // One attendance record = one day
    $basic_pay = $daily_rate * $days_worked;
    $ot_rate = $daily_rate / 8;
    $ot_amount = $ot_hours * $ot_rate;
    $gross_pay = $basic_pay + $ot_amount;
    $performance_allowance = 0.00;
    $gross_plus_allowance = $gross_pay + $performance_allowance;
    
    // Calculate deductions (pro-rated daily)
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, date('m', strtotime($attendance_date)), date('Y', strtotime($attendance_date)));
    $sss_deduction = $MONTHLY_SSS / $days_in_month;
    $philhealth_deduction = $MONTHLY_PHILHEALTH / $days_in_month;
    $pagibig_deduction = $MONTHLY_PAGIBIG / $days_in_month;
    $ca_deduction = 0.00;
    $sss_loan = 0.00;
    $total_deductions = $sss_deduction + $philhealth_deduction + $pagibig_deduction + $ca_deduction + $sss_loan;
    
    $take_home_pay = $gross_plus_allowance - $total_deductions;
    
    // Extract date components
    $report_year = date('Y', strtotime($attendance_date));
    $report_month = date('n', strtotime($attendance_date));
    $report_day = date('j', strtotime($attendance_date));
    $week_number = ceil($report_day / 7);
    if ($week_number > 5) $week_number = 5;
    
    // Insert into daily_payroll_reports
    $insert_sql = "INSERT INTO daily_payroll_reports 
        (employee_id, report_date, report_year, report_month, report_day, week_number, 
         branch_id, days_worked, total_hours, daily_rate, basic_pay, ot_hours, ot_rate, ot_amount,
         performance_allowance, gross_pay, gross_plus_allowance, ca_deduction, 
         sss_deduction, philhealth_deduction, pagibig_deduction, sss_loan, 
         total_deductions, take_home_pay, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
        days_worked = VALUES(days_worked),
        total_hours = VALUES(total_hours),
        basic_pay = VALUES(basic_pay),
        ot_hours = VALUES(ot_hours),
        ot_amount = VALUES(ot_amount),
        gross_pay = VALUES(gross_pay),
        gross_plus_allowance = VALUES(gross_plus_allowance),
        sss_deduction = VALUES(sss_deduction),
        philhealth_deduction = VALUES(philhealth_deduction),
        pagibig_deduction = VALUES(pagibig_deduction),
        total_deductions = VALUES(total_deductions),
        take_home_pay = VALUES(take_home_pay),
        updated_at = NOW()";
    
    $insert_stmt = mysqli_prepare($db, $insert_sql);
    mysqli_stmt_bind_param($insert_stmt, 'isiiiiiddddddddddddddddds', 
        $employee_id, $attendance_date, $report_year, $report_month, $report_day, $week_number,
        $branch_id, $days_worked, $worked_hours, $daily_rate, $basic_pay, $ot_hours, $ot_rate, $ot_amount,
        $performance_allowance, $gross_pay, $gross_plus_allowance, $ca_deduction,
        $sss_deduction, $philhealth_deduction, $pagibig_deduction, $sss_loan,
        $total_deductions, $take_home_pay, $status
    );
    
    $status = 'Pending';
    
    if (mysqli_stmt_execute($insert_stmt)) {
        echo "INSERTED: Employee $employee_id ($row[first_name] $row[last_name]) - $attendance_date - Gross: ₱" . number_format($gross_pay, 2) . "\n";
        $processed++;
    } else {
        echo "ERROR: Employee $employee_id on $attendance_date - " . mysqli_error($db) . "\n";
        $errors++;
    }
}

echo "\n=== Summary ===\n";
echo "Processed: $processed records\n";
echo "Skipped: $skipped records (already exist)\n";
echo "Errors: $errors records\n";
echo "Completed: " . date('Y-m-d H:i:s') . "\n";

// Also update the log file
$log_file = __DIR__ . '/daily_payroll_generator.log';
$log_message = "[" . date('Y-m-d H:i:s') . "] Generated $processed records, skipped $skipped, errors $errors for period $start_date to $end_date\n";
file_put_contents($log_file, $log_message, FILE_APPEND);

mysqli_close($db);
?>
