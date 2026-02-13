<?php
/**
 * Cleanup Script: Aggregate daily_payroll_reports and rebuild weekly_payroll_reports
 * Run this once to clean up duplicates and make weekly table a summary of daily records
 * 
 * Usage: php cleanup_weekly_payroll.php
 * WARNING: This will DELETE existing weekly_payroll_reports data and rebuild it from daily records!
 */

date_default_timezone_set('Asia/Manila');

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("This script can only be run from command line.\n");
}

require_once __DIR__ . '/../../conn/db_connection.php';

$log_file = __DIR__ . '/cleanup_weekly_payroll.log';
$log_message = function($msg) use ($log_file) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $msg\n", FILE_APPEND);
    echo "$msg\n";
};

$log_message("=== Starting Weekly Payroll Cleanup ===");

// Option 1: Clear all weekly records and rebuild from daily
// Option 2: Only rebuild for weeks that have daily data (safer)

$mode = 'rebuild_from_daily'; // 'clear_all' or 'rebuild_from_daily'

if ($mode === 'clear_all') {
    $log_message("Mode: Clearing ALL weekly records and rebuilding from daily data");
    
    // Backup before delete (optional)
    $backup_table = "weekly_payroll_reports_backup_" . date('Ymd_His');
    mysqli_query($db, "CREATE TABLE IF NOT EXISTS $backup_table LIKE weekly_payroll_reports");
    mysqli_query($db, "INSERT INTO $backup_table SELECT * FROM weekly_payroll_reports");
    $log_message("Backup created: $backup_table");
    
    // Clear weekly table
    mysqli_query($db, "TRUNCATE TABLE weekly_payroll_reports");
    $log_message("Cleared weekly_payroll_reports table");
} else {
    $log_message("Mode: Rebuilding from existing daily_payroll_reports data only");
}

// Get all unique year/month/week combinations from daily records
$periods_query = "SELECT DISTINCT report_year, report_month, week_number 
                  FROM daily_payroll_reports 
                  ORDER BY report_year DESC, report_month DESC, week_number DESC";
$periods_result = mysqli_query($db, $periods_query);

$total_periods = mysqli_num_rows($periods_result);
$log_message("Found $total_periods periods with daily data");

$total_inserted = 0;
$default_user_id = 1;
$view_type = 'weekly';

while ($period = mysqli_fetch_assoc($periods_result)) {
    $year = $period['report_year'];
    $month = $period['report_month'];
    $week = $period['week_number'];
    
    // Calculate date range for this week
    $days_in_month = date('t', strtotime("$year-$month-01"));
    $start_day = (($week - 1) * 7) + 1;
    $end_day = min($week * 7, $days_in_month);
    $start_date = sprintf('%04d-%02d-%02d', $year, $month, $start_day);
    $end_date = sprintf('%04d-%02d-%02d', $year, $month, $end_day);
    
    $log_message("Processing Year=$year, Month=$month, Week=$week ($start_date to $end_date)");
    
    // Delete existing weekly records for this period (to avoid duplicates)
    $delete_query = "DELETE FROM weekly_payroll_reports 
                    WHERE report_year = ? AND report_month = ? AND week_number = ? AND view_type = ?";
    $delete_stmt = mysqli_prepare($db, $delete_query);
    mysqli_stmt_bind_param($delete_stmt, 'iiis', $year, $month, $week, $view_type);
    mysqli_stmt_execute($delete_stmt);
    $deleted = mysqli_stmt_affected_rows($delete_stmt);
    mysqli_stmt_close($delete_stmt);
    $log_message("  Deleted $deleted existing weekly records for this period");
    
    // Aggregate daily records for this period
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
    WHERE dpr.report_year = ? AND dpr.report_month = ? AND dpr.week_number = ?
    GROUP BY dpr.employee_id, dpr.branch_id";
    
    $agg_stmt = mysqli_prepare($db, $aggregate_query);
    mysqli_stmt_bind_param($agg_stmt, 'iii', $year, $month, $week);
    mysqli_stmt_execute($agg_stmt);
    $agg_result = mysqli_stmt_get_result($agg_stmt);
    
    $period_inserted = 0;
    
    while ($row = mysqli_fetch_assoc($agg_result)) {
        $emp_id = $row['employee_id'];
        $branch_id = $row['branch_id'];
        
        // Insert aggregated weekly record
        $query = "INSERT INTO weekly_payroll_reports (
            employee_id, report_year, report_month, week_number, view_type, branch_id,
            days_worked, total_hours, daily_rate, basic_pay,
            ot_hours, ot_rate, ot_amount,
            performance_allowance, gross_pay, gross_plus_allowance,
            ca_deduction, sss_deduction, philhealth_deduction, pagibig_deduction, sss_loan, total_deductions,
            take_home_pay, status, payment_status, created_by
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'Not Paid', ?
        )";
        
        $insert_stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($insert_stmt, 'iiiisidddddddddddddddddi', 
            $emp_id, $year, $month, $week, $view_type, $branch_id,
            $row['total_days'], $row['total_hours'], $row['daily_rate'], $row['basic_pay'],
            $row['total_ot_hours'], $row['ot_rate'], $row['total_ot_amount'],
            $row['total_allowance'], $row['total_gross_pay'], $row['total_gross_plus_allowance'],
            $row['total_ca_deduction'], $row['total_sss'], $row['total_philhealth'], $row['total_pagibig'], $row['total_sss_loan'], $row['total_deductions'],
            $row['total_take_home'], $default_user_id
        );
        
        if (mysqli_stmt_execute($insert_stmt)) {
            $period_inserted++;
        } else {
            $log_message("  ERROR inserting emp_id=$emp_id: " . mysqli_error($db));
        }
        mysqli_stmt_close($insert_stmt);
    }
    
    mysqli_stmt_close($agg_stmt);
    $total_inserted += $period_inserted;
    $log_message("  Inserted $period_inserted weekly records for this period");
}

$log_message("=== Cleanup Complete ===");
$log_message("Total weekly records inserted: $total_inserted");
$log_message("weekly_payroll_reports is now a summary of daily_payroll_reports");
$log_message("Run the weekly aggregation scripts going forward to keep it updated");

exit(0);
