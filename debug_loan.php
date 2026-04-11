<?php
require_once __DIR__ . '/conn/db_connection.php';

// Get the actual view_type column definition
$result = mysqli_query($db, "SHOW COLUMNS FROM weekly_payroll_reports LIKE 'view_type'");
$row = mysqli_fetch_assoc($result);
echo "Column Type: " . $row['Type'] . "<br>";

// Simulate what update_loan.php does
$employeeId = 12;
$sssLoan = 100.00;
$year = 2026;
$month = 4;
$week = 2;
$viewType = 'range';

// Check if record exists
$check = mysqli_prepare($db, "SELECT id FROM weekly_payroll_reports 
    WHERE employee_id = ? AND report_year = ? AND report_month = ? AND week_number = ? AND view_type = ?");
mysqli_stmt_bind_param($check, 'iiiis', $employeeId, $year, $month, $week, $viewType);
mysqli_stmt_execute($check);
$existing = mysqli_stmt_get_result($check);
$row = mysqli_fetch_assoc($existing);
echo "Existing record: " . ($row ? 'YES (id=' . $row['id'] . ')' : 'NO') . "<br>";

// Get employee data
$empStmt = mysqli_prepare($db, "SELECT id, daily_rate, branch_id FROM employees WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($empStmt, 'i', $employeeId);
mysqli_stmt_execute($empStmt);
$empResult = mysqli_stmt_get_result($empStmt);
$empRow = mysqli_fetch_assoc($empResult);
echo "Employee found: " . ($empRow ? 'YES' : 'NO') . "<br>";

if ($empRow) {
    $dailyRate = (float)$empRow['daily_rate'];
    $branchId = (int)$empRow['branch_id'];

    // Try to prepare the insert
    $insertStmt = mysqli_prepare($db, "INSERT INTO weekly_payroll_reports
         (employee_id, report_year, report_month, week_number, view_type, branch_id,
          days_worked, total_hours, daily_rate, gross_pay, gross_plus_allowance,
          sss_deduction, philhealth_deduction, pagibig_deduction, ca_deduction,
          sss_loan, total_deductions, take_home_pay, payment_status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, 0, 0, ?, 0, 0, 0, 0, 0, 0, ?, ?, ?, 'Not Paid', NOW())");

    if (!$insertStmt) {
        echo "Prepare failed: " . mysqli_error($db) . "<br>";
    } else {
        echo "Prepare succeeded<br>";

        // Try bind_param
        $bindResult = mysqli_stmt_bind_param($insertStmt, 'iiiiisdddd',
            $employeeId, $year, $month, $week, $viewType, $branchId,
            $dailyRate, $sssLoan, $sssLoan, $sssLoan
        );
        echo "Bind result: " . ($bindResult ? 'SUCCESS' : 'FAILED') . "<br>";

        // Try execute
        $execResult = mysqli_stmt_execute($insertStmt);
        if ($execResult) {
            echo "Execute succeeded! New ID: " . mysqli_insert_id($db) . "<br>";
            // Clean up test data
            mysqli_query($db, "DELETE FROM weekly_payroll_reports WHERE id=" . mysqli_insert_id($db));
        } else {
            echo "Execute FAILED: " . mysqli_stmt_error($insertStmt) . "<br>";
        }
    }
}

mysqli_close($db);
echo "Done";
