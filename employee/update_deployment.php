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

// Resolve branch_id from the provided branch_name
$branchSql = "SELECT id, branch_name FROM branches WHERE branch_name = ? LIMIT 1";
$branchStmt = mysqli_prepare($db, $branchSql);
if (!$branchStmt) {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error (prepare branch)']);
    exit();
}
mysqli_stmt_bind_param($branchStmt, 's', $branchName);
mysqli_stmt_execute($branchStmt);
$branchRes = mysqli_stmt_get_result($branchStmt);
$branchRow = $branchRes ? mysqli_fetch_assoc($branchRes) : null;
mysqli_stmt_close($branchStmt);

if (!$branchRow || empty($branchRow['id'])) {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid branch']);
    exit();
}

$newBranchId = intval($branchRow['id']);
$newBranchName = $branchRow['branch_name'] ?? $branchName;

// Fetch employee current assigned branch_id (and name)
$empSql = "SELECT e.branch_id, b.branch_name AS branch_name FROM employees e LEFT JOIN branches b ON b.id = e.branch_id WHERE e.id = ? LIMIT 1";
$empStmt = mysqli_prepare($db, $empSql);
if (!$empStmt) {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error (prepare employee)']);
    exit();
}
mysqli_stmt_bind_param($empStmt, 'i', $employeeId);
mysqli_stmt_execute($empStmt);
$empResult = mysqli_stmt_get_result($empStmt);
$empRow = $empResult ? mysqli_fetch_assoc($empResult) : null;
mysqli_stmt_close($empStmt);

$oldOriginalBranchId = isset($empRow['branch_id']) ? intval($empRow['branch_id']) : null;
$oldOriginalBranchName = $empRow['branch_name'] ?? '';

// If employee is already assigned to the selected branch, treat as success
if (!empty($oldOriginalBranchId) && $oldOriginalBranchId === $newBranchId) {
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode([
        'success' => true,
        'message' => 'Already on selected branch',
        'old_branch' => $oldOriginalBranchName,
        'new_branch' => $newBranchName,
        'old_original_branch_id' => $oldOriginalBranchId,
        'new_original_branch_id' => $newBranchId,
        'old_original_branch' => $oldOriginalBranchName,
        'new_original_branch' => $newBranchName,
        'logged_transfer' => false
    ]);
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
// Attendance record is optional; only update if it exists
$attendanceId = null;
$oldBranch = $oldOriginalBranchName;
if ($attResult && mysqli_num_rows($attResult) > 0) {
    $attRow = mysqli_fetch_assoc($attResult);
    $attendanceId = isset($attRow['id']) ? intval($attRow['id']) : null;
    $oldBranch = $attRow['branch_name'] ?? $oldOriginalBranchName;

    if ($oldBranch === $branchName) {
        if (ob_get_length()) {
            ob_clean();
        }
        echo json_encode([
            'success' => true,
            'message' => 'Already on selected branch',
            'old_branch' => $oldBranch,
            'new_branch' => $branchName,
            'old_original_branch_id' => $oldOriginalBranchId,
            'new_original_branch_id' => $newBranchId,
            'old_original_branch' => $oldOriginalBranchName,
            'new_original_branch' => $newBranchName,
            'logged_transfer' => false
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
}

// Update employee assigned branch_id
$updateEmpSql = "UPDATE employees SET branch_id = ? WHERE id = ?";
$updateEmpStmt = mysqli_prepare($db, $updateEmpSql);
if (!$updateEmpStmt) {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error (prepare update employee)']);
    exit();
}
mysqli_stmt_bind_param($updateEmpStmt, 'ii', $newBranchId, $employeeId);
if (!mysqli_stmt_execute($updateEmpStmt)) {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update employee branch']);
    exit();
}
mysqli_stmt_close($updateEmpStmt);

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
    'new_branch' => $newBranchName,
    'old_original_branch_id' => $oldOriginalBranchId,
    'new_original_branch_id' => $newBranchId,
    'old_original_branch' => $oldOriginalBranchName,
    'new_original_branch' => $newBranchName,
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
