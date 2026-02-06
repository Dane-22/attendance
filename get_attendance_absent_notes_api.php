<?php
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

function attendanceHasColumn($db, $columnName) {
    $safe = mysqli_real_escape_string($db, $columnName);
    $sql = "SHOW COLUMNS FROM `attendance` LIKE '{$safe}'";
    $result = mysqli_query($db, $sql);
    return $result && mysqli_num_rows($result) > 0;
}

$employeeId = isset($_REQUEST['employee_id']) ? (int)$_REQUEST['employee_id'] : 0;
$date = isset($_REQUEST['date']) ? trim($_REQUEST['date']) : date('Y-m-d');

if ($employeeId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid employee_id']);
    exit();
}

if (!attendanceHasColumn($db, 'absent_notes')) {
    echo json_encode(['success' => true, 'absent_notes' => '' ]);
    exit();
}

$sql = "SELECT COALESCE(absent_notes, '') AS absent_notes FROM attendance WHERE employee_id = ? AND attendance_date = ? ORDER BY id DESC LIMIT 1";
$stmt = mysqli_prepare($db, $sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error (prepare)']);
    exit();
}

mysqli_stmt_bind_param($stmt, 'is', $employeeId, $date);
if (!mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => false, 'message' => 'Database error (execute): ' . mysqli_error($db)]);
    mysqli_stmt_close($stmt);
    exit();
}

$result = mysqli_stmt_get_result($stmt);
$row = $result ? mysqli_fetch_assoc($result) : null;
$notes = $row ? (string)$row['absent_notes'] : '';

mysqli_stmt_close($stmt);
mysqli_close($db);

echo json_encode(['success' => true, 'absent_notes' => $notes]);
?>
