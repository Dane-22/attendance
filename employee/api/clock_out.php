<?php
// api/clock_out.php
session_start();
require_once __DIR__ . '/../../conn/db_connection.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

function attendanceHasIsTimeRunningColumn($db) {
    static $cached = null;
    if ($cached !== null) return $cached;
    $sql = "SELECT COUNT(*) as cnt
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'attendance'
              AND COLUMN_NAME = 'is_time_running'";
    $result = mysqli_query($db, $sql);
    if (!$result) {
        $cached = false;
        return $cached;
    }
    $row = mysqli_fetch_assoc($result);
    $cached = intval($row['cnt'] ?? 0) === 1;
    return $cached;
}

$shiftId = $_POST['shift_id'] ?? null;
$employeeId = $_POST['employee_id'] ?? $_SESSION['employee_id'] ?? null;
$employeeCode = $_POST['employee_code'] ?? $_SESSION['employee_code'] ?? null;

error_log("Clock Out Attempt - Shift ID: $shiftId, Employee ID: $employeeId, Code: $employeeCode");

if (!$employeeId) {
    echo json_encode(['success' => false, 'message' => 'Missing employee data']);
    exit();
}

if (!$shiftId) {
    // Fallback: close the latest open shift for this employee
    $findSql = "SELECT id FROM attendance WHERE employee_id = ? AND time_out IS NULL ORDER BY id DESC LIMIT 1";
    $findStmt = mysqli_prepare($db, $findSql);
    mysqli_stmt_bind_param($findStmt, "i", $employeeId);
    mysqli_stmt_execute($findStmt);
    $findResult = mysqli_stmt_get_result($findStmt);
    if ($findResult && ($row = mysqli_fetch_assoc($findResult))) {
        $shiftId = $row['id'];
    }
    mysqli_stmt_close($findStmt);
}

if (!$shiftId) {
    echo json_encode(['success' => false, 'message' => 'No open shift found']);
    exit();
}

// Clock out
$sql = attendanceHasIsTimeRunningColumn($db)
    ? "UPDATE attendance SET time_out = NOW(), is_time_running = 0 WHERE id = ? AND employee_id = ? AND time_out IS NULL"
    : "UPDATE attendance SET time_out = NOW() WHERE id = ? AND employee_id = ? AND time_out IS NULL";
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