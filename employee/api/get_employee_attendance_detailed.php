<?php
/**
 * Get Employee Attendance Detailed
 * Returns detailed daily attendance with time_in, time_out, and status for calendar view
 */

require_once __DIR__ . '/../../conn/db_connection.php';
require_once __DIR__ . '/../../functions.php';

session_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

function respond($statusCode, $payload) {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

// Check authentication
if (empty($_SESSION['logged_in']) || !in_array($_SESSION['position'], ['Admin', 'Super Admin', 'Developer'])) {
    respond(401, ['success' => false, 'message' => 'Unauthorized']);
}

// Get parameters
$employeeId = isset($_REQUEST['employee_id']) ? (int)$_REQUEST['employee_id'] : 0;
$monthParam = isset($_REQUEST['month']) ? trim($_REQUEST['month']) : date('Y-m');

if ($employeeId <= 0) {
    respond(400, ['success' => false, 'message' => 'Employee ID is required.']);
}

// Validate month format
if (!preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
    respond(400, ['success' => false, 'message' => 'Invalid month format. Expected YYYY-MM.']);
}

list($year, $month) = explode('-', $monthParam);
$year = (int)$year;
$month = (int)$month;

if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
    respond(400, ['success' => false, 'message' => 'Invalid month or year.']);
}

$startDate = sprintf('%04d-%02d-01', $year, $month);
$endDate = sprintf('%04d-%02d-%02d', $year, $month, date('t', strtotime($startDate)));

// Get employee info
$employeeSql = "SELECT id, first_name, last_name, position, employee_code FROM employees WHERE id = ?";
$employeeStmt = mysqli_prepare($db, $employeeSql);
if (!$employeeStmt) {
    respond(500, ['success' => false, 'message' => 'Failed to prepare employee query.']);
}

mysqli_stmt_bind_param($employeeStmt, 'i', $employeeId);
mysqli_stmt_execute($employeeStmt);
$employeeResult = mysqli_stmt_get_result($employeeStmt);
$employee = mysqli_fetch_assoc($employeeResult);
mysqli_stmt_close($employeeStmt);

if (!$employee) {
    respond(404, ['success' => false, 'message' => 'Employee not found.']);
}

$employeeName = trim($employee['first_name'] . ' ' . $employee['last_name']);
$position = $employee['position'];
$isWorker = strtolower($position) === 'worker';

// Get attendance data - select earliest time_in record per day
$sql = "SELECT 
    a.attendance_date,
    a.time_in,
    a.time_out,
    a.status,
    a.branch_name,
    TIMESTAMPDIFF(MINUTE, a.time_in, a.time_out) / 60 as hours
FROM attendance a
INNER JOIN (
    SELECT attendance_date, MIN(time_in) as min_time_in
    FROM attendance
    WHERE employee_id = ?
      AND attendance_date BETWEEN ? AND ?
      AND time_in IS NOT NULL
    GROUP BY attendance_date
) earliest ON a.attendance_date = earliest.attendance_date AND a.time_in = earliest.min_time_in
WHERE a.employee_id = ?
  AND a.attendance_date BETWEEN ? AND ?
ORDER BY a.attendance_date ASC";

$stmt = mysqli_prepare($db, $sql);
if (!$stmt) {
    respond(500, ['success' => false, 'message' => 'Failed to prepare attendance query.']);
}

mysqli_stmt_bind_param($stmt, 'isssss', $employeeId, $startDate, $endDate, $employeeId, $startDate, $endDate);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$attendanceByDate = [];
while ($row = mysqli_fetch_assoc($result)) {
    $date = $row['attendance_date'];
    
    // Format times
    $timeIn = $row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : null;
    $timeOut = $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : null;
    
    // Determine status
    $status = 'Absent';
    if ($row['time_in']) {
        // Check for late status (Workers only, 7:15 AM threshold)
        if ($isWorker) {
            $timeInObj = strtotime($row['time_in']);
            $lateThreshold = strtotime(date('Y-m-d', $timeInObj) . ' 07:15:00');
            if ($timeInObj >= $lateThreshold) {
                $status = 'Late';
            } else {
                $status = 'Present';
            }
        } else {
            $status = 'Present';
        }
    }
    
    $attendanceByDate[$date] = [
        'date' => $date,
        'time_in' => $timeIn,
        'time_out' => $timeOut,
        'status' => $status,
        'branch' => $row['branch_name'] ?? 'N/A',
        'hours' => $row['hours'] ? round((float)$row['hours'], 2) : 0
    ];
}

mysqli_stmt_close($stmt);
mysqli_close($db);

// Build full month data
$daysInMonth = (int)date('t', strtotime($startDate));
$days = [];

for ($day = 1; $day <= $daysInMonth; $day++) {
    $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
    
    if (isset($attendanceByDate[$dateStr])) {
        $days[] = $attendanceByDate[$dateStr];
    } else {
        $days[] = [
            'date' => $dateStr,
            'time_in' => null,
            'time_out' => null,
            'status' => 'No Record',
            'branch' => null,
            'hours' => 0
        ];
    }
}

respond(200, [
    'success' => true,
    'employee_id' => $employeeId,
    'employee_name' => $employeeName,
    'position' => $position,
    'month' => $monthParam,
    'days' => $days
]);
?>
