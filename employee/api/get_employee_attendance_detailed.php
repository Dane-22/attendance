<?php
/**
 * Get Employee Attendance Detailed
 * Returns detailed daily attendance with time_in, time_out, and status for calendar view
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../update_allowance_errors.log');

require_once __DIR__ . '/../../conn/db_connection.php';
require_once __DIR__ . '/../../functions.php';

session_start();

error_log("[get_employee_attendance_detailed.php] Request received: " . print_r($_REQUEST, true));

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

// Get attendance data - select ALL records per day, ordered by time_in
// Include voided records but mark them as voided
$sql = "SELECT
    a.attendance_date,
    a.time_in,
    a.time_out,
    a.status,
    a.branch_name,
    a.is_voided,
    a.void_reason,
    TIMESTAMPDIFF(MINUTE, a.time_in, a.time_out) / 60 as hours
FROM attendance a
WHERE a.employee_id = ?
  AND a.attendance_date BETWEEN ? AND ?
  AND a.time_in IS NOT NULL
ORDER BY a.attendance_date ASC, a.time_in ASC";

$stmt = mysqli_prepare($db, $sql);
if (!$stmt) {
    respond(500, ['success' => false, 'message' => 'Failed to prepare attendance query.']);
}

mysqli_stmt_bind_param($stmt, 'iss', $employeeId, $startDate, $endDate);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$attendanceByDate = [];
while ($row = mysqli_fetch_assoc($result)) {
    $date = $row['attendance_date'];

    // Format times
    $timeIn = $row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : null;
    $timeOut = $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : null;

    // Determine status for this record
    $recordStatus = 'Absent';
    if (!empty($row['is_voided'])) {
        $recordStatus = 'Voided';
    } elseif ($row['time_in']) {
        // Check for late status (Workers only, 7:15 AM threshold)
        if ($isWorker) {
            $timeInObj = strtotime($row['time_in']);
            $lateThreshold = strtotime(date('Y-m-d', $timeInObj) . ' 07:15:00');
            if ($timeInObj >= $lateThreshold) {
                $recordStatus = 'Late';
            } else {
                $recordStatus = 'Present';
            }
        } else {
            $recordStatus = 'Present';
        }
    }

    // Initialize date entry if not exists
    if (!isset($attendanceByDate[$date])) {
        $attendanceByDate[$date] = [
            'date' => $date,
            'records' => [],
            'status' => 'No Record',
            'total_hours' => 0
        ];
    }

    // Add this record to the day's records
    $attendanceByDate[$date]['records'][] = [
        'time_in' => $timeIn,
        'time_out' => $timeOut,
        'status' => $recordStatus,
        'branch' => $row['branch_name'] ?? 'N/A',
        'hours' => $row['hours'] ? round((float)$row['hours'], 2) : 0,
        'is_voided' => !empty($row['is_voided']),
        'void_reason' => $row['void_reason'] ?? null
    ];

    // Update total hours
    $attendanceByDate[$date]['total_hours'] += ($row['hours'] ? round((float)$row['hours'], 2) : 0);
}

// Determine overall day status based on all records
foreach ($attendanceByDate as $date => &$dayData) {
    if (empty($dayData['records'])) {
        $dayData['status'] = 'No Record';
        continue;
    }

    // Use the first record's time_in for lateness check (chronologically first)
    $firstRecord = $dayData['records'][0];
    $dayData['status'] = $firstRecord['status'];

    // If any record shows Late, mark day as Late
    foreach ($dayData['records'] as $record) {
        if ($record['status'] === 'Late') {
            $dayData['status'] = 'Late';
            break;
        }
    }
}
unset($dayData);

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
            'records' => [],
            'status' => 'No Record',
            'total_hours' => 0
        ];
    }
}

error_log("[get_employee_attendance_detailed.php] Success - returning " . count($days) . " days for employee $employeeId");

respond(200, [
    'success' => true,
    'employee_id' => $employeeId,
    'employee_name' => $employeeName,
    'position' => $position,
    'month' => $monthParam,
    'days' => $days
]);
?>
