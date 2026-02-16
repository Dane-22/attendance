<?php
// approve_cash_advance.php - Admin approval/rejection of cash advance requests
require_once __DIR__ . '/conn/db_connection.php';
require_once __DIR__ . '/functions.php';
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST method is allowed']);
    exit;
}

// Check if user is logged in and is admin
if (empty($_SESSION['logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$isAdmin = in_array($_SESSION['position'] ?? '', ['Admin', 'Super Admin']);
if (!$isAdmin) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Admin access required']);
    exit;
}

// Get POST data
$request_id = intval($_POST['request_id'] ?? 0);
$action = strtolower($_POST['action'] ?? '');
$rejection_reason = sanitizeInput($_POST['rejection_reason'] ?? '');
$approved_by = sanitizeInput($_POST['approved_by'] ?? ($_SESSION['username'] ?? 'Admin'));

if ($request_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
    exit;
}

if (!in_array($action, ['approve', 'reject', 'pay'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid action. Use approve, reject, or pay']);
    exit;
}

if ($action === 'reject' && empty($rejection_reason)) {
    echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
    exit;
}

// Get request details
$reqQuery = "SELECT ca.*, e.first_name, e.last_name, e.email 
             FROM cash_advances ca
             JOIN employees e ON ca.employee_id = e.id
             WHERE ca.id = ? AND ca.status = 'pending'";
$reqStmt = mysqli_prepare($db, $reqQuery);
mysqli_stmt_bind_param($reqStmt, 'i', $request_id);
mysqli_stmt_execute($reqStmt);
$request = mysqli_fetch_assoc(mysqli_stmt_get_result($reqStmt));

if (!$request) {
    echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
    exit;
}

// Update request status
if ($action === 'approve') {
    $updateQuery = "UPDATE cash_advances 
                   SET status = 'approved', approved_date = NOW(), approved_by = ?
                   WHERE id = ?";
    $updateStmt = mysqli_prepare($db, $updateQuery);
    mysqli_stmt_bind_param($updateStmt, 'si', $approved_by, $request_id);
    
    if (mysqli_stmt_execute($updateStmt)) {
        // Create notification for employee
        try {
            $notifQuery = "INSERT INTO notifications (type, title, message, employee_id, created_at) 
                          VALUES ('cash_advance', 'Cash Advance Approved', 
                          'Your request for ₱" . number_format($request['amount'], 2) . " has been approved.', 
                          ?, NOW())";
            $notifStmt = mysqli_prepare($db, $notifQuery);
            mysqli_stmt_bind_param($notifStmt, 'i', $request['employee_id']);
            mysqli_stmt_execute($notifStmt);
        } catch (Exception $e) {
            // Notifications table might not exist
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Cash advance request approved successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to approve request']);
    }
} 
elseif ($action === 'reject') {
    $updateQuery = "UPDATE cash_advances 
                   SET status = 'rejected', approved_date = NOW(), approved_by = ?, reason = CONCAT(reason, ' [Rejected: ', ?, ']')
                   WHERE id = ?";
    $updateStmt = mysqli_prepare($db, $updateQuery);
    mysqli_stmt_bind_param($updateStmt, 'ssi', $approved_by, $rejection_reason, $request_id);
    
    if (mysqli_stmt_execute($updateStmt)) {
        // Create notification for employee
        try {
            $notifQuery = "INSERT INTO notifications (type, title, message, employee_id, created_at) 
                          VALUES ('cash_advance', 'Cash Advance Rejected', 
                          'Your request for ₱" . number_format($request['amount'], 2) . " was rejected. Reason: $rejection_reason', 
                          ?, NOW())";
            $notifStmt = mysqli_prepare($db, $notifQuery);
            mysqli_stmt_bind_param($notifStmt, 'i', $request['employee_id']);
            mysqli_stmt_execute($notifStmt);
        } catch (Exception $e) {
            // Notifications table might not exist
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Cash advance request rejected'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reject request']);
    }
}
elseif ($action === 'pay') {
    $updateQuery = "UPDATE cash_advances 
                   SET status = 'paid', paid_date = NOW()
                   WHERE id = ? AND status = 'approved'";
    $updateStmt = mysqli_prepare($db, $updateQuery);
    mysqli_stmt_bind_param($updateStmt, 'i', $request_id);
    
    if (mysqli_stmt_execute($updateStmt) && mysqli_affected_rows($db) > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Cash advance marked as paid'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Request must be approved before paying']);
    }
}

// Helper function to sanitize input
function sanitizeInput($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}
?>
