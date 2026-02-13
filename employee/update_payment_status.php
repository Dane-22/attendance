<?php
// update_payment_status.php - Update employee payment status for weekly report
session_start();
require_once __DIR__ . '/../conn/db_connection.php';

// Check if user is logged in and is admin/super admin
if (empty($_SESSION['logged_in']) || !in_array($_SESSION['position'], ['Admin', 'Super Admin'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get POST data
$employee_id = $_POST['employee_id'] ?? null;
$payment_status = $_POST['payment_status'] ?? null;
$year = $_POST['year'] ?? null;
$month = $_POST['month'] ?? null;
$week = $_POST['week'] ?? null;
$view_type = $_POST['view_type'] ?? 'weekly';

// Validate inputs
if (!$employee_id || !$payment_status || !$year || !$month) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

// Validate payment status
if (!in_array($payment_status, ['Paid', 'Not Paid'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid payment status']);
    exit;
}

// Check if table exists
$table_check = mysqli_query($db, "SHOW TABLES LIKE 'weekly_payroll_reports'");
if (mysqli_num_rows($table_check) == 0) {
    echo json_encode(['success' => false, 'error' => 'Table does not exist']);
    exit;
}

// Check if payment_status column exists, if not add it
$column_check = mysqli_query($db, "SHOW COLUMNS FROM weekly_payroll_reports LIKE 'payment_status'");
if (mysqli_num_rows($column_check) == 0) {
    // Add the column
    mysqli_query($db, "ALTER TABLE weekly_payroll_reports ADD COLUMN payment_status enum('Paid','Not Paid') DEFAULT 'Not Paid' AFTER status");
}

// Update the payment status - insert if not exists, update if exists
$week_num = ($view_type === 'monthly') ? 0 : $week;

// First, check if record exists
$check_query = "SELECT id FROM weekly_payroll_reports 
                WHERE employee_id = ? 
                AND report_year = ? 
                AND report_month = ? 
                AND week_number = ? 
                AND view_type = ?";

$check_stmt = mysqli_prepare($db, $check_query);
mysqli_stmt_bind_param($check_stmt, 'iiiis', $employee_id, $year, $month, $week_num, $view_type);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$record_exists = mysqli_num_rows($check_result) > 0;
mysqli_stmt_close($check_stmt);

if ($record_exists) {
    // Update existing record
    $query = "UPDATE weekly_payroll_reports 
              SET payment_status = ? 
              WHERE employee_id = ? 
              AND report_year = ? 
              AND report_month = ? 
              AND week_number = ? 
              AND view_type = ?";
    
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, 'siiiis', $payment_status, $employee_id, $year, $month, $week_num, $view_type);
} else {
    // Insert new record with just the payment status and required fields
    $query = "INSERT INTO weekly_payroll_reports (
        employee_id, report_year, report_month, week_number, view_type,
        days_worked, total_hours, daily_rate, basic_pay,
        ot_hours, ot_rate, ot_amount,
        performance_allowance, gross_pay, gross_plus_allowance,
        ca_deduction, sss_deduction, philhealth_deduction, pagibig_deduction, sss_loan, total_deductions,
        take_home_pay, status, payment_status, created_by
    ) VALUES (
        ?, ?, ?, ?, ?, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'Pending', ?, ?
    )";
    
    $stmt = mysqli_prepare($db, $query);
    $pending_status = 'Pending';
    $created_by = $_SESSION['user_id'] ?? 0;
    mysqli_stmt_bind_param($stmt, 'iiiisssi', $employee_id, $year, $month, $week_num, $view_type, $payment_status, $pending_status, $created_by);
}

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($db)]);
}

mysqli_stmt_close($stmt);
exit;
