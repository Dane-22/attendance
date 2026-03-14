<?php
// Debug version with error reporting enabled
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "Starting test...\n";

try {
    echo "Loading db_connection.php...\n";
    require_once __DIR__ . '/conn/db_connection.php';
    echo "OK\n";
    
    echo "Loading functions.php...\n";
    require_once __DIR__ . '/functions.php';
    echo "OK\n";
    
    echo "Checking vendor autoload...\n";
    $vendorPath = __DIR__ . '/vendor/autoload.php';
    if (file_exists($vendorPath)) {
        echo "OK - vendor exists at {$vendorPath}\n";
    } else {
        echo "ERROR - vendor not found at {$vendorPath}\n";
    }
    
    echo "\n========================================\n";
    echo "PUSH NOTIFICATION TEST\n";
    echo "========================================\n\n";
    
    $positions = ['Engineer', 'Admin', 'Developer'];
    $totalSent = 0;
    
    foreach ($positions as $position) {
        echo "Testing {$position}...\n";
        
        $sql = "SELECT id, first_name, last_name FROM employees WHERE position = ? AND status = 'Active'";
        $stmt = mysqli_prepare($db, $sql);
        
        if (!$stmt) {
            echo "  ERROR: Failed to prepare statement - " . mysqli_error($db) . "\n";
            continue;
        }
        
        mysqli_stmt_bind_param($stmt, 's', $position);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $count = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $count++;
            echo "  - {$row['first_name']} {$row['last_name']}: ";
            
            $res = sendPushNotification($db, $row['id'], 'Test', 'Test message', '/employee/attendance.php');
            
            if ($res['success']) {
                echo "SENT ({$res['sent']})\n";
                $totalSent += $res['sent'];
            } else {
                echo "FAILED: " . (isset($res['errors'][0]) ? $res['errors'][0] : 'Unknown') . "\n";
            }
        }
        
        if ($count === 0) {
            echo "  (No employees found)\n";
        }
        
        mysqli_stmt_close($stmt);
        echo "\n";
    }
    
    echo "Total sent: {$totalSent}\n";
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nScript complete.\n";
