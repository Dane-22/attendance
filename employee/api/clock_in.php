<?php
// api/clock_in.php
session_start();
require_once __DIR__ . '/../../conn/db_connection.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$employeeId = $_POST['employee_id'] ?? $_SESSION['employee_id'] ?? null;
$employeeCode = $_POST['employee_code'] ?? $_SESSION['employee_code'] ?? null;
$branchName = $_POST['branch_name'] ?? $_SESSION['daily_branch'] ?? null;

error_log("Clock In Attempt - Employee ID: $employeeId, Code: $employeeCode");

if (!$employeeId || !$employeeCode) {
    echo json_encode(['success' => false, 'message' => 'Missing employee data']);
    exit();
}

// Check if already clocked in
$checkSql = "SELECT id FROM attendance WHERE employee_id = ? AND time_out IS NULL";
$stmt = mysqli_prepare($db, $checkSql);
mysqli_stmt_bind_param($stmt, "i", $employeeId);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    echo json_encode(['success' => false, 'message' => 'Already clocked in']);
    exit();
}
mysqli_stmt_close($stmt);

// Clock in
if ($branchName !== null && $branchName !== '') {
    $sql = "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in) VALUES (?, ?, CURDATE(), NOW())";
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, "is", $employeeId, $branchName);
} else {
    $sql = "INSERT INTO attendance (employee_id, time_in) VALUES (?, NOW())";
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, "i", $employeeId);
}

if (mysqli_stmt_execute($stmt)) {
    $timeIn = date('H:i:s');
    $shiftId = mysqli_insert_id($db);
    echo json_encode([
        'success' => true, 
        'message' => 'Clocked in successfully', 
        'time_in' => $timeIn,
        'shift_id' => $shiftId
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($db)]);
}
mysqli_stmt_close($stmt);
?>