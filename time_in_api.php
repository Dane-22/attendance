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

// Check if already clocked in for today
$sql = "SELECT id, time_in, time_out, is_time_running FROM attendance WHERE employee_id = ? AND attendance_date = ? ORDER BY id DESC LIMIT 1";
$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, 'is', $employeeId, $date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if ($row && (int)($row['is_time_running'] ?? 0) === 1) {
    echo json_encode(['success' => false, 'message' => 'Already timed in (time is running)', 'time_in' => $row['time_in']]);
    exit();
}

if ($row && $row['time_in'] && empty($row['time_out'])) {
    echo json_encode(['success' => false, 'message' => 'Already timed in today', 'time_in' => $row['time_in']]);
    exit();
}

$insertSql = "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, status, created_at, is_time_running) VALUES (?, ?, ?, NOW(), 'Present', NOW(), 1)";
$insertStmt = mysqli_prepare($db, $insertSql);
mysqli_stmt_bind_param($insertStmt, 'iss', $employeeId, $branchName, $date);
if (mysqli_stmt_execute($insertStmt)) {
    echo json_encode([
        'success' => true,
        'message' => 'Time in recorded',
        'attendance_id' => mysqli_insert_id($db),
        'time_in' => date('Y-m-d H:i:s'),
        'is_time_running' => true
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($db)]);
}
mysqli_stmt_close($insertStmt);
?>
