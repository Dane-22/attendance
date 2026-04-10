<?php
/**
 * Daily Reconciliation Check
 * Verifies that daily_payroll_reports matches attendance records
 * Run this after daily_payroll_calculation.php to catch any discrepancies
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
$log_file = __DIR__ . '/daily_reconciliation_check.log';
$log_message = function($msg) use ($log_file) {
    $timestamp = date('Y-m-d H:i:s');
    $line = "[$timestamp] $msg\n";
    file_put_contents($log_file, $line, FILE_APPEND);
    echo $line;
};

$log_message("=== Starting Daily Reconciliation Check ===");

// Check yesterday's date
$yesterday = date('Y-m-d', strtotime('-1 day'));
$log_message("Checking reconciliation for: $yesterday");

$issues_found = 0;
$issues_details = [];

// Check 1: Payroll records without matching attendance
$orphaned_sql = "SELECT 
    dpr.id,
    dpr.employee_id,
    e.first_name,
    e.last_name,
    dpr.report_date,
    dpr.days_worked,
    dpr.branch_id
FROM daily_payroll_reports dpr
JOIN employees e ON dpr.employee_id = e.id
LEFT JOIN attendance a ON dpr.employee_id = a.employee_id 
    AND dpr.report_date = a.attendance_date
    AND a.time_in IS NOT NULL 
    AND a.time_out IS NOT NULL
WHERE dpr.report_date = '$yesterday'
AND a.id IS NULL";

$orphaned_result = mysqli_query($db, $orphaned_sql);
$orphaned_count = mysqli_num_rows($orphaned_result);

if ($orphaned_count > 0) {
    $issues_found += $orphaned_count;
    $log_message("ALERT: Found $orphaned_count payroll records without valid attendance:");
    
    while ($row = mysqli_fetch_assoc($orphaned_result)) {
        $detail = sprintf("  - Emp %d (%s %s): %s, days=%s, branch=%s",
            $row['employee_id'],
            $row['first_name'],
            $row['last_name'],
            $row['report_date'],
            $row['days_worked'],
            $row['branch_id']
        );
        $log_message($detail);
        $issues_details[] = $detail;
    }
} else {
    $log_message("✓ No orphaned payroll records found");
}

// Check 2: Attendance records without matching payroll
$missing_payroll_sql = "SELECT 
    a.employee_id,
    e.first_name,
    e.last_name,
    a.attendance_date,
    a.time_in,
    a.time_out,
    a.branch_name
FROM attendance a
JOIN employees e ON a.employee_id = e.id
LEFT JOIN daily_payroll_reports dpr ON a.employee_id = dpr.employee_id 
    AND a.attendance_date = dpr.report_date
WHERE a.attendance_date = '$yesterday'
AND a.time_in IS NOT NULL 
AND a.time_out IS NOT NULL
AND dpr.id IS NULL
AND LOWER(e.position) = 'worker'";

$missing_result = mysqli_query($db, $missing_payroll_sql);
$missing_count = mysqli_num_rows($missing_result);

if ($missing_count > 0) {
    $issues_found += $missing_count;
    $log_message("ALERT: Found $missing_count attendance records without payroll:");
    
    while ($row = mysqli_fetch_assoc($missing_result)) {
        $detail = sprintf("  - Emp %d (%s %s): %s, branch=%s",
            $row['employee_id'],
            $row['first_name'],
            $row['last_name'],
            $row['attendance_date'],
            $row['branch_name']
        );
        $log_message($detail);
        $issues_details[] = $detail;
    }
} else {
    $log_message("✓ All attendance records have matching payroll");
}

// Check 3: Day count mismatches
$mismatch_sql = "SELECT 
    dpr.employee_id,
    e.first_name,
    e.last_name,
    dpr.report_date,
    dpr.days_worked as payroll_days,
    COUNT(a.id) as attendance_count,
    dpr.branch_id
FROM daily_payroll_reports dpr
JOIN employees e ON dpr.employee_id = e.id
LEFT JOIN attendance a ON dpr.employee_id = a.employee_id 
    AND dpr.report_date = a.attendance_date
    AND a.time_in IS NOT NULL 
    AND a.time_out IS NOT NULL
WHERE dpr.report_date = '$yesterday'
GROUP BY dpr.employee_id, dpr.report_date, dpr.branch_id
HAVING dpr.days_worked != COUNT(a.id)";

$mismatch_result = mysqli_query($db, $mismatch_sql);
$mismatch_count = mysqli_num_rows($mismatch_result);

if ($mismatch_count > 0) {
    $issues_found += $mismatch_count;
    $log_message("ALERT: Found $mismatch_count day count mismatches:");
    
    while ($row = mysqli_fetch_assoc($mismatch_result)) {
        $detail = sprintf("  - Emp %d (%s %s): payroll_days=%s, attendance_count=%s",
            $row['employee_id'],
            $row['first_name'],
            $row['last_name'],
            $row['payroll_days'],
            $row['attendance_count']
        );
        $log_message($detail);
        $issues_details[] = $detail;
    }
} else {
    $log_message("✓ All day counts match between payroll and attendance");
}

// Summary
$log_message("\n=== Reconciliation Summary ===");
$log_message("Date: $yesterday");
$log_message("Total issues found: $issues_found");

if ($issues_found > 0) {
    $log_message("STATUS: FAILED - Manual intervention required");
    
    // Here you could add email notification or other alerting
    // For now, we just log it
    
    exit(1); // Exit with error code to signal failure
} else {
    $log_message("STATUS: PASSED - All records reconciled successfully");
    exit(0); // Success
}
?>
