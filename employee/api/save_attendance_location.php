<?php
/**
 * Save Location Data with Attendance Record
 * API Endpoint: employee/api/save_attendance_location.php
 * 
 * Saves GPS coordinates with clock-in/out records
 * Logs to location_logs for audit trail
 */

require_once __DIR__ . '/../../conn/db_connection.php';
require_once __DIR__ . '/../../functions.php';

header('Content-Type: application/json');

// Get POST parameters
$attendance_id = isset($_POST['attendance_id']) ? intval($_POST['attendance_id']) : null;
$employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : null;
$lat = isset($_POST['latitude']) ? floatval($_POST['latitude']) : null;
$lng = isset($_POST['longitude']) ? floatval($_POST['longitude']) : null;
$accuracy = isset($_POST['accuracy']) ? floatval($_POST['accuracy']) : null;
$action = isset($_POST['action']) ? $_POST['action'] : 'clock_in'; // clock_in, clock_out, qr_scan
$device_info = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : null;
$is_validated = isset($_POST['is_validated']) ? intval($_POST['is_validated']) : 0;
$validation_failure_reason = isset($_POST['validation_failure_reason']) ? $_POST['validation_failure_reason'] : null;

// Validate required parameters
if (!$attendance_id || !$employee_id || !$lat || !$lng) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters: attendance_id, employee_id, latitude, longitude'
    ]);
    exit;
}

// Validate action type
$valid_actions = ['clock_in', 'clock_out', 'qr_scan', 'manual_override'];
if (!in_array($action, $valid_actions)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action type'
    ]);
    exit;
}

try {
    // Start transaction
    mysqli_begin_transaction($db);

    // Update attendance record with location
    if ($action === 'clock_in' || $action === 'qr_scan') {
        $sql = "UPDATE attendance 
                SET clock_in_lat = ?, 
                    clock_in_lng = ?, 
                    location_accuracy = ?,
                    location_verified = ?
                WHERE id = ?";
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'dddii', $lat, $lng, $accuracy, $is_validated, $attendance_id);
    } else {
        // clock_out or manual_override for clock-out
        $sql = "UPDATE attendance 
                SET clock_out_lat = ?, 
                    clock_out_lng = ?, 
                    location_accuracy = ?,
                    location_verified = ?
                WHERE id = ?";
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'dddii', $lat, $lng, $accuracy, $is_validated, $attendance_id);
    }

    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$success) {
        throw new Exception('Failed to update attendance record: ' . mysqli_error($db));
    }

    // Log to location_logs for audit trail
    $distance_from_branch = isset($_POST['distance_from_branch']) ? intval($_POST['distance_from_branch']) : null;
    
    $log_sql = "INSERT INTO location_logs 
                (employee_id, attendance_id, action_type, latitude, longitude, 
                 accuracy_meters, branch_id, distance_from_branch_meters, 
                 device_info, ip_address, is_validated, validation_failure_reason) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $log_stmt = mysqli_prepare($db, $log_sql);
    mysqli_stmt_bind_param($log_stmt, 'iissdiiissis', 
        $employee_id, 
        $attendance_id, 
        $action, 
        $lat, 
        $lng, 
        $accuracy, 
        $branch_id, 
        $distance_from_branch, 
        $device_info, 
        $ip_address, 
        $is_validated, 
        $validation_failure_reason
    );
    
    $log_success = mysqli_stmt_execute($log_stmt);
    mysqli_stmt_close($log_stmt);

    if (!$log_success) {
        throw new Exception('Failed to log location data: ' . mysqli_error($db));
    }

    // Commit transaction
    mysqli_commit($db);

    // Log activity
    logActivity($db, $employee_id, 'Location Saved', 
        "Location saved for {$action} - Attendance #{$attendance_id} ({$lat}, {$lng})");

    echo json_encode([
        'success' => true,
        'message' => 'Location saved successfully',
        'data' => [
            'attendance_id' => $attendance_id,
            'action' => $action,
            'latitude' => $lat,
            'longitude' => $lng,
            'accuracy' => $accuracy,
            'is_validated' => $is_validated
        ]
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($db);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

mysqli_close($db);
?>
