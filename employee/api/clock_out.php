<?php
// api/clock_out.php
session_start();
require_once __DIR__ . '/../../conn/db_connection.php';
require_once __DIR__ . '/../../functions.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

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

function attendanceHasStatusColumn($db) {
    static $cached = null;
    if ($cached !== null) return $cached;
    $sql = "SELECT COUNT(*) as cnt
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'attendance'
              AND COLUMN_NAME = 'status'";
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
$action = $_POST['action'] ?? '';

error_log("Clock Out Attempt - Shift ID: $shiftId, Employee ID: $employeeId, Code: $employeeCode");

if (!$employeeId) {
    echo json_encode(['success' => false, 'message' => 'Missing employee data']);
    exit();
}

if ($action === 'undo_clock_out') {
    if (!$shiftId) {
        echo json_encode(['success' => false, 'message' => 'Missing shift_id']);
        exit();
    }

    $hasRunningCol = attendanceHasIsTimeRunningColumn($db);
    $hasStatusCol = attendanceHasStatusColumn($db);
    if ($hasRunningCol) {
        $sql = $hasStatusCol
            ? "UPDATE attendance SET time_out = NULL, status = 'Present', is_time_running = 1 WHERE id = ? AND employee_id = ? AND time_out IS NOT NULL"
            : "UPDATE attendance SET time_out = NULL, is_time_running = 1 WHERE id = ? AND employee_id = ? AND time_out IS NOT NULL";
    } else {
        $sql = $hasStatusCol
            ? "UPDATE attendance SET time_out = NULL, status = 'Present' WHERE id = ? AND employee_id = ? AND time_out IS NOT NULL"
            : "UPDATE attendance SET time_out = NULL WHERE id = ? AND employee_id = ? AND time_out IS NOT NULL";
    }
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error (prepare undo)']);
        exit();
    }
    mysqli_stmt_bind_param($stmt, "ii", $shiftId, $employeeId);

    if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Clock-out undone',
            'shift_id' => $shiftId
        ]);
        logActivity($db, 'Clock-out Undone', "Employee #{$employeeId} undone clock-out for shift #{$shiftId}");
    } else {
        echo json_encode(['success' => false, 'message' => 'Unable to undo clock-out']);
    }
    mysqli_stmt_close($stmt);
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
$hasRunningCol = attendanceHasIsTimeRunningColumn($db);
$hasStatusCol = attendanceHasStatusColumn($db);
if ($hasRunningCol) {
    $sql = $hasStatusCol
        ? "UPDATE attendance SET time_out = NOW(), status = NULL, is_time_running = 0 WHERE id = ? AND employee_id = ? AND time_out IS NULL"
        : "UPDATE attendance SET time_out = NOW(), is_time_running = 0 WHERE id = ? AND employee_id = ? AND time_out IS NULL";
} else {
    $sql = $hasStatusCol
        ? "UPDATE attendance SET time_out = NOW(), status = NULL WHERE id = ? AND employee_id = ? AND time_out IS NULL"
        : "UPDATE attendance SET time_out = NOW() WHERE id = ? AND employee_id = ? AND time_out IS NULL";
}
$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, "ii", $shiftId, $employeeId);

if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
    $timeOut = null;
    $shiftHours = null;
    $totalHoursToday = null;

    $timeStmt = mysqli_prepare($db, "SELECT time_in, time_out FROM attendance WHERE id = ? AND employee_id = ? LIMIT 1");
    if ($timeStmt) {
        mysqli_stmt_bind_param($timeStmt, 'ii', $shiftId, $employeeId);
        mysqli_stmt_execute($timeStmt);
        $timeRes = mysqli_stmt_get_result($timeStmt);
        if ($timeRes && ($timeRow = mysqli_fetch_assoc($timeRes))) {
            $timeOut = $timeRow['time_out'] ?? null;
        }
        mysqli_stmt_close($timeStmt);
    }

    $hoursStmt = mysqli_prepare($db, "SELECT (TIME_TO_SEC(TIMEDIFF(time_out, time_in)) / 3600) AS shift_hours FROM attendance WHERE id = ? AND employee_id = ? AND time_in IS NOT NULL AND time_out IS NOT NULL LIMIT 1");
    if ($hoursStmt) {
        mysqli_stmt_bind_param($hoursStmt, 'ii', $shiftId, $employeeId);
        mysqli_stmt_execute($hoursStmt);
        $hoursRes = mysqli_stmt_get_result($hoursStmt);
        if ($hoursRes && ($hoursRow = mysqli_fetch_assoc($hoursRes))) {
            $shiftHours = isset($hoursRow['shift_hours']) ? round(floatval($hoursRow['shift_hours']), 2) : null;
        }
        mysqli_stmt_close($hoursStmt);
    }

    $totalStmt = mysqli_prepare($db, "SELECT SUM(GREATEST(0, TIME_TO_SEC(TIMEDIFF(COALESCE(time_out, NOW()), time_in)))) / 3600 AS total_hours_today FROM attendance WHERE employee_id = ? AND attendance_date = CURDATE() AND time_in IS NOT NULL");
    if ($totalStmt) {
        mysqli_stmt_bind_param($totalStmt, 'i', $employeeId);
        mysqli_stmt_execute($totalStmt);
        $totalRes = mysqli_stmt_get_result($totalStmt);
        if ($totalRes && ($totalRow = mysqli_fetch_assoc($totalRes))) {
            $totalHoursToday = isset($totalRow['total_hours_today']) ? round(floatval($totalRow['total_hours_today']), 2) : null;
        }
        mysqli_stmt_close($totalStmt);
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Clocked out successfully', 
        'time_out' => $timeOut,
        'shift_hours' => $shiftHours,
        'total_hours_today' => $totalHoursToday
    ]);
    logActivity($db, 'Clocked Out', "Employee #{$employeeId} clocked out, worked {$shiftHours} hours");
} else {
    echo json_encode(['success' => false, 'message' => 'Unable to clock out or already clocked out']);
}
mysqli_stmt_close($stmt);
?>