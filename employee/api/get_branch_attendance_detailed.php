<?php
/**
 * Get Branch Attendance Detailed
 * Returns detailed daily attendance for all employees at a specific branch for calendar view
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

// Rate Limiting Configuration
$RATE_LIMIT_MAX_REQUESTS = 60;
$RATE_LIMIT_WINDOW = 60;

// Initialize rate limiting in session
if (!isset($_SESSION['branch_calendar_rate_limit'])) {
    $_SESSION['branch_calendar_rate_limit'] = [
        'requests' => [],
        'blocked_until' => null
    ];
}

$now = time();

// Check if user is currently blocked
if ($_SESSION['branch_calendar_rate_limit']['blocked_until'] && $now < $_SESSION['branch_calendar_rate_limit']['blocked_until']) {
    $retryAfter = $_SESSION['branch_calendar_rate_limit']['blocked_until'] - $now;
    respond(429, ['success' => false, 'message' => 'Too many requests. Please try again in ' . $retryAfter . ' seconds.']);
}

// Clean old requests outside the window
$_SESSION['branch_calendar_rate_limit']['requests'] = array_filter(
    $_SESSION['branch_calendar_rate_limit']['requests'],
    function($timestamp) use ($now, $RATE_LIMIT_WINDOW) {
        return ($now - $timestamp) < $RATE_LIMIT_WINDOW;
    }
);

// Check if limit exceeded
if (count($_SESSION['branch_calendar_rate_limit']['requests']) >= $RATE_LIMIT_MAX_REQUESTS) {
    $_SESSION['branch_calendar_rate_limit']['blocked_until'] = $now + $RATE_LIMIT_WINDOW;
    respond(429, ['success' => false, 'message' => 'Rate limit exceeded. Please try again in ' . $RATE_LIMIT_WINDOW . ' seconds.']);
}

// Record this request
$_SESSION['branch_calendar_rate_limit']['requests'][] = $now;

// Check authentication
if (empty($_SESSION['logged_in']) || !in_array($_SESSION['position'], ['Admin', 'Super Admin', 'Developer'])) {
    respond(401, ['success' => false, 'message' => 'Unauthorized']);
}

// Get parameters
$branchName = isset($_REQUEST['branch_name']) ? trim($_REQUEST['branch_name']) : '';
$monthParam = isset($_REQUEST['month']) ? trim($_REQUEST['month']) : date('Y-m');

if (empty($branchName)) {
    respond(400, ['success' => false, 'message' => 'Branch name is required.']);
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

// Get attendance data for the branch
$sql = "SELECT 
    a.attendance_date,
    a.time_in,
    a.time_out,
    a.status,
    e.id as employee_id,
    e.first_name,
    e.last_name,
    e.position
FROM attendance a
INNER JOIN employees e ON a.employee_id = e.id
WHERE a.branch_name = ?
  AND a.attendance_date BETWEEN ? AND ?
ORDER BY a.attendance_date ASC, a.time_in ASC";

$stmt = mysqli_prepare($db, $sql);
if (!$stmt) {
    respond(500, ['success' => false, 'message' => 'Failed to prepare attendance query.']);
}

mysqli_stmt_bind_param($stmt, 'sss', $branchName, $startDate, $endDate);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$attendanceByDate = [];
while ($row = mysqli_fetch_assoc($result)) {
    $date = $row['attendance_date'];
    
    // Format times
    $timeIn = $row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : null;
    $timeOut = $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : null;
    
    // Determine status
    $isWorker = strtolower($row['position']) === 'worker';
    $status = 'Absent';
    
    if ($row['time_in']) {
        if ($row['time_out']) {
            // Completed shift
            if ($isWorker) {
                $timeInObj = strtotime($row['time_in']);
                $lateThreshold = strtotime(date('Y-m-d', $timeInObj) . ' 07:15:00');
                if ($timeInObj >= $lateThreshold) {
                    $status = 'Late';
                } else {
                    $status = 'Completed';
                }
            } else {
                $status = 'Completed';
            }
        } else {
            // Still present (no timeout)
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
    }
    
    if (!isset($attendanceByDate[$date])) {
        $attendanceByDate[$date] = [];
    }
    
    $attendanceByDate[$date][] = [
        'employee_id' => $row['employee_id'],
        'name' => trim($row['first_name'] . ' ' . $row['last_name']),
        'position' => $row['position'],
        'time_in' => $timeIn,
        'time_out' => $timeOut,
        'status' => $status
    ];
}

mysqli_stmt_close($stmt);
mysqli_close($db);

// Build full month data
$daysInMonth = (int)date('t', strtotime($startDate));
$days = [];

for ($day = 1; $day <= $daysInMonth; $day++) {
    $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
    $employees = $attendanceByDate[$date] ?? [];
    
    // Calculate summary counts
    $summary = [
        'total' => count($employees),
        'present' => 0,
        'completed' => 0,
        'late' => 0,
        'absent' => 0
    ];
    
    foreach ($employees as $emp) {
        $summary[strtolower($emp['status'])]++;
    }
    
    $days[] = [
        'date' => $dateStr,
        'employees' => $employees,
        'summary' => $summary
    ];
}

respond(200, [
    'success' => true,
    'branch_name' => $branchName,
    'month' => $monthParam,
    'days' => $days
]);
?>
