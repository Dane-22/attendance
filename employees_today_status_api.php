<?php
// employees_today_status_api.php
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
$branch_name = isset($_REQUEST['branch_name']) ? trim($_REQUEST['branch_name']) : '';
$date_today = date("Y-m-d");

if (empty($branch_name)) {
    echo json_encode(["success" => false, "message" => "Branch name is required"]);
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
$hasIsTimeRunning = attendanceHasColumn($db, 'is_time_running');

$timeInSelect = $hasTimeIn ? "a.time_in" : "NULL";
$timeOutSelect = $hasTimeOut ? "a.time_out" : "NULL";
$isTimeRunningSelect = $hasIsTimeRunning ? "COALESCE(a.is_time_running, 0)" : "0";

// Get ALL employees with their latest attendance log for today
$sql = "SELECT 
            e.id,
            e.employee_code,
            e.first_name,
            e.last_name,
            e.position,
            a.branch_name,
            a.status as today_status,
            {$timeInSelect} as time_in,
            {$timeOutSelect} as time_out,
            COALESCE(a.is_auto_absent, 0) as is_auto_absent,
            {$isTimeRunningSelect} as is_time_running,
            CASE
              WHEN {$isTimeRunningSelect} = 1 THEN 1
              ELSE 0
            END as is_timed_in
        FROM employees e
        LEFT JOIN (
            SELECT a1.*
            FROM attendance a1
            INNER JOIN (
                SELECT employee_id, MAX(id) AS max_id
                FROM attendance
                WHERE attendance_date = ?
                GROUP BY employee_id
            ) t ON a1.id = t.max_id
        ) a ON e.id = a.employee_id
        WHERE e.status = 'Active'
        ORDER BY e.employee_code";

$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, "s", $date_today);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$employees = [];
while ($row = mysqli_fetch_assoc($result)) {
    $employees[] = [
        'id' => (int)$row['id'],
        'employee_code' => $row['employee_code'],
        'first_name' => $row['first_name'],
        'last_name' => $row['last_name'],
        'position' => $row['position'],
        'branch_name' => $row['branch_name'],
        'today_status' => $row['today_status'],
        'time_in' => $row['time_in'],
        'time_out' => $row['time_out'],
        'is_auto_absent' => (bool)$row['is_auto_absent'],
        'is_time_running' => (int)($row['is_time_running'] ?? 0) === 1,
        'is_timed_in' => (int)$row['is_timed_in'] === 1
    ];
}

echo json_encode($employees);

mysqli_stmt_close($stmt);
mysqli_close($db);
?>
