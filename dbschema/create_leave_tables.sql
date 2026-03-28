-- Employee Leave Credits System Migration
-- Run this SQL to create the leave management tables

-- ============================================
-- Table: employee_leaves
-- Stores current leave balance for each employee
-- ============================================
CREATE TABLE IF NOT EXISTS `employee_leaves` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `employee_id` INT NOT NULL,
  `total_leaves` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Total leave credits earned',
  `used_leaves` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Total leaves used',
  `remaining_leaves` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Available leaves (calculated: total - used)',
  `last_credited_month` DATE DEFAULT NULL COMMENT 'Last month leave was credited (YYYY-MM-01 format)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee` (`employee_id`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: leave_transactions
-- Audit trail for all leave credits and debits
-- ============================================
CREATE TABLE IF NOT EXISTS `leave_transactions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `employee_id` INT NOT NULL,
  `transaction_type` ENUM('credit', 'debit', 'adjustment') NOT NULL,
  `amount` DECIMAL(5,2) NOT NULL COMMENT 'Positive number for all types',
  `description` VARCHAR(255) NOT NULL,
  `reference_date` DATE NOT NULL COMMENT 'Date of leave taken or credited',
  `created_by` INT DEFAULT NULL COMMENT 'Admin who created the transaction (NULL for system)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_date` (`employee_id`, `reference_date`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Initial Data Migration
-- Create leave records for all existing active employees
-- ============================================
INSERT INTO `employee_leaves` (`employee_id`, `total_leaves`, `used_leaves`, `remaining_leaves`, `last_credited_month`)
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
  );

