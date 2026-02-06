<?php
// set_attendance_ot_hrs_api.php
if (file_exists(__DIR__ . '/conn/db_connection.php')) {
    require_once __DIR__ . '/conn/db_connection.php';
} else {
    require_once __DIR__ . '/db_connection.php';
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

$employeeId = isset($_REQUEST['employee_id']) ? intval($_REQUEST['employee_id']) : 0;
$otHoursRaw = isset($_REQUEST['ot_hours']) ? (string)$_REQUEST['ot_hours'] : '';
$date = isset($_REQUEST['date']) && $_REQUEST['date'] !== '' ? $_REQUEST['date'] : date('Y-m-d');

if ($employeeId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid employee_id']);
    exit();
}

$otHours = preg_replace('/[^0-9]/', '', $otHoursRaw);
if ($otHours === '') {
    echo json_encode(['success' => false, 'message' => 'Missing ot_hours']);
    exit();
}

function attendanceHasColumn($db, $columnName) {
    $safe = mysqli_real_escape_string($db, $columnName);
    $sql = "SHOW COLUMNS FROM `attendance` LIKE '{$safe}'";
    $result = mysqli_query($db, $sql);
    return $result && mysqli_num_rows($result) > 0;
}

$hasTotalOtHrs = attendanceHasColumn($db, 'total_ot_hrs');
if (!$hasTotalOtHrs) {
    echo json_encode(['success' => false, 'message' => 'Server database is missing attendance.total_ot_hrs']);
    exit();
}

$hasUpdatedAt = attendanceHasColumn($db, 'updated_at');

// Find latest attendance row for employee on the given date
$findSql = "SELECT id FROM attendance WHERE employee_id = ? AND attendance_date = ? ORDER BY id DESC LIMIT 1";
$findStmt = mysqli_prepare($db, $findSql);
if (!$findStmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare query: ' . mysqli_error($db)]);
    exit();
}
mysqli_stmt_bind_param($findStmt, 'is', $employeeId, $date);
mysqli_stmt_execute($findStmt);
$res = mysqli_stmt_get_result($findStmt);
$row = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($findStmt);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'No attendance record found for the given date']);
    exit();
}

$attendanceId = intval($row['id']);

$updateSql = $hasUpdatedAt
    ? "UPDATE attendance SET total_ot_hrs = ?, updated_at = NOW() WHERE id = ?"
    : "UPDATE attendance SET total_ot_hrs = ? WHERE id = ?";

$updateStmt = mysqli_prepare($db, $updateSql);
if (!$updateStmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare update: ' . mysqli_error($db)]);
    exit();
}
mysqli_stmt_bind_param($updateStmt, 'si', $otHours, $attendanceId);

if (!mysqli_stmt_execute($updateStmt)) {
    echo json_encode(['success' => false, 'message' => 'Failed to update OT hours: ' . mysqli_error($db)]);
    mysqli_stmt_close($updateStmt);
    exit();
}
mysqli_stmt_close($updateStmt);

echo json_encode([
    'success' => true,
    'message' => 'OT hours updated',
    'attendance_id' => $attendanceId,
    'employee_id' => $employeeId,
    'date' => $date,
    'total_ot_hrs' => $otHours,
]);

mysqli_close($db);
?>
