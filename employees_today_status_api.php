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

// Get parameters (branch_name kept for backward compatibility; endpoint returns all employees)
$branch_name = isset($_REQUEST['branch_name']) ? trim($_REQUEST['branch_name']) : '';
$date_today = date("Y-m-d");

function attendanceHasColumn($db, $columnName) {
    $safe = mysqli_real_escape_string($db, $columnName);
    $sql = "SHOW COLUMNS FROM `attendance` LIKE '{$safe}'";
    $result = mysqli_query($db, $sql);
    return $result && mysqli_num_rows($result) > 0;
}

$hasTimeIn = attendanceHasColumn($db, 'time_in');
$hasTimeOut = attendanceHasColumn($db, 'time_out');
$hasIsTimeRunning = attendanceHasColumn($db, 'is_time_running');
$hasTotalOtHrs = attendanceHasColumn($db, 'total_ot_hrs');
$hasIsOvertimeRunning = attendanceHasColumn($db, 'is_overtime_running');

$timeInSelect = $hasTimeIn ? "a.time_in" : "NULL";
$timeOutSelect = $hasTimeOut ? "a.time_out" : "NULL";
$isTimeRunningSelect = $hasIsTimeRunning ? "COALESCE(a.is_time_running, 0)" : "0";
$totalOtHrsSelect = $hasTotalOtHrs ? "COALESCE(a.total_ot_hrs, '')" : "''";
$isOvertimeRunningSelect = $hasIsOvertimeRunning ? "COALESCE(a.is_overtime_running, 0)" : "0";

// Get ALL employees with their assigned branch (employees.branch_id -> branches) and latest attendance log for today
$sql = "SELECT 
            e.id,
            e.employee_code,
            e.first_name,
            e.last_name,
            e.position,
            e.branch_id,
            b.branch_name AS assigned_branch_name,
            a.branch_name,
            a.status as today_status,
            {$timeInSelect} as time_in,
            {$timeOutSelect} as time_out,
            COALESCE(a.is_auto_absent, 0) as is_auto_absent,
            {$isTimeRunningSelect} as is_time_running,
            {$isOvertimeRunningSelect} as is_overtime_running,
            {$totalOtHrsSelect} as total_ot_hrs,
            CASE
              WHEN {$isTimeRunningSelect} = 1 THEN 1
              ELSE 0
            END as is_timed_in
        FROM employees e
        LEFT JOIN branches b ON e.branch_id = b.id
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
          AND e.position = 'Worker'
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
        'branch_id' => isset($row['branch_id']) ? (is_null($row['branch_id']) ? null : (int)$row['branch_id']) : null,
        'assigned_branch_name' => $row['assigned_branch_name'],
        'branch_name' => $row['branch_name'],
        'today_status' => $row['today_status'],
        'time_in' => $row['time_in'],
        'time_out' => $row['time_out'],
        'is_auto_absent' => (bool)$row['is_auto_absent'],
        'is_time_running' => (int)($row['is_time_running'] ?? 0) === 1,
        'is_overtime_running' => (int)($row['is_overtime_running'] ?? 0) === 1,
        'is_timed_in' => (int)$row['is_timed_in'] === 1,
        'total_ot_hrs' => isset($row['total_ot_hrs']) ? (string)$row['total_ot_hrs'] : ''
    ];
}

echo json_encode($employees);

mysqli_stmt_close($stmt);
mysqli_close($db);
?>
