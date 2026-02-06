<?php
require_once __DIR__ . '/conn/db_connection.php';
header('Content-Type: application/json');

$employeeId = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : 0;
$branchName = isset($_POST['branch_name']) ? trim($_POST['branch_name']) : '';
$date = isset($_POST['date']) ? trim($_POST['date']) : date('Y-m-d');

if ($employeeId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid employee_id']);
    exit();
}

function attendanceHasColumn($db, $columnName) {
    $safe = mysqli_real_escape_string($db, $columnName);
    $sql = "SHOW COLUMNS FROM `attendance` LIKE '{$safe}'";
    $result = mysqli_query($db, $sql);
    return $result && mysqli_num_rows($result) > 0;
}

$hasStatus = attendanceHasColumn($db, 'status');
$hasUpdatedAt = attendanceHasColumn($db, 'updated_at');
$hasCreatedAt = attendanceHasColumn($db, 'created_at');
$hasIsTimeRunning = attendanceHasColumn($db, 'is_time_running');
$hasIsOvertimeRunning = attendanceHasColumn($db, 'is_overtime_running');
$hasTotalOtHrs = attendanceHasColumn($db, 'total_ot_hrs');

if (!$hasStatus) {
    echo json_encode(['success' => false, 'message' => 'Server database is missing attendance.status. Please run DB migration.']);
    exit();
}

// Find latest attendance row for today
$selectSql = "SELECT id FROM attendance WHERE employee_id = ? AND attendance_date = ? ORDER BY id DESC LIMIT 1";
$selectStmt = mysqli_prepare($db, $selectSql);
mysqli_stmt_bind_param($selectStmt, 'is', $employeeId, $date);
mysqli_stmt_execute($selectStmt);
$res = mysqli_stmt_get_result($selectStmt);
$row = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($selectStmt);

if ($row && isset($row['id'])) {
    $attendanceId = (int)$row['id'];

    $setParts = [];
    $types = '';
    $params = [];

    $setParts[] = "status = ?";
    $types .= 's';
    $params[] = 'Absent';

    if ($hasIsTimeRunning) {
        $setParts[] = "is_time_running = 0";
    }

    if ($hasIsOvertimeRunning) {
        $setParts[] = "is_overtime_running = 0";
    }

    if ($hasTotalOtHrs) {
        $setParts[] = "total_ot_hrs = ''";
    }

    if ($hasUpdatedAt) {
        $setParts[] = "updated_at = NOW()";
    }

    $updateSql = "UPDATE attendance SET " . implode(', ', $setParts) . " WHERE id = ?";
    $types .= 'i';
    $params[] = $attendanceId;

    $stmt = mysqli_prepare($db, $updateSql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Attendance marked as Absent']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($db)]);
    }

    mysqli_stmt_close($stmt);
    exit();
}

// If no record exists for today, insert a new one marked absent
if (!$branchName) {
    $branchName = 'N/A';
}

$columns = ['employee_id', 'branch_name', 'attendance_date', 'status'];
$placeholders = ['?', '?', '?', '?'];
$types = 'isss';
$params = [$employeeId, $branchName, $date, 'Absent'];

if ($hasCreatedAt) {
    $columns[] = 'created_at';
    $placeholders[] = 'NOW()';
}

if ($hasIsTimeRunning) {
    $columns[] = 'is_time_running';
    $placeholders[] = '0';
}

if ($hasIsOvertimeRunning) {
    $columns[] = 'is_overtime_running';
    $placeholders[] = '0';
}

if ($hasTotalOtHrs) {
    $columns[] = 'total_ot_hrs';
    $placeholders[] = "''";
}

$insertSql = "INSERT INTO attendance (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
$insertStmt = mysqli_prepare($db, $insertSql);
mysqli_stmt_bind_param($insertStmt, $types, ...$params);

if (mysqli_stmt_execute($insertStmt)) {
    echo json_encode(['success' => true, 'message' => 'Attendance marked as Absent']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($db)]);
}

mysqli_stmt_close($insertStmt);
?>
