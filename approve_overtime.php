<?php
/**
 * Approve/Reject Overtime API
 * 
 * Endpoint: POST /approve_overtime.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

try {
    // Include database connection
    require_once __DIR__ . '/conn/db_connection.php';

    // Get input data
    $input = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = $_POST;
        if (empty($input)) {
            $json = file_get_contents('php://input');
            $input = json_decode($json, true) ?? [];
        }
    }

    // Validate required fields
    if (empty($input['request_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required field: request_id'
        ]);
        exit;
    }

    if (empty($input['action']) || !in_array($input['action'], ['approve', 'reject'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action. Must be "approve" or "reject"'
        ]);
        exit;
    }

    $request_id = intval($input['request_id']);
    $action = $input['action'];
    $approved_by = isset($input['approved_by']) ? mysqli_real_escape_string($db, trim($input['approved_by'])) : 'Administrator';
    $rejection_reason = isset($input['rejection_reason']) ? mysqli_real_escape_string($db, trim($input['rejection_reason'])) : null;

    // Validate rejection reason for reject action
    if ($action === 'reject' && empty($rejection_reason)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Rejection reason is required when rejecting a request'
        ]);
        exit;
    }

    // Get the overtime request details
    $get_sql = "SELECT r.*, e.first_name, e.last_name, e.employee_code 
                FROM overtime_requests r 
                LEFT JOIN employees e ON r.employee_id = e.id 
                WHERE r.id = ?";
    $get_stmt = mysqli_prepare($db, $get_sql);
    mysqli_stmt_bind_param($get_stmt, "i", $request_id);
    mysqli_stmt_execute($get_stmt);
    $result = mysqli_stmt_get_result($get_stmt);

    if (mysqli_num_rows($result) === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Overtime request not found'
        ]);
        exit;
    }

    $request = mysqli_fetch_assoc($result);

    // Check if request is already processed
    if ($request['status'] !== 'pending') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'This request has already been ' . $request['status']
        ]);
        exit;
    }

    // Start transaction
    mysqli_begin_transaction($db);

    try {
        if ($action === 'approve') {
            // Update overtime request status
            $update_sql = "UPDATE overtime_requests 
                           SET status = 'approved', 
                               approved_by = ?, 
                               approved_at = NOW() 
                           WHERE id = ?";
            $update_stmt = mysqli_prepare($db, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "si", $approved_by, $request_id);
            
            if (!mysqli_stmt_execute($update_stmt)) {
                throw new Exception('Failed to update overtime request: ' . mysqli_error($db));
            }
            
            // Update attendance record with OT hours
            $attendance_sql = "UPDATE attendance 
                              SET is_overtime_running = 1, 
                                  total_ot_hrs = ? 
                              WHERE employee_id = ? 
                              AND attendance_date = ?
                              AND (is_overtime_running = 0 OR total_ot_hrs = '0')";
            $attendance_stmt = mysqli_prepare($db, $attendance_sql);
            $total_ot_hrs = strval($request['requested_hours']);
            mysqli_stmt_bind_param($attendance_stmt, "sis", $total_ot_hrs, $request['employee_id'], $request['request_date']);
            mysqli_stmt_execute($attendance_stmt);
            
            // Create approval notification
            $notif_title = "Overtime Request Approved";
            $notif_message = "Your overtime request for {$request['requested_hours']} hours has been approved.";
            $notif_sql = "INSERT INTO employee_notifications (employee_id, overtime_request_id, notification_type, title, message, is_read, created_at) 
                          VALUES (?, ?, 'overtime_approved', ?, ?, 0, NOW())";
            $notif_stmt = mysqli_prepare($db, $notif_sql);
            mysqli_stmt_bind_param($notif_stmt, "iiss", $request['employee_id'], $request_id, $notif_title, $notif_message);
            mysqli_stmt_execute($notif_stmt);
            
        } else { // reject
            // Update overtime request status
            $update_sql = "UPDATE overtime_requests 
                           SET status = 'rejected', 
                               approved_by = ?, 
                               approved_at = NOW(),
                               rejection_reason = ? 
                           WHERE id = ?";
            $update_stmt = mysqli_prepare($db, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "ssi", $approved_by, $rejection_reason, $request_id);
            
            if (!mysqli_stmt_execute($update_stmt)) {
                throw new Exception('Failed to update overtime request: ' . mysqli_error($db));
            }
            
            // Create rejection notification
            $notif_title = "Overtime Request Rejected";
            $notif_message = "Your overtime request for {$request['requested_hours']} hours has been rejected. Reason: {$rejection_reason}";
            $notif_sql = "INSERT INTO employee_notifications (employee_id, overtime_request_id, notification_type, title, message, is_read, created_at) 
                          VALUES (?, ?, 'overtime_rejected', ?, ?, 0, NOW())";
            $notif_stmt = mysqli_prepare($db, $notif_sql);
            mysqli_stmt_bind_param($notif_stmt, "iiss", $request['employee_id'], $request_id, $notif_title, $notif_message);
            mysqli_stmt_execute($notif_stmt);
        }
        
        // Commit transaction
        mysqli_commit($db);
        
        $message = $action === 'approve' 
            ? 'Overtime request approved successfully' 
            : 'Overtime request rejected successfully';
        
        echo json_encode([
            'success' => true,
            'message' => $message
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($db);
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
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
