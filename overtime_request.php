<?php
/**
 * Overtime Request API
 * 
 * Endpoint: POST /overtime_request.php
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

try {
    // Include database connection
    require_once __DIR__ . '/conn/db_connection.php';

    // Get input data (support both form-data and JSON)
    $input = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = $_POST;
        if (empty($input)) {
            $json = file_get_contents('php://input');
            $input = json_decode($json, true) ?? [];
        }
    }

    // Validate required fields
    $required = ['employee_id', 'employee_code', 'branch_name', 'requested_hours', 'overtime_reason'];
    $missing = [];
    foreach ($required as $field) {
        if (!isset($input[$field]) || trim($input[$field]) === '') {
            $missing[] = $field;
        }
    }

    if (!empty($missing)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields: ' . implode(', ', $missing)
        ]);
        exit;
    }

    $employee_id = intval($input['employee_id']);
    $employee_code = mysqli_real_escape_string($db, trim($input['employee_code']));
    $branch_name = mysqli_real_escape_string($db, trim($input['branch_name']));
    $requested_hours = floatval($input['requested_hours']);
    $overtime_reason = mysqli_real_escape_string($db, trim($input['overtime_reason']));

    // Validate hours (max 4 hours)
    if ($requested_hours <= 0 || $requested_hours > 4) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Requested hours must be between 0.5 and 4 hours'
        ]);
        exit;
    }

    // Get requester info (from session or input)
    $requested_by = isset($input['requested_by']) ? trim($input['requested_by']) : 'System';
    $requested_by_user_id = isset($input['requested_by_user_id']) ? intval($input['requested_by_user_id']) : null;
    $request_date = date('Y-m-d');

    // Check for duplicate request for this date
    $check_sql = "SELECT id FROM overtime_requests WHERE employee_id = ? AND request_date = ? AND status != 'rejected'";
    $check_stmt = mysqli_prepare($db, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "is", $employee_id, $request_date);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($check_result) > 0) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Duplicate overtime request for this date'
        ]);
        exit;
    }

    // Insert the overtime request
    $sql = "INSERT INTO overtime_requests 
            (employee_id, branch_name, request_date, requested_hours, overtime_reason, status, requested_by, requested_by_user_id, requested_at) 
            VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, NOW())";

    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, "issdsss", 
        $employee_id, 
        $branch_name, 
        $request_date, 
        $requested_hours, 
        $overtime_reason,
        $requested_by,
        $requested_by_user_id
    );

    if (mysqli_stmt_execute($stmt)) {
        $request_id = mysqli_insert_id($db);
        
        // Create notification for the employee
        $notif_title = "Overtime Request Submitted";
        $notif_message = "Your overtime request for {$requested_hours} hours has been submitted and is pending approval.";
        $notif_sql = "INSERT INTO employee_notifications (employee_id, overtime_request_id, notification_type, title, message, is_read, created_at) 
                       VALUES (?, ?, 'overtime_pending', ?, ?, 0, NOW())";
        $notif_stmt = mysqli_prepare($db, $notif_sql);
        mysqli_stmt_bind_param($notif_stmt, "iiss", $employee_id, $request_id, $notif_title, $notif_message);
        mysqli_stmt_execute($notif_stmt);
        
        echo json_encode([
            'success' => true,
            'message' => 'Overtime request submitted successfully',
            'request_id' => $request_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to submit overtime request: ' . mysqli_error($db)
        ]);
    }

    mysqli_close($db);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
