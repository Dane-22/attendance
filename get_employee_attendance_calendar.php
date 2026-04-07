<?php
if (file_exists(__DIR__ . '/conn/db_connection.php')) {
    require_once __DIR__ . '/conn/db_connection.php';
} else {
    require_once __DIR__ . '/db_connection.php';
}
require_once __DIR__ . '/payroll_report_helpers.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

function respond_calendar($statusCode, $payload) {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

$employeeId = isset($_REQUEST['employee_id']) ? (int)$_REQUEST['employee_id'] : 0;
$monthParam = isset($_REQUEST['month']) ? trim($_REQUEST['month']) : date('Y-m');

if ($employeeId <= 0) {
    respond_calendar(400, [
        'success' => false,
        'message' => 'Employee ID is required.',
    ]);
}

$parsedMonth = payroll_parse_month($monthParam);
if ($parsedMonth === null) {
    respond_calendar(400, [
        'success' => false,
        'message' => 'Invalid month. Expected YYYY-MM.',
    ]);
}

list($year, $month) = $parsedMonth;
$startDate = sprintf('%04d-%02d-01', $year, $month);
$endDate = sprintf('%04d-%02d-%02d', $year, $month, payroll_last_day_of_month($year, $month));

function attendance_calendar_has_column($db, $columnName) {
    $safe = mysqli_real_escape_string($db, $columnName);
    $sql = "SHOW COLUMNS FROM `attendance` LIKE '{$safe}'";
    $result = mysqli_query($db, $sql);
    return $result && mysqli_num_rows($result) > 0;
}

$hasTimeIn = attendance_calendar_has_column($db, 'time_in');
$hasTimeOut = attendance_calendar_has_column($db, 'time_out');
$timeInExpr = $hasTimeIn ? "a.time_in IS NOT NULL" : "0";
$timeOutExpr = $hasTimeOut ? "a.time_out IS NOT NULL" : "0";
$hoursExpr = ($hasTimeIn && $hasTimeOut)
    ? "GREATEST(TIMESTAMPDIFF(MINUTE, a.time_in, a.time_out), 0) / 60"
    : "0";

$sql = "
    SELECT
        a.attendance_date AS work_date,
        COUNT(*) AS log_count,
        SUM(CASE
            WHEN {$timeInExpr} AND {$timeOutExpr}
            THEN {$hoursExpr}
            ELSE 0
        END) AS total_hours,
        GROUP_CONCAT(DISTINCT NULLIF(TRIM(COALESCE(a.status, '')), '') ORDER BY a.status SEPARATOR ',') AS status_summary,
        MAX(CASE
            WHEN {$timeInExpr} OR LOWER(COALESCE(a.status, '')) IN ('present', 'late')
            THEN 1
            ELSE 0
        END) AS has_attendance
    FROM attendance a
    WHERE a.employee_id = ?
      AND a.attendance_date BETWEEN ? AND ?
    GROUP BY a.attendance_date
    ORDER BY a.attendance_date ASC
";

$stmt = mysqli_prepare($db, $sql);
if (!$stmt) {
    mysqli_close($db);
    respond_calendar(500, [
        'success' => false,
        'message' => 'Failed to prepare attendance calendar query.',
        'error' => mysqli_error($db),
    ]);
}

mysqli_stmt_bind_param($stmt, 'iss', $employeeId, $startDate, $endDate);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$days = [];
while ($row = mysqli_fetch_assoc($result)) {
    $statusSummary = [];
    if (!empty($row['status_summary'])) {
        $statusSummary = array_values(array_filter(array_map('trim', explode(',', $row['status_summary']))));
    }

    $days[] = [
        'date' => $row['work_date'],
        'has_attendance' => ((int)$row['has_attendance']) === 1,
        'log_count' => (int)$row['log_count'],
        'total_hours' => payroll_to_float($row['total_hours']),
        'status_summary' => $statusSummary,
    ];
}

mysqli_stmt_close($stmt);
mysqli_close($db);

respond_calendar(200, [
    'success' => true,
    'employee_id' => $employeeId,
    'month' => $monthParam,
    'days' => $days,
]);
?>
