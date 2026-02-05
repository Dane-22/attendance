<?php
require_once __DIR__ . '/conn/db_connection.php';
header('Content-Type: application/json');

$employeeId = $_POST['employee_id'] ?? null;
$fromBranch = $_POST['from_branch'] ?? null;
$toBranch = $_POST['to_branch'] ?? null;
$date = date('Y-m-d');

if (!$employeeId || !$fromBranch || !$toBranch) {
    echo json_encode(['success' => false, 'message' => 'Missing employee_id, from_branch, or to_branch']);
    exit();
}

// 1. Find open attendance for today at fromBranch
$sql = "SELECT id FROM attendance WHERE employee_id = ? AND attendance_date = ? AND branch_name = ? AND time_in IS NOT NULL AND time_out IS NULL ORDER BY id DESC LIMIT 1";
$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, 'iss', $employeeId, $date, $fromBranch);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'No open attendance found for transfer']);
    exit();
}
$attendanceId = $row['id'];

// 2. Time out from fromBranch
$updateSql = "UPDATE attendance SET time_out = NOW(), updated_at = NOW(), is_time_running = 0 WHERE id = ?";
$updateStmt = mysqli_prepare($db, $updateSql);
mysqli_stmt_bind_param($updateStmt, 'i', $attendanceId);
if (!mysqli_stmt_execute($updateStmt)) {
    echo json_encode(['success' => false, 'message' => 'Failed to time out from current branch: ' . mysqli_error($db)]);
    mysqli_stmt_close($updateStmt);
    exit();
}
mysqli_stmt_close($updateStmt);

// 3. Time in to toBranch
$insertSql = "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, status, created_at, is_time_running) VALUES (?, ?, ?, NOW(), 'Present', NOW(), 1)";
$insertStmt = mysqli_prepare($db, $insertSql);
mysqli_stmt_bind_param($insertStmt, 'iss', $employeeId, $toBranch, $date);
if (mysqli_stmt_execute($insertStmt)) {
    echo json_encode([
        'success' => true,
        'message' => 'Transferred and timed in to new branch',
        'attendance_id' => mysqli_insert_id($db),
        'time_in' => date('Y-m-d H:i:s'),
        'from_branch' => $fromBranch,
        'to_branch' => $toBranch
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to time in to new branch: ' . mysqli_error($db)]);
}
mysqli_stmt_close($insertStmt);
?>
