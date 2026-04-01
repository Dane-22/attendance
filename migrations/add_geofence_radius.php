<?php
/**
 * Migration: Add geofence_radius column to branches table
 * Run this on the server if the column doesn't exist
 */

require_once __DIR__ . '/conn/db_connection.php';

header('Content-Type: text/plain');

echo "Checking branches table columns...\n";

// Check if geofence_radius column exists
$checkSql = "SELECT COUNT(*) as cnt
               FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE()
                 AND TABLE_NAME = 'branches'
                 AND COLUMN_NAME = 'geofence_radius'";

$result = mysqli_query($db, $checkSql);
$row = mysqli_fetch_assoc($result);

if (intval($row['cnt']) === 0) {
    echo "Column 'geofence_radius' not found. Adding it...\n";
    
    $alterSql = "ALTER TABLE branches 
                 ADD COLUMN geofence_radius INT DEFAULT 200 COMMENT 'Geofence radius in meters'";
    
    if (mysqli_query($db, $alterSql)) {
        echo "SUCCESS: Added 'geofence_radius' column with default value of 200 meters.\n";
    } else {
        echo "ERROR: " . mysqli_error($db) . "\n";
    }
} else {
    echo "Column 'geofence_radius' already exists. No changes needed.\n";
}

// Also verify lat/long columns exist
$checkLat = "SELECT COUNT(*) as cnt
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'branches'
               AND COLUMN_NAME = 'lat'";
               
$checkLong = "SELECT COUNT(*) as cnt
              FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'branches'
                AND COLUMN_NAME = 'long'";

$resultLat = mysqli_query($db, $checkLat);
$rowLat = mysqli_fetch_assoc($resultLat);

$resultLong = mysqli_query($db, $checkLong);
$rowLong = mysqli_fetch_assoc($resultLong);

echo "\nColumn status:\n";
echo "- lat: " . (intval($rowLat['cnt']) > 0 ? "EXISTS" : "MISSING") . "\n";
echo "- long: " . (intval($rowLong['cnt']) > 0 ? "EXISTS" : "MISSING") . "\n";
echo "- geofence_radius: " . (intval($row['cnt']) > 0 ? "EXISTS" : "ADDED/EXISTING") . "\n";

echo "\nDone.\n";
?>
