<?php
// functions.php - Utility functions for the Attendance System

/**
 * Logs an activity to the activity_logs table
 * @param mysqli $db Database connection
 * @param string $action The action performed (e.g., 'Logged In', 'Marked Attendance')
 * @param string $details Additional details about the action
 * @return bool True on success, false on failure
 */
function logActivity($db, $action, $details) {
    // Get user_id from session
    $user_id = $_SESSION['employee_id'] ?? null;

    // Get IP address
    $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

    // Prepare the insert statement
    $sql = "INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'isss', $user_id, $action, $details, $ip_address);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $result;
}
?>