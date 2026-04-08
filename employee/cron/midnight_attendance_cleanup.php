<?php
/**
 * Midnight Auto-Close Cron Job for Attendance System
 * 
 * This script automatically closes open attendance records from the previous day.
 * Run this script at midnight (00:00) daily via cron job or Windows Task Scheduler.
 * 
 * Recommended Schedule: Daily at 00:00
 * Command: php /path/to/midnight_attendance_cleanup.php
 */

// Set timezone to Philippine Time
date_default_timezone_set('Asia/Manila');

// Include the database connection (uses .env file)
require_once __DIR__ . '/../../conn/db_connection.php';

// Get yesterday's date
$yesterday = date('Y-m-d', strtotime('-1 day'));
$currentDate = date('Y-m-d H:i:s');

// Log start
error_log("[Midnight Cleanup] Starting cleanup for records before {$yesterday}");

// Find all open records from yesterday and before
$selectSql = "SELECT id, employee_id, attendance_date, time_in, branch_name 
              FROM attendance 
              WHERE attendance_date <= ? 
              AND time_in IS NOT NULL 
              AND time_out IS NULL";

$selectStmt = mysqli_prepare($db, $selectSql);
if (!$selectStmt) {
    error_log("[Midnight Cleanup] Prepare failed: " . mysqli_error($db));
    exit(1);
}

mysqli_stmt_bind_param($selectStmt, 's', $yesterday);
mysqli_stmt_execute($selectStmt);
$result = mysqli_stmt_get_result($selectStmt);

$autoClosedCount = 0;
$failedCount = 0;
$recordsToClose = [];

// Collect records
while ($row = mysqli_fetch_assoc($result)) {
    $recordsToClose[] = $row;
}
mysqli_stmt_close($selectStmt);

// Close each open record
foreach ($recordsToClose as $record) {
    $attendanceId = $record['id'];
    $employeeId = $record['employee_id'];
    $recordDate = $record['attendance_date'];
    
    // Set time_out to end of the record's date (23:59:59)
    $autoTimeOut = $recordDate . ' 23:59:59';
    
    // Update the record
    $updateSql = "UPDATE attendance 
                  SET time_out = ?,
                      is_time_running = 0,
                      is_overtime_running = 0
                  WHERE id = ?";
    
    $updateStmt = mysqli_prepare($db, $updateSql);
    if ($updateStmt) {
        mysqli_stmt_bind_param($updateStmt, 'si', $autoTimeOut, $attendanceId);
        
        if (mysqli_stmt_execute($updateStmt)) {
            $autoClosedCount++;
            error_log("[Midnight Cleanup] Auto-closed record #{$attendanceId} for employee #{$employeeId} on {$recordDate}");
        } else {
            $failedCount++;
            error_log("[Midnight Cleanup] Failed to close record #{$attendanceId}: " . mysqli_stmt_error($updateStmt));
        }
        mysqli_stmt_close($updateStmt);
    } else {
        $failedCount++;
        error_log("[Midnight Cleanup] Prepare failed for update: " . mysqli_error($db));
    }
}

// Summary log
error_log("[Midnight Cleanup] Completed. Auto-closed: {$autoClosedCount}, Failed: {$failedCount}");

// Close database connection
mysqli_close($db);

// Output for manual runs
if (php_sapi_name() === 'cli') {
    echo "Midnight Attendance Cleanup - {$currentDate}\n";
    echo "Target Date: {$yesterday}\n";
    echo "Records Auto-Closed: {$autoClosedCount}\n";
    echo "Failed: {$failedCount}\n";
    echo "Done.\n";
}

exit(0);
?>
