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

// Fetch current assigned branch from employees table
$empSql = "SELECT branch_name FROM employees WHERE id = ? LIMIT 1";
$empStmt = mysqli_prepare($db, $empSql);
if (!$empStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error (prepare employee)']);
    exit();
}

mysqli_stmt_bind_param($empStmt, 'i', $employeeId);
mysqli_stmt_execute($empStmt);
$empResult = mysqli_stmt_get_result($empStmt);
if (!$empResult || mysqli_num_rows($empResult) === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Employee not found']);
    exit();
}

$empRow = mysqli_fetch_assoc($empResult);
$oldBranch = $empRow['branch_name'] ?? '';

if ($oldBranch === $branchName) {
    echo json_encode([
        'success' => true,
        'message' => 'Already on selected branch',
        'old_branch' => $oldBranch,
        'new_branch' => $branchName
    ]);
    exit();
}

// Update employee assigned branch
$updateEmpSql = "UPDATE employees SET branch_name = ?, updated_at = NOW() WHERE id = ?";
$updateEmpStmt = mysqli_prepare($db, $updateEmpSql);
if (!$updateEmpStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error (prepare update employee)']);
    exit();
}
mysqli_stmt_bind_param($updateEmpStmt, 'si', $branchName, $employeeId);
if (!mysqli_stmt_execute($updateEmpStmt)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update employee branch']);
    exit();
}

// Insert transfer log (best effort)
$transferSql = "INSERT INTO employee_transfers (employee_id, from_branch, to_branch, transfer_date, status)
                VALUES (?, ?, ?, NOW(), 'completed')";
$transferStmt = mysqli_prepare($db, $transferSql);
if ($transferStmt) {
    mysqli_stmt_bind_param($transferStmt, 'iss', $employeeId, $oldBranch, $branchName);
    mysqli_stmt_execute($transferStmt);
    mysqli_stmt_close($transferStmt);
}

echo json_encode([
    'success' => true,
    'message' => 'Branch updated successfully',
    'old_branch' => $oldBranch,
    'new_branch' => $branchName
]);
