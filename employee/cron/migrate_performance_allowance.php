<?php
/**
 * Migration: Add performance_allowance to employees table
 * Run this once to add the column for storing default performance allowance per employee
 */

require_once __DIR__ . '/../conn/db_connection.php';

// Check if column exists
$check_query = "SHOW COLUMNS FROM employees LIKE 'performance_allowance'";
$result = mysqli_query($db, $check_query);

if (mysqli_num_rows($result) > 0) {
    echo "Column 'performance_allowance' already exists in employees table.\n";
} else {
    // Add the column
    $alter_query = "ALTER TABLE employees 
                    ADD COLUMN performance_allowance DECIMAL(10,2) DEFAULT 0.00 
                    AFTER daily_rate";
    
    if (mysqli_query($db, $alter_query)) {
        echo "Successfully added 'performance_allowance' column to employees table.\n";
    } else {
        echo "Error adding column: " . mysqli_error($db) . "\n";
    }
}

mysqli_close($db);
echo "Migration complete.\n";
?>
