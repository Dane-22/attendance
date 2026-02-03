<?php
require_once __DIR__ . '/conn/db_connection.php';
header('Content-Type: application/json');

$employeeId = $_POST['employee_id'] ?? null;
$branchName = $_POST['branch_name'] ?? null;

if (!$employeeId || !$branchName) {
    echo json_encode(['success' => false, 'message' => 'Missing employee_id or branch_name']);
    exit();
}

$date = date('Y-m-d');

// Find today's latest running attendance row
$sql = "SELECT id, time_in, time_out, is_time_running, branch_name FROM attendance WHERE employee_id = ? AND attendance_date = ? AND is_time_running = 1 ORDER BY id DESC LIMIT 1";
$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, 'is', $employeeId, $date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'No open attendance record for time out']);
    exit();
}

if (!empty($row['branch_name']) && $row['branch_name'] !== $branchName) {
    echo json_encode(['success' => false, 'message' => 'Cannot time out from a different branch']);
    exit();
}

$attendanceId = $row['id'];
$updateSql = "UPDATE attendance SET time_out = NOW(), is_time_running = 0, updated_at = NOW() WHERE id = ?";
$updateStmt = mysqli_prepare($db, $updateSql);
mysqli_stmt_bind_param($updateStmt, 'i', $attendanceId);
if (mysqli_stmt_execute($updateStmt)) {
    echo json_encode([
        'success' => true,
        'message' => 'Time out recorded',
        'attendance_id' => $attendanceId,
        'time_out' => date('Y-m-d H:i:s'),
        'is_time_running' => false
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($db)]);
}
mysqli_stmt_close($updateStmt);
?>
