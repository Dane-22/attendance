<?php
/**
 * TEST VERSION - Immediate Push Notification Test
 * This sends notifications immediately regardless of time
 * Run this to verify the push notification system is working
 */

// Suppress PHP warnings
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';

echo "========================================\n";
echo "PUSH NOTIFICATION TEST\n";
echo "========================================\n\n";

// Get all employees by position
$testGroups = [
    'Engineer' => [
        'title' => 'Time In Reminder',
        'message' => 'Good morning! Please don\'t forget to time in for your shift. (TEST MESSAGE)',
        'url' => '/employee/attendance.php'
    ],
    'Admin' => [
        'title' => 'Time In Reminder',
        'message' => 'Good morning! Please don\'t forget to time in for your shift. (TEST MESSAGE)',
        'url' => '/employee/attendance.php'
    ],
    'Developer' => [
        'title' => 'Time In Reminder',
        'message' => 'Good morning! Please don\'t forget to time in for your shift. (TEST MESSAGE)',
        'url' => '/employee/attendance.php'
    ]
];

$totalSent = 0;
$totalFailed = 0;

foreach ($testGroups as $position => $config) {
    echo "Testing notifications for {$position}...\n";
    
    // Get employees with this position
    $sql = "SELECT id, first_name, last_name, position FROM employees 
            WHERE position = ? 
            AND status = 'Active'";
    
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 's', $position);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $count = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $count++;
        echo "  - Sending to {$row['first_name']} {$row['last_name']}... ";
        
        $result2 = sendPushNotification(
            $db,
            $row['id'],
            $config['title'],
            $config['message'],
            $config['url']
        );
        
        if ($result2['success']) {
            echo "✓ Sent ({$result2['sent']} notifications)\n";
            $totalSent += $result2['sent'];
        } else {
            $error = isset($result2['errors'][0]) ? $result2['errors'][0] : 'Unknown error';
            echo "✗ Failed: {$error}\n";
            $totalFailed++;
        }
    }
    mysqli_stmt_close($stmt);
    
    if ($count === 0) {
        echo "  (No employees found with this position)\n";
    }
    echo "\n";
}

echo "========================================\n";
echo "TEST COMPLETE\n";
echo "========================================\n";
echo "Total Notifications Sent: {$totalSent}\n";
echo "Total Failed: {$totalFailed}\n\n";

if ($totalSent > 0) {
    echo "✓ Push notifications are working!\n";
    echo "Check your browser/device for the notifications.\n";
} else {
    echo "✗ No notifications were sent.\n";
    echo "Possible issues:\n";
    echo "  - No employees found in database\n";
    echo "  - No push subscriptions (employees haven't enabled notifications)\n";
    echo "  - VAPID keys not configured correctly\n";
    echo "  - Web Push library not installed\n";
}

echo "\n";
