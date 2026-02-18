<?php
// change-password-api.php
// API endpoint for changing passwords - can be tested with Postman

session_start();
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit;
}

require_once 'conn/db_connection.php';
require_once 'functions.php';
require_once 'procurement-api.php';

// Get input data (support both JSON and form-data)
$input = [];
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (strpos($contentType, 'application/json') !== false) {
    // JSON input
    $json = file_get_contents('php://input');
    $input = json_decode($json, true) ?: [];
} else {
    // Form data
    $input = $_POST;
}

// Authentication - Check API Key
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
$validApiKey = 'qwertyuiopasdfghjklzxcvbnm'; // Same key as procurement API

if ($apiKey !== $validApiKey) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Invalid or missing X-API-Key header.']);
    exit;
}

// Get employee_code from body
$employeeCode = $input['employee_code'] ?? '';

if (empty($employeeCode)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'employee_code is required in request body']);
    exit;
}

// Lookup employee by employee_code to get the ID
$lookupQuery = "SELECT id, password_hash, employee_code FROM employees WHERE employee_code = ?";
$lookupStmt = mysqli_prepare($db, $lookupQuery);
mysqli_stmt_bind_param($lookupStmt, "s", $employeeCode);
mysqli_stmt_execute($lookupStmt);
$lookupResult = mysqli_stmt_get_result($lookupStmt);
$user = mysqli_fetch_assoc($lookupResult);
mysqli_stmt_close($lookupStmt);

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Employee not found with code: ' . $employeeCode]);
    exit;
}

$employeeId = $user['id'];
$stored_hash = $user['password_hash'];
$employee_code = $user['employee_code'];

// Get required fields
$current_password = $input['current_password'] ?? '';
$new_password = $input['new_password'] ?? '';
$confirm_password = $input['confirm_password'] ?? '';

// Validation
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All fields are required: current_password, new_password, confirm_password']);
    exit;
}

if ($new_password !== $confirm_password) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
    exit;
}

if (strlen($new_password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long']);
    exit;
}

// Verify current password (support both MD5 and bcrypt)
$password_verified = false;

if (strpos($stored_hash, '$2y$') === 0) {
    // Bcrypt
    $password_verified = password_verify($current_password, $stored_hash);
} else {
    // MD5
    $password_verified = (md5($current_password) === $stored_hash);
}

if (!$password_verified) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
    exit;
}

// Update with new bcrypt password
$new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
$updateQuery = "UPDATE employees SET password_hash = ?, updated_at = NOW() WHERE id = ?";
$updateStmt = mysqli_prepare($db, $updateQuery);
mysqli_stmt_bind_param($updateStmt, "si", $new_password_hash, $employeeId);
$updateSuccess = mysqli_stmt_execute($updateStmt);
mysqli_stmt_close($updateStmt);

if (!$updateSuccess) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update password in database']);
    exit;
}

// Sync to procurement system
$sync_result = syncPasswordToProcurement($employee_code, $new_password);

// Log activity to database
logApiActivity($db, $employeeId, 'Password Changed', "User {$employee_code} changed password via API");

// Build response
$response = [
    'success' => true,
    'message' => 'Password updated successfully',
    'data' => [
        'employee_id' => $employeeId,
        'employee_code' => $employee_code,
        'password_updated' => true,
        'procurement_sync' => [
            'success' => $sync_result['success'],
            'message' => $sync_result['message'] ?? null
        ]
    ]
];

if (!$sync_result['success']) {
    $response['message'] .= '. Note: Procurement sync failed - ' . $sync_result['message'];
}

http_response_code(200);
echo json_encode($response);
mysqli_close($db);
exit;
