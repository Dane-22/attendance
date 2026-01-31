<?php
require_once __DIR__ . '/../conn/db_connection.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$employeeId = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
$branchName = isset($_POST['branch_name']) ? trim($_POST['branch_name']) : '';

if ($employeeId <= 0 || $branchName === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

$today = date('Y-m-d');

$selectSql = "SELECT id, branch_name, status FROM attendance WHERE employee_id = ? AND attendance_date = ? LIMIT 1";
$selectStmt = mysqli_prepare($db, $selectSql);
if (!$selectStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error (prepare)']);
    exit();
}

mysqli_stmt_bind_param($selectStmt, 'is', $employeeId, $today);
mysqli_stmt_execute($selectStmt);
$result = mysqli_stmt_get_result($selectStmt);

if (!$result || mysqli_num_rows($result) === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'No attendance record found for today']);
    exit();
}

$row = mysqli_fetch_assoc($result);
$attendanceId = intval($row['id']);
$oldBranch = $row['branch_name'];
$status = $row['status'];

if ($status !== 'Present') {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Only Present employees can be transferred']);
    exit();
}

if ($oldBranch === $branchName) {
    echo json_encode([
        'success' => true,
        'message' => 'Already on selected branch',
        'attendance_id' => $attendanceId,
        'old_branch' => $oldBranch,
        'new_branch' => $branchName
    ]);
    exit();
}

$updateSql = "UPDATE attendance SET branch_name = ?, updated_at = NOW() WHERE id = ?";
$updateStmt = mysqli_prepare($db, $updateSql);
if (!$updateStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error (prepare update)']);
    exit();
}

mysqli_stmt_bind_param($updateStmt, 'si', $branchName, $attendanceId);

if (!mysqli_stmt_execute($updateStmt)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update branch']);
    exit();
}

echo json_encode([
    'success' => true,
    'message' => 'Branch updated successfully',
    'attendance_id' => $attendanceId,
    'old_branch' => $oldBranch,
    'new_branch' => $branchName
]);
