<?php
// functions.php - Utility functions for the Attendance System

// Suppress PHP warnings/notices to prevent HTML in JSON responses
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

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

/**
 * Logs an activity to the activity_logs table (API version with explicit user_id)
 * @param mysqli $db Database connection
 * @param int|null $user_id The user ID performing the action
 * @param string $action The action performed (e.g., 'API Login', 'Time In')
 * @param string $details Additional details about the action
 * @return bool True on success, false on failure
 */
function logApiActivity($db, $user_id, $action, $details) {
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

/**
 * Sends a push notification to a specific user
 * Uses web-push-php library if available, otherwise returns error
 * @param mysqli $db Database connection
 * @param int $userId The user ID to send notification to
 * @param string $title The notification title
 * @param string $message The notification body/message
 * @param string|null $url Optional URL to open when notification is clicked
 * @return array Result with success status and details
 */
function sendPushNotification($db, $userId, $title, $message, $url = null) {
    $result = [
        'success' => false,
        'sent' => 0,
        'failed' => 0,
        'errors' => []
    ];

    // Validate inputs
    if (!$db || !$userId || empty($title) || empty($message)) {
        $result['errors'][] = 'Invalid parameters provided';
        return $result;
    }

    // Check if web-push-php library is available
    $vendorAutoload = __DIR__ . '/vendor/autoload.php';
    if (!file_exists($vendorAutoload)) {
        $result['errors'][] = 'Web Push library not installed. Run: php install_webpush.php';
        return $result;
    }

    require_once $vendorAutoload;

    // Load VAPID keys from environment
    $vapidPublicKey = getenv('VAPID_PUBLIC_KEY');
    $vapidPrivateKey = getenv('VAPID_PRIVATE_KEY');
    $vapidSubject = getenv('VAPID_SUBJECT') ?: 'mailto:admin@jajr.com';

    if (!$vapidPublicKey || !$vapidPrivateKey) {
        $result['errors'][] = 'VAPID keys not configured. Set VAPID_PUBLIC_KEY and VAPID_PRIVATE_KEY in .env file.';
        return $result;
    }

    // Get all push subscriptions for this user
    $sql = "SELECT endpoint, p256dh, auth FROM push_subscriptions WHERE user_id = ?";
    $stmt = mysqli_prepare($db, $sql);
    
    if (!$stmt) {
        $result['errors'][] = 'Database error: Failed to prepare subscription query';
        return $result;
    }

    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $queryResult = mysqli_stmt_get_result($stmt);
    
    $subscriptions = [];
    while ($row = mysqli_fetch_assoc($queryResult)) {
        $subscriptions[] = $row;
    }
    mysqli_stmt_close($stmt);

    if (empty($subscriptions)) {
        $result['errors'][] = 'No push subscriptions found for user';
        return $result;
    }

    try {
        // Create WebPush client
        $auth = [
            'VAPID' => [
                'subject' => $vapidSubject,
                'publicKey' => $vapidPublicKey,
                'privateKey' => $vapidPrivateKey,
            ],
        ];
        
        $webPush = new \Minishlink\WebPush\WebPush($auth);
        
        // Prepare notification payload
        $payload = json_encode([
            'title' => $title,
            'body' => $message,
            'icon' => '/uploads/profile_images/profile_0_1769993901.png',
            'badge' => '/uploads/profile_images/profile_0_1769993901.png',
            'tag' => 'jajr-notification-' . time(),
            'url' => $url ?: '/employee/dashboard.php',
            'notificationId' => time(),
            'requireInteraction' => true
        ]);
        
        // Send to each subscription
        foreach ($subscriptions as $subscription) {
            $subscriptionObj = \Minishlink\WebPush\Subscription::create([
                'endpoint' => $subscription['endpoint'],
                'publicKey' => $subscription['p256dh'],
                'authToken' => $subscription['auth'],
            ]);
            
            $webPush->queueNotification($subscriptionObj, $payload);
        }
        
        // Flush notifications
        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getEndpoint();
            
            if ($report->isSuccess()) {
                $result['sent']++;
            } else {
                $result['failed']++;
                $result['errors'][] = $report->getReason();
                
                // If subscription expired, remove it
                if ($report->isSubscriptionExpired()) {
                    $deleteSql = "DELETE FROM push_subscriptions WHERE endpoint = ?";
                    $deleteStmt = mysqli_prepare($db, $deleteSql);
                    if ($deleteStmt) {
                        mysqli_stmt_bind_param($deleteStmt, 's', $endpoint);
                        mysqli_stmt_execute($deleteStmt);
                        mysqli_stmt_close($deleteStmt);
                    }
                }
            }
        }
        
        $result['success'] = ($result['sent'] > 0);
        
    } catch (Exception $e) {
        $result['errors'][] = 'WebPush Error: ' . $e->getMessage();
    }

    return $result;
}

/**
 * Calculate days worked and gross pay based on actual hours (Option E - Hybrid)
 * For full days (>=8 hours): full day pay
 * For partial days (<8 hours): hourly rate × actual hours
 * @param float $actual_hours Hours actually worked
 * @param float $daily_rate Employee's daily rate
 * @return array ['days_worked' => float, 'gross_pay' => float, 'hourly_rate' => float]
 */
function calculateDaysAndPay($actual_hours, $daily_rate) {
    $standard_hours = 8.0;
    $hourly_rate = $daily_rate / $standard_hours;

    if ($actual_hours >= $standard_hours) {
        // Full day: pay full daily rate
        $days_worked = 1.0;
        $gross_pay = $daily_rate;
    } else {
        // Partial day: pay hourly rate × actual hours
        $days_worked = $actual_hours / $standard_hours;
        $gross_pay = $hourly_rate * $actual_hours;
    }

    return [
        'days_worked' => round($days_worked, 2),
        'gross_pay' => round($gross_pay, 2),
        'hourly_rate' => round($hourly_rate, 2)
    ];
}