<?php
// api/clock_out.php
session_start();
require_once '../conn/db_connection.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$shiftId = $_POST['shift_id'] ?? null;
$employeeId = $_POST['employee_id'] ?? $_SESSION['user_id'] ?? null;
$employeeCode = $_POST['employee_code'] ?? $_SESSION['employee_code'] ?? null;

error_log("Clock Out Attempt - Shift ID: $shiftId, Employee ID: $employeeId, Code: $employeeCode");

if (!$shiftId || !$employeeId) {
    echo json_encode(['success' => false, 'message' => 'Missing shift or employee data']);
    exit();
}

// Clock out
$sql = "UPDATE attendance SET time_out = NOW() WHERE id = ? AND employee_id = ? AND time_out IS NULL";
$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, "ii", $shiftId, $employeeId);

if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
    $timeOut = date('H:i:s');
    echo json_encode([
        'success' => true, 
        'message' => 'Clocked out successfully', 
        'time_out' => $timeOut
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Unable to clock out or already clocked out']);
}
mysqli_stmt_close($stmt);
?>