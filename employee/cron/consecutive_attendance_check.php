<?php
/**
 * Consecutive Attendance Issues Notification Script
 * 
 * Detects workers with 3+ consecutive days of Late/Absent attendance
 * and notifies Admin and Engineer position users.
 * 
 * Schedule: Run daily at 9:30 AM (after time-in window)
 * Cron: 30 9 * * * cd /var/www/jajr-project && php employee/cron/consecutive_attendance_check.php
 */

// Suppress PHP warnings
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

require_once __DIR__ . '/../../conn/db_connection.php';
require_once __DIR__ . '/../../functions.php';

// Configuration
$consecutiveThreshold = 3;  // Number of consecutive days to trigger
$workdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$monitorPositions = ['Worker'];
$notifyPositions = ['Admin', 'Engineer'];
$lookbackDays = 14;  // Look back 2 weeks to handle weekends

echo "========================================\n";
echo "Consecutive Attendance Check\n";
echo "Started: " . date('Y-m-d H:i:s') . "\n";
echo "========================================\n\n";

// Check if today is a workday
$today = date('l');
if (!in_array($today, $workdays)) {
    echo "Today is {$today} (non-workday). Skipping check.\n";
    exit(0);
}

echo "Today: {$today} - Running check...\n\n";

$notificationsSent = 0;
$workersChecked = 0;
$workersWithIssues = 0;
$errors = [];

try {
    // Step 1: Find all workers with attendance records in the lookback period
    $placeholders = implode(',', array_fill(0, count($monitorPositions), '?'));
    $sql = "SELECT DISTINCT e.id, e.first_name, e.last_name, e.employee_code, 
                   e.position, b.branch_name
            FROM employees e
            LEFT JOIN branches b ON b.id = e.branch_id
            WHERE e.position IN ($placeholders)
              AND e.status = 'Active'";
    
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare worker query: ' . mysqli_error($db));
    }
    
    $types = str_repeat('s', count($monitorPositions));
    mysqli_stmt_bind_param($stmt, $types, ...$monitorPositions);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $workers = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $workers[] = $row;
    }
    mysqli_stmt_close($stmt);
    
    echo "Found " . count($workers) . " active workers to check\n\n";
    
    if (empty($workers)) {
        echo "No workers found. Exiting.\n";
        exit(0);
    }
    
    // Step 2: For each worker, check consecutive attendance issues
    foreach ($workers as $worker) {
        $workersChecked++;
        $employeeId = $worker['id'];
        $employeeName = $worker['first_name'] . ' ' . $worker['last_name'];
        
        echo "Checking: {$employeeName} ({$worker['employee_code']})... ";
        
        // Get last X days of attendance (excluding Sundays)
        // We need to look at more days to account for weekends
        $attendanceSql = "SELECT attendance_date, status, time_in, time_out
                         FROM attendance
                         WHERE employee_id = ?
                           AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                           AND DAYOFWEEK(attendance_date) != 1  -- Exclude Sundays (1=Sunday)
                         ORDER BY attendance_date DESC
                         LIMIT ?";
        
        $attStmt = mysqli_prepare($db, $attendanceSql);
        if (!$attStmt) {
            $errors[] = "Failed to prepare attendance query for {$employeeName}";
            echo "ERROR\n";
            continue;
        }
        
        mysqli_stmt_bind_param($attStmt, 'iii', $employeeId, $lookbackDays, $consecutiveThreshold);
        mysqli_stmt_execute($attStmt);
        $attResult = mysqli_stmt_get_result($attStmt);
        
        $attendanceRecords = [];
        while ($attRow = mysqli_fetch_assoc($attResult)) {
            $attendanceRecords[] = $attRow;
        }
        mysqli_stmt_close($attStmt);
        
        // Check if we have enough records
        if (count($attendanceRecords) < $consecutiveThreshold) {
            echo "SKIP (only " . count($attendanceRecords) . " workdays found)\n";
            continue;
        }
        
        // Check if ALL records have issues (Late or Absent)
        $allHaveIssues = true;
        $issueDetails = [];
        
        foreach ($attendanceRecords as $record) {
            $status = $record['status'] ?? 'Absent';
            
            // If no time_in and status is Present/Late, treat as Absent
            if (empty($record['time_in']) && in_array($status, ['Present', 'Late'])) {
                $status = 'Absent';
            }
            
            if (!in_array($status, ['Late', 'Absent'])) {
                $allHaveIssues = false;
                break;
            }
            
            $issueDetails[] = [
                'date' => $record['attendance_date'],
                'status' => $status
            ];
        }
        
        if (!$allHaveIssues) {
            echo "OK (no consecutive issues)\n";
            continue;
        }
        
        // Check if already notified for this streak
        if (alreadyNotifiedForStreak($db, $employeeId, $issueDetails)) {
            echo "SKIP (already notified)\n";
            continue;
        }
        
        $workersWithIssues++;
        echo "ALERT ({$consecutiveThreshold} consecutive issues)\n";
        
        // Step 3: Get Admin and Engineer users to notify
        $notifyPlaceholders = implode(',', array_fill(0, count($notifyPositions), '?'));
        $notifySql = "SELECT id, first_name, last_name, position
                     FROM employees
                     WHERE position IN ($notifyPlaceholders)
                       AND status = 'Active'";
        
        $notifyStmt = mysqli_prepare($db, $notifySql);
        if (!$notifyStmt) {
            $errors[] = "Failed to prepare notify query";
            continue;
        }
        
        $notifyTypes = str_repeat('s', count($notifyPositions));
        mysqli_stmt_bind_param($notifyStmt, $notifyTypes, ...$notifyPositions);
        mysqli_stmt_execute($notifyStmt);
        $notifyResult = mysqli_stmt_get_result($notifyStmt);
        
        $recipients = [];
        while ($notifyRow = mysqli_fetch_assoc($notifyResult)) {
            $recipients[] = $notifyRow;
        }
        mysqli_stmt_close($notifyStmt);
        
        echo "  -> Notifying " . count($recipients) . " recipients\n";
        
        // Step 4: Build notification message
        $datesList = [];
        foreach ($issueDetails as $detail) {
            $datesList[] = date('M d', strtotime($detail['date'])) . ': ' . $detail['status'];
        }
        
        $notificationTitle = 'Attendance Alert: Consecutive Issues';
        $notificationMessage = sprintf(
            "%s (%s) has %d consecutive attendance issues:\n- %s\n\nBranch: %s",
            $employeeName,
            $worker['employee_code'],
            count($issueDetails),
            implode("\n- ", $datesList),
            $worker['branch_name'] ?? 'Unknown'
        );
        
        $notificationUrl = '/employee/audit.php?search=' . urlencode($worker['employee_code']) . '&search_type=all';
        
        // Step 5: Send notifications
        foreach ($recipients as $recipient) {
            $recipientName = $recipient['first_name'] . ' ' . $recipient['last_name'];
            
            // Insert into employee_notifications table
            $insertSql = "INSERT INTO employee_notifications 
                         (employee_id, notification_type, title, message, is_read, created_at)
                         VALUES (?, 'attendance_alert', ?, ?, 0, NOW())";
            
            $insertStmt = mysqli_prepare($db, $insertSql);
            if ($insertStmt) {
                mysqli_stmt_bind_param($insertStmt, 'iss', 
                    $recipient['id'], 
                    $notificationTitle, 
                    $notificationMessage
                );
                mysqli_stmt_execute($insertStmt);
                mysqli_stmt_close($insertStmt);
            }
            
            // Send push notification
            $pushResult = sendPushNotification(
                $db,
                $recipient['id'],
                $notificationTitle,
                $notificationMessage,
                $notificationUrl
            );
            
            if ($pushResult['success']) {
                echo "  -> Sent to {$recipientName} ({$recipient['position']})\n";
                $notificationsSent += $pushResult['sent'];
            } else {
                echo "  -> Failed to send to {$recipientName}: " . ($pushResult['errors'][0] ?? 'Unknown error') . "\n";
            }
        }
        
        // Log the notification sent
        logNotificationSent($db, $employeeId, $issueDetails);
        
        // Log activity
        logActivity($db, 'Attendance Alert', 
            "Consecutive attendance alert sent for {$employeeName} ({$worker['employee_code']}): " . 
            count($issueDetails) . " consecutive days with issues. Notified " . count($recipients) . " staff."
        );
        
        echo "\n";
    }
    
} catch (Exception $e) {
    $errors[] = "Fatal error: " . $e->getMessage();
    echo "\nFATAL ERROR: " . $e->getMessage() . "\n";
}

echo "========================================\n";
echo "Summary\n";
echo "========================================\n";
echo "Workers checked: {$workersChecked}\n";
echo "Workers with consecutive issues: {$workersWithIssues}\n";
echo "Notifications sent: {$notificationsSent}\n";

if (!empty($errors)) {
    echo "\nErrors encountered:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
}

echo "\nCompleted at: " . date('Y-m-d H:i:s') . "\n";

// Helper function: Check if already notified for this streak
function alreadyNotifiedForStreak($db, $employeeId, $issueDetails) {
    // Get the most recent issue date
    $latestDate = $issueDetails[0]['date'] ?? null;
    if (!$latestDate) {
        return false;
    }
    
    // Check if we already sent a notification for this employee
    // with this latest date (meaning we've already alerted for this streak)
    $checkSql = "SELECT id FROM attendance_notification_log 
                WHERE employee_id = ? 
                  AND latest_issue_date = ?
                  AND notification_type = 'consecutive_attendance'
                LIMIT 1";
    
    $checkStmt = mysqli_prepare($db, $checkSql);
    if (!$checkStmt) {
        return false;  // If table doesn't exist, proceed anyway
    }
    
    mysqli_stmt_bind_param($checkStmt, 'is', $employeeId, $latestDate);
    mysqli_stmt_execute($checkStmt);
    mysqli_stmt_store_result($checkStmt);
    $alreadyExists = mysqli_stmt_num_rows($checkStmt) > 0;
    mysqli_stmt_close($checkStmt);
    
    return $alreadyExists;
}

// Helper function: Log notification sent
function logNotificationSent($db, $employeeId, $issueDetails) {
    // Create table if not exists
    $createTableSql = "CREATE TABLE IF NOT EXISTS attendance_notification_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        notification_type VARCHAR(50) NOT NULL,
        issue_count INT NOT NULL,
        issue_dates VARCHAR(255) NOT NULL,
        latest_issue_date DATE NOT NULL,
        notified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_employee_type (employee_id, notification_type),
        INDEX idx_latest_date (latest_issue_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    mysqli_query($db, $createTableSql);
    
    // Build issue dates string
    $dates = [];
    foreach ($issueDetails as $detail) {
        $dates[] = $detail['date'] . ':' . $detail['status'];
    }
    $datesString = implode('|', $dates);
    $latestDate = $issueDetails[0]['date'] ?? date('Y-m-d');
    
    // Insert log record
    $insertSql = "INSERT INTO attendance_notification_log 
                 (employee_id, notification_type, issue_count, issue_dates, latest_issue_date)
                 VALUES (?, 'consecutive_attendance', ?, ?, ?)";
    
    $insertStmt = mysqli_prepare($db, $insertSql);
    if ($insertStmt) {
        $count = count($issueDetails);
        mysqli_stmt_bind_param($insertStmt, 'isss', $employeeId, $count, $datesString, $latestDate);
        mysqli_stmt_execute($insertStmt);
        mysqli_stmt_close($insertStmt);
    }
}

mysqli_close($db);
exit(0);
