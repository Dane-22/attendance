<?php
// employee/api/save_push_subscription.php - Save Web Push subscription for Super Admin
require_once __DIR__ . '/../../conn/db_connection.php';
header('Content-Type: application/json');

// Start session to check authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DEBUG: Log session info
error_log("DEBUG save_push_subscription: Session status: " . session_status());
error_log("DEBUG save_push_subscription: logged_in=" . ($_SESSION['logged_in'] ?? 'not set'));
error_log("DEBUG save_push_subscription: position=" . ($_SESSION['position'] ?? 'not set'));
error_log("DEBUG save_push_subscription: employee_id=" . ($_SESSION['employee_id'] ?? 'not set'));
error_log("DEBUG save_push_subscription: REQUEST_HEADERS=" . json_encode(getallheaders()));

// Check if user is logged in and is any active employee (Admin, Super Admin, Engineer, Developer, or Worker)
$allowedPositions = ['Admin', 'Super Admin', 'Engineer', 'Developer', 'Employee', 'Worker'];
if (empty($_SESSION['logged_in']) || !in_array($_SESSION['position'], $allowedPositions)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Please log in to enable notifications.',
        'debug' => [
            'logged_in' => $_SESSION['logged_in'] ?? null,
            'position' => $_SESSION['position'] ?? null
        ]
    ]);
    exit;
}

// Get user ID from session
$userId = $_SESSION['employee_id'] ?? null;
if (!$userId) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'User ID not found in session.'
    ]);
    exit;
}

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate required fields
if (!$data || empty($data['endpoint']) || empty($data['keys']['p256dh']) || empty($data['keys']['auth'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid subscription data. Required: endpoint, keys.p256dh, keys.auth'
    ]);
    exit;
}

$endpoint = $data['endpoint'];
$p256dh = $data['keys']['p256dh'];
$auth = $data['keys']['auth'];

// Check if this subscription already exists for this user
$checkSql = "SELECT id FROM push_subscriptions WHERE user_id = ? AND endpoint = ?";
$checkStmt = mysqli_prepare($db, $checkSql);

if (!$checkStmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: Failed to prepare check statement'
    ]);
    exit;
}

mysqli_stmt_bind_param($checkStmt, 'is', $userId, $endpoint);
mysqli_stmt_execute($checkStmt);
$checkResult = mysqli_stmt_get_result($checkStmt);
$existingSub = mysqli_fetch_assoc($checkResult);
mysqli_stmt_close($checkStmt);

if ($existingSub) {
    // Update existing subscription
    $updateSql = "UPDATE push_subscriptions 
                  SET p256dh = ?, auth = ?, updated_at = NOW() 
                  WHERE id = ?";
    $updateStmt = mysqli_prepare($db, $updateSql);
    
    if (!$updateStmt) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: Failed to prepare update statement'
        ]);
        exit;
    }
    
    mysqli_stmt_bind_param($updateStmt, 'ssi', $p256dh, $auth, $existingSub['id']);
    $result = mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Push subscription updated successfully.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update push subscription: ' . mysqli_error($db)
        ]);
    }
} else {
    // Insert new subscription
    $insertSql = "INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth, created_at, updated_at) 
                   VALUES (?, ?, ?, ?, NOW(), NOW())";
    $insertStmt = mysqli_prepare($db, $insertSql);
    
    if (!$insertStmt) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: Failed to prepare insert statement'
        ]);
        exit;
    }
    
    mysqli_stmt_bind_param($insertStmt, 'isss', $userId, $endpoint, $p256dh, $auth);
    $result = mysqli_stmt_execute($insertStmt);
    mysqli_stmt_close($insertStmt);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Push subscription saved successfully.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save push subscription: ' . mysqli_error($db)
        ]);
    }
}
?>
