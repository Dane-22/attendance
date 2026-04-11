<?php
require_once __DIR__ . '/conn/db_connection.php';

$sql = "ALTER TABLE weekly_payroll_reports 
        MODIFY COLUMN view_type ENUM('weekly', 'monthly', 'range') DEFAULT 'weekly'";

if (mysqli_query($db, $sql)) {
    echo "Successfully updated weekly_payroll_reports.view_type column\n";
} else {
    echo "Error: " . mysqli_error($db) . "\n";
}

// Also fix summary_reports if it exists
$sql2 = "ALTER TABLE summary_reports 
         MODIFY COLUMN view_type ENUM('weekly', 'monthly', 'range') DEFAULT 'weekly'";

if (mysqli_query($db, $sql2)) {
    echo "Successfully updated summary_reports.view_type column\n";
} else {
    echo "summary_reports error (may not exist): " . mysqli_error($db) . "\n";
}

mysqli_close($db);
echo "Done!";
