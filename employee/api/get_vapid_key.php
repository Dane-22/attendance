<?php
// employee/api/get_vapid_key.php - Get VAPID public key for push subscription
require_once __DIR__ . '/../../conn/db_connection.php';
header('Content-Type: application/json');

// Start session to check authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in (any authenticated user can get VAPID key)
if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Please log in.'
    ]);
    exit;
}

// Load VAPID public key from environment or .env file
$vapidPublicKey = getenv('VAPID_PUBLIC_KEY');

// If not in environment, try to load from .env file
if (!$vapidPublicKey) {
    $envPath = __DIR__ . '/../../.env';
    if (file_exists($envPath)) {
        $envContents = file_get_contents($envPath);
        if (preg_match('/VAPID_PUBLIC_KEY=([^\n]+)/', $envContents, $matches)) {
            $vapidPublicKey = trim($matches[1]);
        }
    }
}

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
