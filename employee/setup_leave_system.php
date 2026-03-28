<?php
/**
 * Leave System Setup Script
 * Run this to create the leave management tables and initialize employee records
 * Access via: http://your-domain/main/employee/setup_leave_system.php
 */

session_start();
require('../conn/db_connection.php');

// Check if user is logged in and is admin
$can_access = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              in_array($_SESSION['position'] ?? '', ['Super Admin', 'Admin']);

if (!$can_access) {
    die('Access denied. Super Admin or Admin only.');
}

$success_messages = [];
$error_messages = [];

// Create employee_leaves table
$table1_sql = "CREATE TABLE IF NOT EXISTS `employee_leaves` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `employee_id` INT NOT NULL,
  `total_leaves` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `used_leaves` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `remaining_leaves` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `last_credited_month` DATE DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee` (`employee_id`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($db, $table1_sql)) {
    $success_messages[] = "Table 'employee_leaves' created successfully";
} else {
    $error_messages[] = "Error creating employee_leaves: " . mysqli_error($db);
}

// Create leave_transactions table
$table2_sql = "CREATE TABLE IF NOT EXISTS `leave_transactions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `employee_id` INT NOT NULL,
  `transaction_type` ENUM('credit', 'debit', 'adjustment') NOT NULL,
  `amount` DECIMAL(5,2) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `reference_date` DATE NOT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_date` (`employee_id`, `reference_date`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($db, $table2_sql)) {
    $success_messages[] = "Table 'leave_transactions' created successfully";
} else {
    $error_messages[] = "Error creating leave_transactions: " . mysqli_error($db);
}

// Initialize leave records for existing active employees
$init_sql = "INSERT INTO `employee_leaves` (`employee_id`, `total_leaves`, `used_leaves`, `remaining_leaves`, `last_credited_month`)
SELECT 
  e.id,
  0.00 AS total_leaves,
  0.00 AS used_leaves,
  0.00 AS remaining_leaves,
  NULL AS last_credited_month
FROM `employees` e
WHERE e.status = 'Active'
  AND NOT EXISTS (
    SELECT 1 FROM `employee_leaves` el WHERE el.employee_id = e.id
  )";

if (mysqli_query($db, $init_sql)) {
    $affected = mysqli_affected_rows($db);
    $success_messages[] = "Initialized leave records for {$affected} active employees";
} else {
    $error_messages[] = "Error initializing records: " . mysqli_error($db);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Leave System Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 4px; }
        h1 { color: #333; }
        .back-link { margin-top: 20px; display: inline-block; }
    </style>
</head>
<body>
    <h1>Leave System Setup</h1>
    
    <?php if (empty($success_messages) && empty($error_messages)): ?>
        <p>No actions performed.</p>
    <?php endif; ?>
    
    <?php foreach ($success_messages as $msg): ?>
        <div class="success">✓ <?php echo htmlspecialchars($msg); ?></div>
    <?php endforeach; ?>
    
    <?php foreach ($error_messages as $msg): ?>
        <div class="error">✗ <?php echo htmlspecialchars($msg); ?></div>
    <?php endforeach; ?>
    
    <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
</body>
</html>
