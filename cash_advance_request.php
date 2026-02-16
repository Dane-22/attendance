<?php
// cash_advance_request.php - Submit new cash advance request
require_once __DIR__ . '/conn/db_connection.php';
require_once __DIR__ . '/functions.php';

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

// Get POST data
$employee_id = intval($_POST['employee_id'] ?? 0);
$employee_code = sanitizeInput($_POST['employee_code'] ?? '');
$amount = floatval($_POST['amount'] ?? 0);
$particular = sanitizeInput($_POST['particular'] ?? 'Cash Advance');
$reason = sanitizeInput($_POST['reason'] ?? '');

// Validate required fields
if ($employee_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid employee ID']);
    exit;
}

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Amount must be greater than 0']);
    exit;
}

if (empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Reason is required']);
    exit;
}

// Verify employee exists
$empQuery = "SELECT id, first_name, last_name, employee_code, monthly_salary FROM employees WHERE id = ?";
$empStmt = $conn->prepare($empQuery);
$empStmt->bind_param("i", $employee_id);
$empStmt->execute();
$empResult = $empStmt->get_result();

if ($empResult->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Employee not found']);
    exit;
}

$employee = $empResult->fetch_assoc();

// Check for existing pending requests
$checkQuery = "SELECT COUNT(*) as pending_count FROM cash_advances 
               WHERE employee_id = ? AND status = 'pending'";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bind_param("i", $employee_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$pendingCount = $checkResult->fetch_assoc()['pending_count'];

if ($pendingCount > 0) {
    echo json_encode(['success' => false, 'message' => 'You already have a pending cash advance request']);
    exit;
}

// Check maximum amount (50% of monthly salary or 10000 default)
$maxAmount = 10000; // Default max
if (!empty($employee['monthly_salary']) && $employee['monthly_salary'] > 0) {
    $maxAmount = $employee['monthly_salary'] * 0.5;
}

if ($amount > $maxAmount) {
    echo json_encode([
        'success' => false, 
        'message' => 'Requested amount exceeds maximum allowed (₱' . number_format($maxAmount, 2) . ')'
    ]);
    exit;
}

// Check outstanding balance
$balanceQuery = "SELECT 
    SUM(CASE WHEN particular = 'Payment' THEN -amount ELSE amount END) as balance
    FROM cash_advances 
    WHERE employee_id = ? AND status IN ('approved', 'paid')";
$balanceStmt = $conn->prepare($balanceQuery);
$balanceStmt->bind_param("i", $employee_id);
$balanceStmt->execute();
$balanceResult = $balanceStmt->get_result();
$outstandingBalance = floatval($balanceResult->fetch_assoc()['balance'] ?? 0);

if ($outstandingBalance > 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'You have an outstanding balance of ₱' . number_format($outstandingBalance, 2) . '. Please settle before requesting new advance.'
    ]);
    exit;
}

// Insert new cash advance request
$insertQuery = "INSERT INTO cash_advances (employee_id, amount, particular, reason, status, request_date) 
                VALUES (?, ?, ?, ?, 'pending', NOW())";
$insertStmt = $conn->prepare($insertQuery);
$insertStmt->bind_param("idss", $employee_id, $amount, $particular, $reason);

if ($insertStmt->execute()) {
    $request_id = $conn->insert_id;
    
    // Create notification for admin (if notifications table exists)
    try {
        $notifQuery = "INSERT INTO notifications (type, title, message, employee_id, created_at) 
                       VALUES ('cash_advance', 'New Cash Advance Request', 
                       'New request from {$employee['first_name']} {$employee['last_name']} - ₱' . number_format($amount, 2), 
                       ?, NOW())";
        $notifStmt = $conn->prepare($notifQuery);
        $notifStmt->bind_param("i", $employee_id);
        $notifStmt->execute();
    } catch (Exception $e) {
        // Notifications table might not exist, continue silently
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Cash advance request submitted successfully',
        'request_id' => $request_id
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to submit request: ' . $conn->error
    ]);
}

// Helper function to sanitize input
function sanitizeInput($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}
?>
