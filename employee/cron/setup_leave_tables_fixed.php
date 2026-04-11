<?php
/**
 * CLI Setup Script for Leave Tables
 * Run this to create the leave management tables without web access
 */

date_default_timezone_set('Asia/Manila');

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("CLI only\n");
}

require_once __DIR__ . '/../../conn/db_connection.php';

echo "=== Leave System Table Setup ===\n\n";

$success = [];
$errors = [];

// Create employee_leaves table (without FK to avoid issues)
$sql1 = "CREATE TABLE IF NOT EXISTS employee_leaves (
  id INT NOT NULL AUTO_INCREMENT,
  employee_id INT NOT NULL,
  total_leaves DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  used_leaves DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  remaining_leaves DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  last_credited_month DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_employee (employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($db, $sql1)) {
    $success[] = "Table 'employee_leaves' created";
} else {
    $errors[] = "Error creating employee_leaves: " . mysqli_error($db);
}

// Create leave_transactions table (without FK)
$sql2 = "CREATE TABLE IF NOT EXISTS leave_transactions (
  id INT NOT NULL AUTO_INCREMENT,
  employee_id INT NOT NULL,
  transaction_type ENUM('credit', 'debit', 'adjustment') NOT NULL,
  amount DECIMAL(5,2) NOT NULL,
  description VARCHAR(255) NOT NULL,
  reference_date DATE NOT NULL,
  created_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_employee_date (employee_id, reference_date),
  KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($db, $sql2)) {
    $success[] = "Table 'leave_transactions' created";
} else {
    $errors[] = "Error creating leave_transactions: " . mysqli_error($db);
}

// Initialize records for active employees
$sql3 = "INSERT INTO employee_leaves (employee_id, total_leaves, used_leaves, remaining_leaves, last_credited_month)
SELECT e.id, 0.00, 0.00, 0.00, NULL
FROM employees e
WHERE e.status = 'Active'
  AND NOT EXISTS (SELECT 1 FROM employee_leaves el WHERE el.employee_id = e.id)";

if (mysqli_query($db, $sql3)) {
    $affected = mysqli_affected_rows($db);
    $success[] = "Initialized {$affected} employee records";
} else {
    $errors[] = "Error initializing records: " . mysqli_error($db);
}

echo "Results:\n";
echo str_repeat("-", 40) . "\n";
foreach ($success as $msg) echo "OK: {$msg}\n";
foreach ($errors as $msg) echo "ERR: {$msg}\n";
echo str_repeat("-", 40) . "\n";

exit(empty($errors) ? 0 : 1);
