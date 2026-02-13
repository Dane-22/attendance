<?php
/**
 * Cleanup Script: Remove duplicates from weekly_payroll_reports
 * Aggregates duplicate records per employee/week/branch and keeps only one summary
 */

date_default_timezone_set('Asia/Manila');

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("CLI only\n");
}

require_once __DIR__ . '/../../conn/db_connection.php';

$log_file = __DIR__ . '/cleanup_duplicates.log';
$log_message = function($msg) use ($log_file) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $msg\n", FILE_APPEND);
    echo "$msg\n";
};

$log_message("=== Starting Weekly Payroll Deduplication ===");

// First, check current state
$check_result = mysqli_query($db, "SELECT COUNT(*) as total, COUNT(DISTINCT CONCAT(employee_id, '-', report_year, '-', report_month, '-', week_number, '-', branch_id)) as unique_records FROM weekly_payroll_reports");
$check_row = mysqli_fetch_assoc($check_result);
$total_before = $check_row['total'];
$unique_before = $check_row['unique_records'];
$duplicates = $total_before - $unique_before;

$log_message("Before cleanup: $total_before total records, $unique_before unique combinations");
$log_message("Duplicates found: $duplicates");

if ($duplicates == 0) {
    $log_message("No duplicates found. Nothing to clean up.");
    exit(0);
}

// Create backup table
$backup_table = "weekly_payroll_reports_backup_" . date('Ymd_His');
mysqli_query($db, "CREATE TABLE IF NOT EXISTS `$backup_table` LIKE weekly_payroll_reports");
mysqli_query($db, "INSERT INTO `$backup_table` SELECT * FROM weekly_payroll_reports");
$log_message("Backup created: $backup_table");

// Get all duplicate groups
$duplicates_query = "SELECT employee_id, report_year, report_month, week_number, branch_id, COUNT(*) as count, MAX(id) as keep_id
                     FROM weekly_payroll_reports 
                     GROUP BY employee_id, report_year, report_month, week_number, branch_id 
                     HAVING COUNT(*) > 1";
$dup_result = mysqli_query($db, $duplicates_query);

$groups_cleaned = 0;
$records_deleted = 0;

while ($dup = mysqli_fetch_assoc($dup_result)) {
    $emp_id = $dup['employee_id'];
    $year = $dup['report_year'];
    $month = $dup['report_month'];
    $week = $dup['week_number'];
    $branch = $dup['branch_id'];
    $keep_id = $dup['keep_id'];
    $count = $dup['count'];
    
    // Aggregate values from all duplicates
    $agg_query = "SELECT 
        COALESCE(SUM(days_worked), 0) as total_days,
        COALESCE(SUM(total_hours), 0) as total_hours,
        COALESCE(AVG(daily_rate), 0) as avg_daily_rate,
        COALESCE(SUM(basic_pay), 0) as total_basic_pay,
        COALESCE(SUM(ot_hours), 0) as total_ot_hours,
        COALESCE(AVG(ot_rate), 0) as avg_ot_rate,
        COALESCE(SUM(ot_amount), 0) as total_ot_amount,
        COALESCE(SUM(performance_allowance), 0) as total_allowance,
        COALESCE(SUM(gross_pay), 0) as total_gross,
        COALESCE(SUM(gross_plus_allowance), 0) as total_gross_plus,
        COALESCE(SUM(ca_deduction), 0) as total_ca,
        COALESCE(SUM(sss_deduction), 0) as total_sss,
        COALESCE(SUM(philhealth_deduction), 0) as total_philhealth,
        COALESCE(SUM(pagibig_deduction), 0) as total_pagibig,
        COALESCE(SUM(sss_loan), 0) as total_sss_loan,
        COALESCE(SUM(total_deductions), 0) as total_deductions,
        COALESCE(SUM(take_home_pay), 0) as total_take_home,
        COALESCE(MAX(payment_status), 'Not Paid') as payment_status
    FROM weekly_payroll_reports 
    WHERE employee_id = ? AND report_year = ? AND report_month = ? AND week_number = ? AND branch_id = ?";
    
    $agg_stmt = mysqli_prepare($db, $agg_query);
    mysqli_stmt_bind_param($agg_stmt, 'iiiii', $emp_id, $year, $month, $week, $branch);
    mysqli_stmt_execute($agg_stmt);
    $agg_result = mysqli_stmt_get_result($agg_stmt);
    $agg = mysqli_fetch_assoc($agg_result);
    mysqli_stmt_close($agg_stmt);
    
    // Convert to float values and build update query
    $total_days = floatval($agg['total_days']);
    $total_hours = floatval($agg['total_hours']);
    $avg_daily_rate = floatval($agg['avg_daily_rate']);
    $total_basic_pay = floatval($agg['total_basic_pay']);
    $total_ot_hours = floatval($agg['total_ot_hours']);
    $avg_ot_rate = floatval($agg['avg_ot_rate']);
    $total_ot_amount = floatval($agg['total_ot_amount']);
    $total_allowance = floatval($agg['total_allowance']);
    $total_gross = floatval($agg['total_gross']);
    $total_gross_plus = floatval($agg['total_gross_plus']);
    $total_ca = floatval($agg['total_ca']);
    $total_sss = floatval($agg['total_sss']);
    $total_philhealth = floatval($agg['total_philhealth']);
    $total_pagibig = floatval($agg['total_pagibig']);
    $total_sss_loan = floatval($agg['total_sss_loan']);
    $total_deductions_val = floatval($agg['total_deductions']);
    $total_take_home = floatval($agg['total_take_home']);
    $payment_status = mysqli_real_escape_string($db, $agg['payment_status'] ?? 'Not Paid');
    
    // Update using direct query to avoid bind_param issues
    $update_query = "UPDATE weekly_payroll_reports SET
        days_worked = $total_days,
        total_hours = $total_hours,
        daily_rate = $avg_daily_rate,
        basic_pay = $total_basic_pay,
        ot_hours = $total_ot_hours,
        ot_rate = $avg_ot_rate,
        ot_amount = $total_ot_amount,
        performance_allowance = $total_allowance,
        gross_pay = $total_gross,
        gross_plus_allowance = $total_gross_plus,
        ca_deduction = $total_ca,
        sss_deduction = $total_sss,
        philhealth_deduction = $total_philhealth,
        pagibig_deduction = $total_pagibig,
        sss_loan = $total_sss_loan,
        total_deductions = $total_deductions_val,
        take_home_pay = $total_take_home,
        payment_status = '$payment_status',
        updated_at = CURRENT_TIMESTAMP
    WHERE id = $keep_id";
    
    mysqli_query($db, $update_query);
    
    // Delete the other duplicates using direct query
    $branch_val = $branch === null ? 'NULL' : $branch;
    $delete_query = "DELETE FROM weekly_payroll_reports 
                     WHERE employee_id = $emp_id AND report_year = $year AND report_month = $month AND week_number = $week AND branch_id <=> $branch_val
                     AND id != $keep_id";
    mysqli_query($db, $delete_query);
    $deleted = mysqli_affected_rows($db);
    
    $records_deleted += $deleted;
    $groups_cleaned++;
    
    $log_message("Cleaned: Emp $emp_id Week $week - Aggregated $count records, deleted $deleted duplicates");
}

// Verify results
$check_result2 = mysqli_query($db, "SELECT COUNT(*) as total FROM weekly_payroll_reports");
$check_row2 = mysqli_fetch_assoc($check_result2);
$total_after = $check_row2['total'];

$log_message("=== Cleanup Complete ===");
$log_message("Groups cleaned: $groups_cleaned");
$log_message("Records deleted: $records_deleted");
$log_message("Total records before: $total_before");
$log_message("Total records after: $total_after");
$log_message("Backup table: $backup_table");

exit(0);
