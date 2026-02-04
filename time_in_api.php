<?php
require_once __DIR__ . '/conn/db_connection.php';
header('Content-Type: application/json');

$employeeId = $_POST['employee_id'] ?? null;
$branchName = $_POST['branch_name'] ?? null;
$debug = isset($_POST['debug']) ? (int)$_POST['debug'] : 0;

if (!$employeeId || !$branchName) {
    echo json_encode(['success' => false, 'message' => 'Missing employee_id or branch_name']);
    exit();
}

function attendanceHasColumn($db, $columnName) {
    $safe = mysqli_real_escape_string($db, $columnName);
    $sql = "SHOW COLUMNS FROM `attendance` LIKE '{$safe}'";
    $result = mysqli_query($db, $sql);
    return $result && mysqli_num_rows($result) > 0;
}

function getCurrentDatabaseName($db) {
    $result = mysqli_query($db, "SELECT DATABASE() AS db_name");
    if (!$result) return null;
    $row = mysqli_fetch_assoc($result);
    return $row ? ($row['db_name'] ?? null) : null;
}

function hasAttendanceTable($db) {
    $result = mysqli_query($db, "SHOW TABLES LIKE 'attendance'");
    return $result && mysqli_num_rows($result) > 0;
}

$hasTimeIn = attendanceHasColumn($db, 'time_in');
$hasTimeOut = attendanceHasColumn($db, 'time_out');
$hasIsTimeRunning = attendanceHasColumn($db, 'is_time_running');

if (!$hasTimeIn) {
    $payload = [
        'success' => false,
        'message' => 'Server database is missing attendance.time_in. Please run DB migration on the correct database.'
    ];
    if ($debug === 1) {
        $payload['debug'] = [
            'db_name' => getCurrentDatabaseName($db),
            'has_attendance_table' => hasAttendanceTable($db),
            'mysqli_error' => mysqli_error($db),
        ];
    }
    echo json_encode($payload);
    exit();
}

if (!$hasTimeOut) {
    $payload = [
        'success' => false,
        'message' => 'Server database is missing attendance.time_out. Please run DB migration on the correct database.'
    ];
    if ($debug === 1) {
        $payload['debug'] = [
            'db_name' => getCurrentDatabaseName($db),
            'has_attendance_table' => hasAttendanceTable($db),
            'mysqli_error' => mysqli_error($db),
        ];
    }
    echo json_encode($payload);
    exit();
}

$date = date('Y-m-d');

// Check if already clocked in for today
$sql = $hasIsTimeRunning
    ? "SELECT id, time_in, time_out, is_time_running FROM attendance WHERE employee_id = ? AND attendance_date = ? ORDER BY id DESC LIMIT 1"
    : "SELECT id, time_in, time_out, 0 as is_time_running FROM attendance WHERE employee_id = ? AND attendance_date = ? ORDER BY id DESC LIMIT 1";
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

$insertSql = $hasIsTimeRunning
    ? "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, status, created_at, is_time_running) VALUES (?, ?, ?, NOW(), 'Present', NOW(), 1)"
    : "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, status, created_at) VALUES (?, ?, ?, NOW(), 'Present', NOW())";
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
