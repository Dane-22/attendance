-- Payroll System New Tables
-- Created for weekly_report.php backend recreation
-- Run this SQL to create the new supporting tables

-- ============================================
-- 1. payroll_periods
-- Manages payroll periods (weekly/monthly) with closure tracking
-- ============================================
DROP TABLE IF EXISTS `payroll_periods`;
CREATE TABLE IF NOT EXISTS `payroll_periods` (
  `id` int NOT NULL AUTO_INCREMENT,
  `period_type` enum('weekly','monthly','custom') NOT NULL,
  `year` int NOT NULL,
  `month` int NOT NULL,
  `week_number` int DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_closed` tinyint(1) DEFAULT '0',
  `closed_by` int DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_period` (`year`,`month`,`week_number`,`period_type`),
  KEY `idx_period_type` (`period_type`),
  KEY `idx_dates` (`start_date`,`end_date`),
  KEY `idx_is_closed` (`is_closed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. payroll_adjustments
-- Stores manual adjustments (allowances, loans, cash advances)
-- ============================================
DROP TABLE IF EXISTS `payroll_adjustments`;
CREATE TABLE IF NOT EXISTS `payroll_adjustments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `period_id` int NOT NULL,
  `adjustment_type` enum('allowance','cash_advance','sss_loan','other') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_period` (`employee_id`,`period_id`),
  KEY `idx_adjustment_type` (`adjustment_type`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_adjustment_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_adjustment_period` FOREIGN KEY (`period_id`) REFERENCES `payroll_periods`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. payment_status_tracking
-- Tracks payment status per employee per period
-- ============================================
DROP TABLE IF EXISTS `payment_status_tracking`;
CREATE TABLE IF NOT EXISTS `payment_status_tracking` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `period_id` int NOT NULL,
  `status` enum('Not Paid','Paid','Pending') DEFAULT 'Not Paid',
  `paid_at` timestamp NULL DEFAULT NULL,
  `paid_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_emp_period` (`employee_id`,`period_id`),
  KEY `idx_status` (`status`),
  KEY `idx_paid_at` (`paid_at`),
  CONSTRAINT `fk_payment_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payment_period` FOREIGN KEY (`period_id`) REFERENCES `payroll_periods`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. payroll_cache
-- Caches calculated payroll data for performance
-- ============================================
DROP TABLE IF EXISTS `payroll_cache`;
CREATE TABLE IF NOT EXISTS `payroll_cache` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cache_key` varchar(64) NOT NULL,
  `period_id` int DEFAULT NULL,
  `branch_id` int DEFAULT NULL,
  `view_type` varchar(20) NOT NULL,
  `employee_count` int DEFAULT '0',
  `summary_data` json DEFAULT NULL,
  `cached_data` json DEFAULT NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_cache_key` (`cache_key`),
  KEY `idx_period_branch` (`period_id`,`branch_id`),
  KEY `idx_expires` (`expires_at`),
  KEY `idx_view_type` (`view_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. payslip_templates (optional - for future payslip customization)
-- ============================================
DROP TABLE IF EXISTS `payslip_templates`;
CREATE TABLE IF NOT EXISTS `payslip_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `template_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `template_content` text COLLATE utf8mb4_unicode_ci,
  `is_default` tinyint(1) DEFAULT '0',
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_is_default` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Insert default payslip template
-- ============================================
INSERT INTO `payslip_templates` (`template_name`, `is_default`, `created_by`) VALUES
('Default Template', 1, 1);

-- ============================================
-- Sample data for testing (optional)
-- Uncomment if you want sample data
-- ============================================
/*
-- Create a sample payroll period
INSERT INTO `payroll_periods` (`period_type`, `year`, `month`, `week_number`, `start_date`, `end_date`) VALUES
('weekly', 2026, 4, 2, '2026-04-07', '2026-04-11'),
('monthly', 2026, 4, NULL, '2026-04-01', '2026-04-30');

-- Sample payment status
INSERT INTO `payment_status_tracking` (`employee_id`, `period_id`, `status`) VALUES
(24, 1, 'Not Paid'),
(27, 1, 'Not Paid');
*/

-- ============================================
-- Migration helper: Migrate existing data from employees table
-- This migrates performance_allowance and sss_loan if they exist
-- ============================================
-- Note: Run this after creating the tables if you have existing data

DELIMITER //
CREATE PROCEDURE IF NOT EXISTS MigrateExistingPayrollData()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_employee_id INT;
    DECLARE v_allowance DECIMAL(10,2);
    DECLARE v_loan DECIMAL(10,2);
    
    -- Check if performance_allowance column exists in employees
    IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_NAME = 'employees' AND COLUMN_NAME = 'performance_allowance') THEN
        
        -- Cursor for employees with allowances
        DECLARE cur CURSOR FOR 
            SELECT id, performance_allowance, sss_loan 
            FROM employees 
            WHERE performance_allowance > 0 OR sss_loan > 0;
        DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
        
        OPEN cur;
        read_loop: LOOP
            FETCH cur INTO v_employee_id, v_allowance, v_loan;
            IF done THEN
                LEAVE read_loop;
            END IF;
            
            -- Note: This would need a period_id to insert properly
            -- This is just a template for migration logic
            
        END LOOP;
        CLOSE cur;
    END IF;
END //
DELIMITER ;

-- ============================================
-- Indexes on existing tables (if not already present)
-- ============================================
-- These help with the new payroll queries

-- Add index on daily_payroll_reports for faster lookups
ALTER TABLE `daily_payroll_reports` 
ADD INDEX IF NOT EXISTS `idx_status` (`status`),
ADD INDEX IF NOT EXISTS `idx_branch_date` (`branch_id`, `report_date`);

-- Add index on attendance for void filtering
ALTER TABLE `attendance` 
ADD INDEX IF NOT EXISTS `idx_employee_date_status` (`employee_id`, `attendance_date`, `status`);

-- ============================================
-- Done
-- ============================================
