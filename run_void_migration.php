<?php
/**
 * Run void attendance migration
 * This adds the is_voided column to the attendance table
 */

require_once __DIR__ . '/conn/db_connection.php';

// Enable error display temporarily for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain');

echo "Running void attendance migration...\n\n";

// Check if column already exists
$check_sql = "SHOW COLUMNS FROM attendance LIKE 'is_voided'";
$check_result = mysqli_query($db, $check_sql);

if (mysqli_num_rows($check_result) > 0) {
    echo "Column 'is_voided' already exists. Migration already applied.\n";
    exit;
}

// Run the migration
$sql = "ALTER TABLE attendance 
        ADD COLUMN is_voided TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Flag indicating if record is voided (0=active, 1=voided)' AFTER total_ot_hrs,
        ADD COLUMN void_reason TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Reason for voiding the record' AFTER is_voided,
        ADD COLUMN voided_by INT NULL DEFAULT NULL COMMENT 'User ID of admin who voided the record' AFTER void_reason,
        ADD COLUMN voided_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Timestamp when record was voided' AFTER voided_by,
        ADD INDEX idx_voided (is_voided) COMMENT 'Index for filtering voided records'";

if (mysqli_query($db, $sql)) {
    echo "SUCCESS! Migration completed.\n";
    echo "Added columns:\n";
    echo "  - is_voided (TINYINT, default 0)\n";
    echo "  - void_reason (TEXT, nullable)\n";
    echo "  - voided_by (INT, nullable)\n";
    echo "  - voided_at (TIMESTAMP, nullable)\n";
    echo "  - INDEX idx_voided\n";
} else {
    echo "ERROR: " . mysqli_error($db) . "\n";
}

// Verify
$result = mysqli_query($db, "SHOW COLUMNS FROM attendance LIKE 'is_voided'");
if (mysqli_num_rows($result) > 0) {
    echo "\nVerification: Column 'is_voided' now exists.\n";
}
