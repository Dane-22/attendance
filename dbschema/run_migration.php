<?php
// Run database migration to add requested_by_user_id column
require_once __DIR__ . '/../conn/db_connection.php';

$sql = "ALTER TABLE overtime_requests 
        ADD COLUMN requested_by_user_id INT NULL AFTER requested_by,
        ADD INDEX idx_requested_by_user (requested_by_user_id)";

if (mysqli_query($db, $sql)) {
    echo "SUCCESS: Column requested_by_user_id added to overtime_requests table\n";
} else {
    $error = mysqli_error($db);
    if (strpos($error, 'Duplicate') !== false || strpos($error, 'already exists') !== false) {
        echo "INFO: Column already exists\n";
    } else {
        echo "ERROR: " . $error . "\n";
    }
}
