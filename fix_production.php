<?php
require_once __DIR__ . '/conn/db_connection.php';

// First check current column
$result = mysqli_query($db, "SHOW COLUMNS FROM weekly_payroll_reports LIKE 'view_type'");
$row = mysqli_fetch_assoc($result);
echo "BEFORE: " . $row['Type'] . "\n";

// Fix the column
$sql = "ALTER TABLE weekly_payroll_reports 
        MODIFY COLUMN view_type ENUM('weekly', 'monthly', 'range') DEFAULT 'weekly'";

if (mysqli_query($db, $sql)) {
    echo "ALTER TABLE SUCCESS\n";
} else {
    echo "ALTER TABLE ERROR: " . mysqli_error($db) . "\n";
}

// Verify the change  
$result2 = mysqli_query($db, "SHOW COLUMNS FROM weekly_payroll_reports LIKE 'view_type'");
$row2 = mysqli_fetch_assoc($result2);
echo "AFTER: " . $row2['Type'] . "\n";

// Test inserting with 'range'
$test = mysqli_query($db, "INSERT INTO weekly_payroll_reports 
    (employee_id, report_year, report_month, week_number, view_type, branch_id, 
     days_worked, total_hours, daily_rate, gross_pay, sss_loan, total_deductions, take_home_pay, payment_status)
    VALUES (9999, 2026, 4, 1, 'range', 1, 0, 0, 500, 0, 100, 100, 0, 'Not Paid')
    ON DUPLICATE KEY UPDATE sss_loan=100");

if ($test) {
    echo "TEST INSERT SUCCESS\n";
    mysqli_query($db, "DELETE FROM weekly_payroll_reports WHERE employee_id=9999");
} else {
    echo "TEST INSERT FAILED: " . mysqli_error($db) . "\n";
}

mysqli_close($db);
echo "Done!";
