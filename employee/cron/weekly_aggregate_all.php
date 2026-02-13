<?php
/**
 * Weekly Payroll Aggregation - ALL Employees
 * Runs every Saturday midnight to aggregate daily payroll data for ALL branches
 */

date_default_timezone_set('Asia/Manila');

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("CLI only\n");
}

require_once __DIR__ . '/../../conn/db_connection.php';

$log_file = __DIR__ . '/weekly_aggregate_all.log';
$log_message = function($msg) use ($log_file) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $msg\n", FILE_APPEND);
    echo "$msg\n";
};

$log_message("=== Weekly Payroll Aggregation (ALL Employees) Starting ===");

// Calculate previous week's Monday to Saturday
$today = new DateTime();
$today->modify('last saturday'); // Get last Saturday (today when script runs)
$year = (int)$today->format('Y');
$month = (int)$today->format('n');
$week_number = (int)$today->format('W');

// Week runs Monday to Saturday (6 days)
$week_end = clone $today; // Saturday
$week_start = clone $week_end;
$week_start->modify('-5 days'); // Monday

$start_date = $week_start->format('Y-m-d');
$end_date = $week_end->format('Y-m-d');

$log_message("Processing week: $year-$month (Week $week_number)");
$log_message("Date range: $start_date to $end_date");

// Check if daily_payroll_reports table has data for this period
$check_query = "SELECT COUNT(*) as count, MIN(report_date) as min_date, MAX(report_date) as max_date 
                FROM daily_payroll_reports 
                WHERE report_date BETWEEN ? AND ?";
$check_stmt = mysqli_prepare($db, $check_query);
mysqli_stmt_bind_param($check_stmt, 'ss', $start_date, $end_date);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$check_row = mysqli_fetch_assoc($check_result);
mysqli_stmt_close($check_stmt);

$log_message("Daily records found: {$check_row['count']} (from {$check_row['min_date']} to {$check_row['max_date']})");

if ($check_row['count'] == 0) {
    $log_message("WARNING: No daily payroll data found for this week. Skipping aggregation.");
    exit(0);
}

// Aggregate daily records for ALL employees (no branch filter)
$aggregate_query = "SELECT 
    dpr.employee_id,
    e.fname,
    e.lname,
    dpr.branch_id,
    b.branch_name,
    SUM(dpr.days_worked) as total_days,
    SUM(dpr.total_hours) as total_hours,
    AVG(dpr.daily_rate) as daily_rate,
    SUM(dpr.basic_pay) as basic_pay,
    SUM(dpr.ot_hours) as total_ot_hours,
    AVG(dpr.ot_rate) as ot_rate,
    SUM(dpr.ot_amount) as total_ot_amount,
    SUM(dpr.performance_allowance) as total_allowance,
    SUM(dpr.gross_pay) as total_gross_pay,
    SUM(dpr.gross_plus_allowance) as total_gross_plus_allowance,
    SUM(dpr.ca_deduction) as total_ca_deduction,
    SUM(dpr.sss_deduction) as total_sss,
    SUM(dpr.philhealth_deduction) as total_philhealth,
    SUM(dpr.pagibig_deduction) as total_pagibig,
    SUM(dpr.sss_loan) as total_sss_loan,
    SUM(dpr.total_deductions) as total_deductions,
    SUM(dpr.take_home_pay) as total_take_home
FROM daily_payroll_reports dpr
JOIN employees e ON dpr.employee_id = e.id
LEFT JOIN branches b ON dpr.branch_id = b.id
WHERE dpr.report_date BETWEEN ? AND ?
GROUP BY dpr.employee_id, dpr.branch_id";

$stmt = mysqli_prepare($db, $aggregate_query);
mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$total_records = 0;
$default_user_id = 1;
$view_type = 'weekly';

while ($row = mysqli_fetch_assoc($result)) {
    $emp_id = $row['employee_id'];
    $branch_id = $row['branch_id'];
    $employee_name = $row['fname'] . ' ' . $row['lname'];
    
    // Check if weekly record exists to preserve payment_status
    $check_query = "SELECT payment_status FROM weekly_payroll_reports 
                   WHERE employee_id = ? AND report_year = ? AND report_month = ? 
                   AND week_number = ? AND view_type = ? AND branch_id = ?";
    $check_stmt = mysqli_prepare($db, $check_query);
    mysqli_stmt_bind_param($check_stmt, 'iiiisi', $emp_id, $year, $month, $week_number, $view_type, $branch_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $existing = mysqli_fetch_assoc($check_result);
    $payment_status = $existing ? $existing['payment_status'] : 'Not Paid';
    mysqli_stmt_close($check_stmt);
    
    // Insert/Update weekly record
    $query = "INSERT INTO weekly_payroll_reports (
        employee_id, report_year, report_month, week_number, view_type, branch_id,
        days_worked, total_hours, daily_rate, basic_pay,
        ot_hours, ot_rate, ot_amount,
        performance_allowance, gross_pay, gross_plus_allowance,
        ca_deduction, sss_deduction, philhealth_deduction, pagibig_deduction, sss_loan, total_deductions,
        take_home_pay, status, payment_status, created_by
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?
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
    
    $insert_stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($insert_stmt, 'iiiisiddddddddddddddddsdsi', 
        $emp_id, $year, $month, $week_number, $view_type, $branch_id,
        $row['total_days'], $row['total_hours'], $row['daily_rate'], $row['basic_pay'],
        $row['total_ot_hours'], $row['ot_rate'], $row['total_ot_amount'],
        $row['total_allowance'], $row['total_gross_pay'], $row['total_gross_plus_allowance'],
        $row['total_ca_deduction'], $row['total_sss'], $row['total_philhealth'], $row['total_pagibig'], $row['total_sss_loan'], $row['total_deductions'],
        $row['total_take_home'], $payment_status, $default_user_id
    );
    
    if (mysqli_stmt_execute($insert_stmt)) {
        $total_records++;
        $log_message("Aggregated: Employee #$emp_id ($employee_name) - Branch: " . ($row['branch_name'] ?? 'N/A') . " - Days: " . $row['total_days'] . " - Net: " . $row['total_take_home']);
    } else {
        $log_message("ERROR: " . mysqli_error($db));
    }
    mysqli_stmt_close($insert_stmt);
}

mysqli_stmt_close($stmt);

$log_message("Total weekly records aggregated: $total_records");
$log_message("=== Weekly Aggregation (ALL Employees) Complete ===\n");

exit(0);
