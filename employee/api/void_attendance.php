<?php
/**
 * Void Attendance API
 * Allows admins to void completed attendance records
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../conn/db_connection.php';
require_once __DIR__ . '/../../functions.php';

session_start();

header('Content-Type: application/json');

// Rate Limiting Configuration
$RATE_LIMIT_MAX_REQUESTS = 30;
$RATE_LIMIT_WINDOW = 60;

// Initialize rate limiting in session
if (!isset($_SESSION['void_attendance_rate_limit'])) {
    $_SESSION['void_attendance_rate_limit'] = [
        'requests' => [],
        'blocked_until' => null
    ];
}

$now = time();

// Check if user is currently blocked
if ($_SESSION['void_attendance_rate_limit']['blocked_until'] && $now < $_SESSION['void_attendance_rate_limit']['blocked_until']) {
    $retryAfter = $_SESSION['void_attendance_rate_limit']['blocked_until'] - $now;
    http_response_code(429);
    echo json_encode(['error' => 'Too many requests. Please try again in ' . $retryAfter . ' seconds.']);
    exit;
}

// Clean old requests outside the window
$_SESSION['void_attendance_rate_limit']['requests'] = array_filter(
    $_SESSION['void_attendance_rate_limit']['requests'],
    function($timestamp) use ($now, $RATE_LIMIT_WINDOW) {
        return ($now - $timestamp) < $RATE_LIMIT_WINDOW;
    }
);

// Check if limit exceeded
if (count($_SESSION['void_attendance_rate_limit']['requests']) >= $RATE_LIMIT_MAX_REQUESTS) {
    $_SESSION['void_attendance_rate_limit']['blocked_until'] = $now + $RATE_LIMIT_WINDOW;
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded. Please try again in ' . $RATE_LIMIT_WINDOW . ' seconds.']);
    exit;
}

// Record this request
$_SESSION['void_attendance_rate_limit']['requests'][] = $now;

// Check authentication and permissions
if (empty($_SESSION['logged_in']) || !in_array($_SESSION['position'], ['Admin', 'Super Admin', 'Developer'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized. Admin access required.']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

// Validate required fields
$attendance_id = intval($input['attendance_id'] ?? 0);
$void_reason = trim($input['void_reason'] ?? '');

if ($attendance_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid attendance ID']);
    exit;
}

if (empty($void_reason)) {
    http_response_code(400);
    echo json_encode(['error' => 'Void reason is required']);
    exit;
}

if (strlen($void_reason) > 500) {
    http_response_code(400);
    echo json_encode(['error' => 'Void reason must be less than 500 characters']);
    exit;
}

// Check if attendance record exists and is not already voided
$check_sql = "SELECT a.*, e.first_name, e.last_name, e.employee_code 
              FROM attendance a 
              JOIN employees e ON a.employee_id = e.id 
              WHERE a.id = ?";
$check_stmt = mysqli_prepare($db, $check_sql);

if (!$check_stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . mysqli_error($db)]);
    exit;
}

mysqli_stmt_bind_param($check_stmt, 'i', $attendance_id);
mysqli_stmt_execute($check_stmt);
$result = mysqli_stmt_get_result($check_stmt);
$record = mysqli_fetch_assoc($result);
mysqli_stmt_close($check_stmt);

if (!$record) {
    http_response_code(404);
    echo json_encode(['error' => 'Attendance record not found']);
    exit;
}

// Check if already voided
if (!empty($record['is_voided'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Attendance record is already voided']);
    exit;
}

// Only allow voiding completed records (with both time_in and time_out)
if (empty($record['time_in']) || empty($record['time_out'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Only completed attendance records can be voided']);
    exit;
}

// Void the attendance record
$void_sql = "UPDATE attendance 
             SET is_voided = 1, 
                 void_reason = ?, 
                 voided_by = ?, 
                 voided_at = NOW() 
             WHERE id = ?";

$void_stmt = mysqli_prepare($db, $void_sql);

if (!$void_stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . mysqli_error($db)]);
    exit;
}

$admin_id = $_SESSION['user_id'] ?? 0;
mysqli_stmt_bind_param($void_stmt, 'sii', $void_reason, $admin_id, $attendance_id);

if (!mysqli_stmt_execute($void_stmt)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to void record: ' . mysqli_stmt_error($void_stmt)]);
    mysqli_stmt_close($void_stmt);
    exit;
}

$rows_affected = mysqli_stmt_affected_rows($void_stmt);
mysqli_stmt_close($void_stmt);

if ($rows_affected === 0) {
    http_response_code(500);
    echo json_encode(['error' => 'No record was updated']);
    exit;
}

// Log the activity
$employee_name = $record['first_name'] . ' ' . $record['last_name'];
$action = "Attendance Voided";
$details = "Admin voided attendance #{$attendance_id} for employee {$employee_name} ({$record['employee_code']}) on {$record['attendance_date']}. Reason: {$void_reason}";
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

$log_sql = "INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) 
            VALUES (?, ?, ?, ?, NOW())";
$log_stmt = mysqli_prepare($db, $log_sql);

if ($log_stmt) {
    mysqli_stmt_bind_param($log_stmt, 'isss', $admin_id, $action, $details, $ip_address);
    mysqli_stmt_execute($log_stmt);
    mysqli_stmt_close($log_stmt);
}

// Return success response
echo json_encode([
    'success' => true,
    'message' => 'Attendance record voided successfully',
    'data' => [
        'attendance_id' => $attendance_id,
        'employee_name' => $employee_name,
        'attendance_date' => $record['attendance_date'],
        'void_reason' => $void_reason,
        'voided_at' => date('Y-m-d H:i:s')
    ]
]);
