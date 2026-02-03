<?php
// api/clock_in.php
session_start();
require_once __DIR__ . '/../../conn/db_connection.php';

header('Content-Type: application/json');

// Keep JSON clean
error_reporting(E_ALL);
ini_set('display_errors', 0);

function attendanceHasTimeColumns($db) {
    $sql = "SELECT COUNT(*) as cnt
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'attendance'
              AND COLUMN_NAME IN ('time_in','time_out')";
    $result = mysqli_query($db, $sql);
    if (!$result) return false;
    $row = mysqli_fetch_assoc($result);
    return intval($row['cnt'] ?? 0) === 2;
}

$employeeId = $_POST['employee_id'] ?? $_SESSION['employee_id'] ?? null;
$employeeCode = $_POST['employee_code'] ?? $_SESSION['employee_code'] ?? null;
$branchName = $_POST['branch_name'] ?? $_SESSION['daily_branch'] ?? null;

error_log("Clock In Attempt - Employee ID: $employeeId, Code: $employeeCode");

if (!$employeeId || !$employeeCode) {
    echo json_encode(['success' => false, 'message' => 'Missing employee data']);
    exit();
}

if (!attendanceHasTimeColumns($db)) {
    echo json_encode(['success' => false, 'message' => 'Time In/Out is not available: attendance table has no time_in/time_out columns']);
    exit();
}

if ($branchName === null || $branchName === '') {
    $bSql = "SELECT branch_name FROM employees WHERE id = ? LIMIT 1";
    $bStmt = mysqli_prepare($db, $bSql);
    if ($bStmt) {
        mysqli_stmt_bind_param($bStmt, 'i', $employeeId);
        mysqli_stmt_execute($bStmt);
        $bResult = mysqli_stmt_get_result($bStmt);
        if ($bResult && ($bRow = mysqli_fetch_assoc($bResult))) {
            $branchName = $bRow['branch_name'] ?? '';
        }
        mysqli_stmt_close($bStmt);
    }
    if ($branchName === null || $branchName === '') {
        $branchName = 'System';
    }
}

// Check if already clocked in
$checkSql = "SELECT id FROM attendance WHERE employee_id = ? AND attendance_date = CURDATE() AND time_in IS NOT NULL AND time_out IS NULL";
$stmt = mysqli_prepare($db, $checkSql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error (prepare check)']);
    exit();
}
mysqli_stmt_bind_param($stmt, "i", $employeeId);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    echo json_encode(['success' => false, 'message' => 'Already clocked in']);
    exit();
}
mysqli_stmt_close($stmt);

// If there's an existing attendance row for today with no time_in yet, update it instead of inserting
$existingSql = "SELECT id FROM attendance WHERE employee_id = ? AND attendance_date = CURDATE() AND time_in IS NULL ORDER BY id DESC LIMIT 1";
$existingStmt = mysqli_prepare($db, $existingSql);
if ($existingStmt) {
    mysqli_stmt_bind_param($existingStmt, "i", $employeeId);
    mysqli_stmt_execute($existingStmt);
    $existingResult = mysqli_stmt_get_result($existingStmt);
    if ($existingResult && ($existingRow = mysqli_fetch_assoc($existingResult))) {
        $existingId = intval($existingRow['id']);

        if ($branchName !== null && $branchName !== '') {
            $updateSql = "UPDATE attendance SET time_in = NOW(), branch_name = ? WHERE id = ?";
            $updateStmt = mysqli_prepare($db, $updateSql);
            if (!$updateStmt) {
                echo json_encode(['success' => false, 'message' => 'Database error (prepare update)']);
                exit();
            }
            mysqli_stmt_bind_param($updateStmt, "si", $branchName, $existingId);
        } else {
            $updateSql = "UPDATE attendance SET time_in = NOW() WHERE id = ?";
            $updateStmt = mysqli_prepare($db, $updateSql);
            if (!$updateStmt) {
                echo json_encode(['success' => false, 'message' => 'Database error (prepare update)']);
                exit();
            }
            mysqli_stmt_bind_param($updateStmt, "i", $existingId);
        }

        if (mysqli_stmt_execute($updateStmt)) {
            $timeIn = date('H:i:s');
            echo json_encode([
                'success' => true,
                'message' => 'Clocked in successfully',
                'time_in' => $timeIn,
                'shift_id' => $existingId
            ]);
            exit();
        }

        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($db)]);
        exit();
    }
    mysqli_stmt_close($existingStmt);
}

// Clock in
if ($branchName !== null && $branchName !== '') {
    $sql = "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in) VALUES (?, ?, CURDATE(), NOW())";
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error (prepare insert)']);
        exit();
    }
    mysqli_stmt_bind_param($stmt, "is", $employeeId, $branchName);
} else {
    $sql = "INSERT INTO attendance (employee_id, time_in) VALUES (?, NOW())";
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error (prepare insert)']);
        exit();
    }
    mysqli_stmt_bind_param($stmt, "i", $employeeId);
}

if (mysqli_stmt_execute($stmt)) {
    $timeIn = date('H:i:s');
    $shiftId = mysqli_insert_id($db);
    echo json_encode([
        'success' => true, 
        'message' => 'Clocked in successfully', 
        'time_in' => $timeIn,
        'shift_id' => $shiftId
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($db)]);
}
mysqli_stmt_close($stmt);
?>