<?php
// Test script to check overtime_requests table and report.php
require_once __DIR__ . '/conn/db_connection.php';

echo "Testing database connection...\n";

// Check if overtime_requests table exists
$result = mysqli_query($db, "SHOW TABLES LIKE 'overtime_requests'");
$exists = mysqli_num_rows($result) > 0;
echo "overtime_requests table exists: " . ($exists ? 'YES' : 'NO') . "\n";

if ($exists) {
    // Check table structure
    $result = mysqli_query($db, "DESCRIBE overtime_requests");
    echo "\nTable columns:\n";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    // Check for approved overtime in current week
    $start_date = date('Y-m-d', strtotime('monday this week'));
    $end_date = date('Y-m-d', strtotime('sunday this week'));
    echo "\nChecking approved overtime for $start_date to $end_date...\n";
    
    $query = "SELECT r.employee_id, r.request_date, r.ot_hours, r.status
              FROM overtime_requests r
              WHERE r.request_date BETWEEN ? AND ?
              AND r.status IN ('approved', 'pre-approved')";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $count = mysqli_num_rows($result);
    echo "Found $count approved overtime records\n";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "  - Employee " . $row['employee_id'] . ": " . $row['ot_hours'] . " hrs on " . $row['request_date'] . " (" . $row['status'] . ")\n";
    }
} else {
    echo "ERROR: overtime_requests table does not exist!\n";
}
