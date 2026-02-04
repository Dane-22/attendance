<?php
// get_shift_logs_api.php
if (file_exists(__DIR__ . '/conn/db_connection.php')) {
    require_once __DIR__ . '/conn/db_connection.php';
} else {
    require_once __DIR__ . '/db_connection.php';
}

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

// Get parameters
$employee_id = isset($_REQUEST['employee_id']) ? (int)$_REQUEST['employee_id'] : 0;
$date = isset($_REQUEST['date']) ? trim($_REQUEST['date']) : date("Y-m-d");
$limit = isset($_REQUEST['limit']) ? (int)$_REQUEST['limit'] : 50;

if ($employee_id <= 0) {
    echo json_encode(["success" => false, "message" => "Employee ID is required"]);
    exit;
}

function attendanceHasColumn($db, $columnName) {
    $safe = mysqli_real_escape_string($db, $columnName);
    $sql = "SHOW COLUMNS FROM `attendance` LIKE '{$safe}'";
    $result = mysqli_query($db, $sql);
    return $result && mysqli_num_rows($result) > 0;
}

$hasTimeIn = attendanceHasColumn($db, 'time_in');
$hasTimeOut = attendanceHasColumn($db, 'time_out');

$timeInSelect = $hasTimeIn ? "a.time_in" : "NULL";
$timeOutSelect = $hasTimeOut ? "a.time_out" : "NULL";

// Get ALL attendance logs for the employee on the specified date
$sql = "SELECT 
            a.id,
            a.employee_id,
            a.attendance_date,
            {$timeInSelect} as time_in,
            {$timeOutSelect} as time_out,
            a.status,
            a.branch_name,
            a.created_at
        FROM attendance a
        WHERE a.employee_id = ? 
        AND a.attendance_date = ?
        ORDER BY a.created_at ASC
        LIMIT ?";

$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, "isi", $employee_id, $date, $limit);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$logs = [];
while ($row = mysqli_fetch_assoc($result)) {
    $logs[] = [
        'id' => (int)$row['id'],
        'time_in' => $row['time_in'],
        'time_out' => $row['time_out'],
        'status' => $row['status'],
        'branch_name' => $row['branch_name'],
        'created_at' => $row['created_at']
    ];
}

$response = [
    'success' => true,
    'logs' => $logs,
    'count' => count($logs),
    'employee_id' => $employee_id,
    'date' => $date
];

echo json_encode($response);

mysqli_stmt_close($stmt);
mysqli_close($db);
?>
