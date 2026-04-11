<?php
require_once __DIR__ . '/conn/db_connection.php';

$result = mysqli_query($db, "SHOW COLUMNS FROM weekly_payroll_reports LIKE 'view_type'");
$row = mysqli_fetch_assoc($result);
echo "view_type column: " . $row['Type'] . "\n";

// Also check what value is being sent
error_log("[check] view_type value test: range");

// Test insert with range
$test = mysqli_query($db, "INSERT INTO weekly_payroll_reports 
    (employee_id, report_year, report_month, week_number, view_type, branch_id, 
     days_worked, total_hours, daily_rate, gross_pay, sss_loan, total_deductions, take_home_pay, payment_status)
    VALUES (9999, 2026, 4, 1, 'range', 1, 0, 0, 500, 0, 100, 100, 0, 'Not Paid')
    ON DUPLICATE KEY UPDATE view_type='range'");

if ($test) {
    echo "Test insert with 'range' SUCCESS\n";
    mysqli_query($db, "DELETE FROM weekly_payroll_reports WHERE employee_id=9999");
} else {
    echo "Test insert FAILED: " . mysqli_error($db) . "\n";
}

mysqli_close($db);
