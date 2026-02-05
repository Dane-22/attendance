<?php
require_once __DIR__ . '/../conn/db_connection.php';
session_start();

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
ob_start();

header('Content-Type: application/json');

try {

if (!isset($db) || !($db instanceof mysqli)) {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection not available']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$employeeId = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
$branchName = isset($_POST['branch_name']) ? trim($_POST['branch_name']) : '';
$action = isset($_POST['action']) ? trim($_POST['action']) : 'transfer';

if ($employeeId <= 0 || $branchName === '') {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

// Fetch current assigned branch from employees table
// Get latest attendance branch today for this employee
$attSql = "SELECT id, branch_name FROM attendance WHERE employee_id = ? AND attendance_date = CURDATE() ORDER BY id DESC LIMIT 1";
$attStmt = mysqli_prepare($db, $attSql);
if (!$attStmt) {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error (prepare attendance)']);
    exit();
}

mysqli_stmt_bind_param($attStmt, 'i', $employeeId);
mysqli_stmt_execute($attStmt);
$attResult = mysqli_stmt_get_result($attStmt);
if (!$attResult || mysqli_num_rows($attResult) === 0) {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'No attendance record for today']);
    exit();
}

$attRow = mysqli_fetch_assoc($attResult);
$attendanceId = intval($attRow['id']);
$oldBranch = $attRow['branch_name'] ?? '';

if ($oldBranch === $branchName) {
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode([
        'success' => true,
        'message' => 'Already on selected branch',
        'old_branch' => $oldBranch,
        'new_branch' => $branchName
    ]);
    exit();
}

// Update latest attendance record branch for today
$updateAttSql = "UPDATE attendance SET branch_name = ?, updated_at = NOW() WHERE id = ?";
$updateAttStmt = mysqli_prepare($db, $updateAttSql);
if (!$updateAttStmt) {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error (prepare update attendance)']);
    exit();
}
mysqli_stmt_bind_param($updateAttStmt, 'si', $branchName, $attendanceId);
if (!mysqli_stmt_execute($updateAttStmt)) {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update attendance branch']);
    exit();
}

// Insert transfer log (best effort)
$didLogTransfer = false;
if ($action !== 'undo_transfer') {
    $transferSql = "INSERT INTO employee_transfers (employee_id, from_branch, to_branch, transfer_date, status)
                    VALUES (?, ?, ?, NOW(), 'completed')";
    $transferStmt = mysqli_prepare($db, $transferSql);
    if ($transferStmt) {
        mysqli_stmt_bind_param($transferStmt, 'iss', $employeeId, $oldBranch, $branchName);
        mysqli_stmt_execute($transferStmt);
        mysqli_stmt_close($transferStmt);
        $didLogTransfer = true;
    }
}

echo json_encode([
    'success' => true,
    'message' => ($action === 'undo_transfer') ? 'Transfer undone' : 'Branch updated successfully',
    'old_branch' => $oldBranch,
    'new_branch' => $branchName,
    'logged_transfer' => $didLogTransfer
]);

} catch (Throwable $e) {
    error_log('update_deployment.php error: ' . $e->getMessage());
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

exit();
