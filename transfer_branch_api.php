<?php
require_once __DIR__ . '/conn/db_connection.php';
header('Content-Type: application/json');

$employeeId = $_POST['employee_id'] ?? null;
$fromBranch = $_POST['from_branch'] ?? null;
$toBranch = $_POST['to_branch'] ?? null;
$toBranchId = $_POST['to_branch_id'] ?? null;
$date = date('Y-m-d');

function employeesHasColumn($db, $columnName) {
    $safe = mysqli_real_escape_string($db, $columnName);
    $sql = "SHOW COLUMNS FROM `employees` LIKE '{$safe}'";
    $result = mysqli_query($db, $sql);
    return $result && mysqli_num_rows($result) > 0;
}

function attendanceHasColumn($db, $columnName) {
    $safe = mysqli_real_escape_string($db, $columnName);
    $sql = "SHOW COLUMNS FROM `attendance` LIKE '{$safe}'";
    $result = mysqli_query($db, $sql);
    return $result && mysqli_num_rows($result) > 0;
}

if (!$employeeId || (!$toBranch && !$toBranchId)) {
    echo json_encode(['success' => false, 'message' => 'Missing employee_id or to_branch/to_branch_id']);
    exit();
}

// Resolve target branch id + name
$resolvedToBranchId = null;
$resolvedToBranchName = null;

if ($toBranchId) {
    $sql = "SELECT id, branch_name FROM branches WHERE id = ? AND is_active = 1 LIMIT 1";
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $toBranchId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Target branch not found or inactive']);
        exit();
    }
    $resolvedToBranchId = (int)$row['id'];
    $resolvedToBranchName = $row['branch_name'];
} else {
    $sql = "SELECT id, branch_name FROM branches WHERE branch_name = ? AND is_active = 1 LIMIT 1";
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 's', $toBranch);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Target branch not found or inactive']);
        exit();
    }
    $resolvedToBranchId = (int)$row['id'];
    $resolvedToBranchName = $row['branch_name'];
}

// 1) Find open attendance for today (optionally constrained by from_branch)
$attendanceId = null;
$actualFromBranch = null;

if ($fromBranch) {
    $sql = "SELECT id, branch_name FROM attendance WHERE employee_id = ? AND attendance_date = ? AND branch_name = ? AND time_in IS NOT NULL AND time_out IS NULL ORDER BY id DESC LIMIT 1";
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'iss', $employeeId, $date, $fromBranch);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);
    if ($row) {
        $attendanceId = (int)$row['id'];
        $actualFromBranch = $row['branch_name'];
    }
} else {
    $sql = "SELECT id, branch_name FROM attendance WHERE employee_id = ? AND attendance_date = ? AND time_in IS NOT NULL AND time_out IS NULL ORDER BY id DESC LIMIT 1";
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'is', $employeeId, $date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);
    if ($row) {
        $attendanceId = (int)$row['id'];
        $actualFromBranch = $row['branch_name'];
    }
}

// Always update employees.branch_id (transfer assignment)
$hasUpdatedAt = employeesHasColumn($db, 'updated_at');
$updateEmpSql = $hasUpdatedAt
    ? "UPDATE employees SET branch_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? LIMIT 1"
    : "UPDATE employees SET branch_id = ? WHERE id = ? LIMIT 1";
$updateEmpStmt = mysqli_prepare($db, $updateEmpSql);
if (!$updateEmpStmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare employee update: ' . mysqli_error($db)]);
    exit();
}
mysqli_stmt_bind_param($updateEmpStmt, 'ii', $resolvedToBranchId, $employeeId);
if (!mysqli_stmt_execute($updateEmpStmt)) {
    echo json_encode(['success' => false, 'message' => 'Failed to update employee branch: ' . mysqli_error($db)]);
    mysqli_stmt_close($updateEmpStmt);
    exit();
}
mysqli_stmt_close($updateEmpStmt);

// 2) If no open attendance, transfer is only employees table change
if (!$attendanceId) {
    echo json_encode([
        'success' => true,
        'message' => 'Employee branch updated (no open attendance to transfer)',
        'employee_id' => (int)$employeeId,
        'to_branch_id' => $resolvedToBranchId,
        'to_branch' => $resolvedToBranchName,
        'timed_out' => false,
        'timed_in' => false,
    ]);
    exit();
}

// 3) Time out from current branch
$updateSql = "UPDATE attendance SET time_out = NOW(), updated_at = NOW(), is_time_running = 0 WHERE id = ?";
$updateStmt = mysqli_prepare($db, $updateSql);
mysqli_stmt_bind_param($updateStmt, 'i', $attendanceId);
if (!mysqli_stmt_execute($updateStmt)) {
    echo json_encode(['success' => false, 'message' => 'Failed to time out from current branch: ' . mysqli_error($db)]);
    mysqli_stmt_close($updateStmt);
    exit();
}
mysqli_stmt_close($updateStmt);

// 4) Time in to target branch
$hasIsOvertimeRunning = attendanceHasColumn($db, 'is_overtime_running');
$hasTotalOtHrs = attendanceHasColumn($db, 'total_ot_hrs');
$shouldIncludeOtDefaults = $hasIsOvertimeRunning && $hasTotalOtHrs;

$insertSql = $shouldIncludeOtDefaults
    ? "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, status, created_at, is_overtime_running, is_time_running, total_ot_hrs) VALUES (?, ?, ?, NOW(), 'Present', NOW(), 0, 1, '')"
    : "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, status, created_at, is_time_running) VALUES (?, ?, ?, NOW(), 'Present', NOW(), 1)";
$insertStmt = mysqli_prepare($db, $insertSql);
mysqli_stmt_bind_param($insertStmt, 'iss', $employeeId, $resolvedToBranchName, $date);
if (mysqli_stmt_execute($insertStmt)) {
    echo json_encode([
        'success' => true,
        'message' => 'Transferred and timed in to new branch',
        'employee_id' => (int)$employeeId,
        'attendance_id' => mysqli_insert_id($db),
        'time_in' => date('Y-m-d H:i:s'),
        'from_branch' => $actualFromBranch,
        'to_branch' => $resolvedToBranchName,
        'to_branch_id' => $resolvedToBranchId,
        'timed_out' => true,
        'timed_in' => true,
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to time in to new branch: ' . mysqli_error($db)]);
}
mysqli_stmt_close($insertStmt);
?>
