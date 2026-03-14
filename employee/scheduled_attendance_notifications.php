<?php
/**
 * Scheduled Push Notifications for Attendance Reminders
 * Run this script via cron job or Windows Task Scheduler
 * 
 * Schedule:
 * - Engineers: 
 *   - Time-in: 6:50 AM, Mon-Sat
 *   - Time-out: 3:50 PM, Mon-Sat
 * - Admin, Developer & Super Admin:
 *   - Time-in: 7:30 AM, Mon-Sat
 *   - Time-out: 4:50 PM, Mon-Fri only
 * - Workers & Employees:
 *   - Time-in: 8:00 AM, Mon-Sat
 *   - Time-out: 5:00 PM, Mon-Sat
 */

// Suppress PHP warnings
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';

// Get current time and day
$currentTime = date('H:i');
$currentDay = date('l'); // Monday, Tuesday, etc.
$currentDate = date('Y-m-d');

// Check if it's a weekend (Sunday)
if ($currentDay === 'Sunday') {
    echo "Sunday - No notifications sent.\n";
    exit(0);
}

$isWeekend = ($currentDay === 'Saturday');
$isWeekday = (!$isWeekend && $currentDay !== 'Sunday');

// Define notification schedules
$schedules = [
    // Engineer Time-in: 6:50 AM, Mon-Sat
    [
        'time' => '06:50',
        'positions' => ['Engineer'],
        'type' => 'time_in',
        'title' => 'Time In Reminder',
        'message' => 'Good morning! Please don\'t forget to time in for your shift. Have a great day!',
        'url' => '/employee/attendance.php',
        'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
    ],
    // Engineer Time-out: 3:50 PM, Mon-Sat
    [
        'time' => '15:50',
        'positions' => ['Engineer'],
        'type' => 'time_out',
        'title' => 'Time Out Reminder',
        'message' => 'Reminder: Please don\'t forget to time out before leaving. Have a safe trip home!',
        'url' => '/employee/attendance.php',
        'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
    ],
    // Admin, Developer & Super Admin Time-in: 7:30 AM, Mon-Sat
    [
        'time' => '07:30',
        'positions' => ['Admin', 'Developer', 'Super Admin'],
        'type' => 'time_in',
        'title' => 'Time In Reminder',
        'message' => 'Good morning! Please don\'t forget to time in for your shift. Have a productive day!',
        'url' => '/employee/attendance.php',
        'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
    ],
    // Admin, Developer & Super Admin Time-out: 4:50 PM, Mon-Sat
    [
        'time' => '16:50',
        'positions' => ['Admin', 'Developer', 'Super Admin'],
        'type' => 'time_out',
        'title' => 'Time Out Reminder',
        'message' => 'Reminder: Please don\'t forget to time out before leaving. Have a great evening!',
        'url' => '/employee/attendance.php',
        'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
    ],
    // Worker Time-in: 8:00 AM, Mon-Sat
    [
        'time' => '08:00',
        'positions' => ['Worker', 'Employee'],
        'type' => 'time_in',
        'title' => 'Time In Reminder',
        'message' => 'Good morning! Please don\'t forget to time in for your shift. Have a great day!',
        'url' => '/employee/attendance.php',
        'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
    ],
    // Worker Time-out: 5:00 PM, Mon-Sat
    [
        'time' => '17:00',
        'positions' => ['Worker', 'Employee'],
        'type' => 'time_out',
        'title' => 'Time Out Reminder',
        'message' => 'Reminder: Please don\'t forget to time out before leaving. Have a safe trip home!',
        'url' => '/employee/attendance.php',
        'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
    ],
];

$notificationsSent = 0;
$errors = [];

// Check each schedule
foreach ($schedules as $schedule) {
    // Check if current time matches (within 1 minute window)
    $scheduleTime = $schedule['time'];
    $timeWindow = 1; // 1 minute window
    
    // Convert to minutes for comparison
    $currentMinutes = (int)date('H') * 60 + (int)date('i');
    $scheduleMinutes = (int)substr($scheduleTime, 0, 2) * 60 + (int)substr($scheduleTime, 3, 2);
    
    // Check if within time window and day is valid
    $timeMatch = (abs($currentMinutes - $scheduleMinutes) <= $timeWindow);
    $dayMatch = in_array($currentDay, $schedule['days']);
    
    if ($timeMatch && $dayMatch) {
        echo "Processing schedule: {$schedule['title']} for " . implode(', ', $schedule['positions']) . "\n";
        
        // Get employees for these positions who haven't timed in/out yet
        $positions = $schedule['positions'];
        $placeholders = implode(',', array_fill(0, count($positions), '?'));
        
        // Build query to get active employees with these positions
        $sql = "SELECT id, first_name, last_name, position FROM employees 
                WHERE position IN ($placeholders) 
                AND status = 'Active'";
        
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            $errors[] = "Failed to prepare employee query: " . mysqli_error($db);
            continue;
        }
        
        // Bind position parameters
        $types = str_repeat('s', count($positions));
        mysqli_stmt_bind_param($stmt, $types, ...$positions);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $employees = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $employees[] = $row;
        }
        mysqli_stmt_close($stmt);
        
        echo "Found " . count($employees) . " employees\n";
        
        // Send notification to each employee
        foreach ($employees as $employee) {
            $employeeId = $employee['id'];
            $employeeName = $employee['first_name'] . ' ' . $employee['last_name'];
            
            // Check if already timed in/out today (to avoid duplicate notifications)
            if ($schedule['type'] === 'time_in') {
                // Check if already timed in
                $checkSql = "SELECT id FROM attendance 
                            WHERE employee_id = ? 
                            AND attendance_date = ? 
                            AND time_in IS NOT NULL";
                $checkStmt = mysqli_prepare($db, $checkSql);
                mysqli_stmt_bind_param($checkStmt, 'is', $employeeId, $currentDate);
                mysqli_stmt_execute($checkStmt);
                $checkResult = mysqli_stmt_get_result($checkStmt);
                $alreadyTimedIn = mysqli_num_rows($checkResult) > 0;
                mysqli_stmt_close($checkStmt);
                
                if ($alreadyTimedIn) {
                    echo "  - Skipping {$employeeName} (already timed in)\n";
                    continue;
                }
            } elseif ($schedule['type'] === 'time_out') {
                // Check if already timed out
                $checkSql = "SELECT id FROM attendance 
                            WHERE employee_id = ? 
                            AND attendance_date = ? 
                            AND time_out IS NOT NULL";
                $checkStmt = mysqli_prepare($db, $checkSql);
                mysqli_stmt_bind_param($checkStmt, 'is', $employeeId, $currentDate);
                mysqli_stmt_execute($checkStmt);
                $checkResult = mysqli_stmt_get_result($checkStmt);
                $alreadyTimedOut = mysqli_num_rows($checkResult) > 0;
                mysqli_stmt_close($checkStmt);
                
                // Also check if they timed in today (don't remind to clock out if they never clocked in)
                $checkInSql = "SELECT id FROM attendance 
                               WHERE employee_id = ? 
                               AND attendance_date = ? 
                               AND time_in IS NOT NULL";
                $checkInStmt = mysqli_prepare($db, $checkInSql);
                mysqli_stmt_bind_param($checkInStmt, 'is', $employeeId, $currentDate);
                mysqli_stmt_execute($checkInStmt);
                $checkInResult = mysqli_stmt_get_result($checkInStmt);
                $hasTimedIn = mysqli_num_rows($checkInResult) > 0;
                mysqli_stmt_close($checkInStmt);
                
                if ($alreadyTimedOut || !$hasTimedIn) {
                    echo "  - Skipping {$employeeName} (" . ($alreadyTimedOut ? "already timed out" : "never timed in") . ")\n";
                    continue;
                }
            }
            
            // Send push notification
            echo "  - Sending to {$employeeName} ({$employee['position']})\n";
            $result = sendPushNotification(
                $db,
                $employeeId,
                $schedule['title'],
                $schedule['message'],
                $schedule['url']
            );
            
            if ($result['success']) {
                $notificationsSent += $result['sent'];
                echo "    ✓ Sent successfully ({$result['sent']} notifications)\n";
            } else {
                $errorMsg = isset($result['errors'][0]) ? $result['errors'][0] : 'Unknown error';
                $errors[] = "Failed to send to {$employeeName}: {$errorMsg}";
                echo "    ✗ Failed: {$errorMsg}\n";
            }
        }
    }
}

// Summary
echo "\n========================================\n";
echo "Scheduled Push Notification Summary\n";
echo "========================================\n";
echo "Current Time: {$currentTime}\n";
echo "Current Day: {$currentDay}\n";
echo "Total Notifications Sent: {$notificationsSent}\n";

if (!empty($errors)) {
    echo "\nErrors:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
}

echo "\nDone.\n";
