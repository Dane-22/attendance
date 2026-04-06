<?php
/**
 * Mark All Notifications as Read
 * Endpoint for marking all notifications as read for current user
 */

session_start();
header('Content-Type: application/json');

// Include database connection
if (file_exists(__DIR__ . '/../conn/db_connection.php')) {
    require_once __DIR__ . '/../conn/db_connection.php';
} else {
    require_once __DIR__ . '/../db_connection.php';
}

// Check if user is logged in
if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$employeeId = $_SESSION['employee_id'];

// Check if action is mark_all_read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_all_read') {
    // Check if employee_notifications table exists
    $checkTable = @mysqli_query($db, "SHOW TABLES LIKE 'employee_notifications'");
    if (!$checkTable || mysqli_num_rows($checkTable) === 0) {
        echo json_encode(['success' => false, 'message' => 'Notifications table not found']);
        exit;
    }
    
    // Update all unread notifications for this employee
    $sql = "UPDATE employee_notifications 
            SET is_read = 1, 
                read_at = NOW() 
            WHERE employee_id = ? AND is_read = 0";
    
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare query']);
        exit;
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $employeeId);
    $result = mysqli_stmt_execute($stmt);
    $affectedRows = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'All notifications marked as read',
            'affected_rows' => $affectedRows
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update notifications']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
