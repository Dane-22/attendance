<?php
// employee/api/get_vapid_key.php - Get VAPID public key for push subscription
require_once __DIR__ . '/../../conn/db_connection.php';
header('Content-Type: application/json');

// Start session to check authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is Super Admin
if (empty($_SESSION['logged_in']) || $_SESSION['position'] !== 'Super Admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Super Admin access required.'
    ]);
    exit;
}

// Load VAPID public key from environment
$vapidPublicKey = getenv('VAPID_PUBLIC_KEY');

if (!$vapidPublicKey) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'VAPID public key not configured'
    ]);
    exit;
}

// Return the public key
echo json_encode([
    'success' => true,
    'publicKey' => $vapidPublicKey
]);
?>
