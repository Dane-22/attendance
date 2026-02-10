-- Weekly Payroll Reports Table Schema
-- This table stores weekly payroll report data with support for editable fields like CA

CREATE TABLE IF NOT EXISTS `weekly_payroll_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `report_year` int NOT NULL,
  `report_month` int NOT NULL,
  `week_number` int NOT NULL COMMENT 'Week 1-5',
  `view_type` enum('weekly','monthly') DEFAULT 'weekly',
  `branch_id` int DEFAULT NULL COMMENT 'Filtered branch, NULL for all',
  
  -- Attendance & Rate Data
  `days_worked` int DEFAULT 0,
  `total_hours` int DEFAULT 0,
  `daily_rate` decimal(10,2) DEFAULT 0.00,
  `basic_pay` decimal(10,2) DEFAULT 0.00,
  
  -- Overtime
  `ot_hours` decimal(5,2) DEFAULT 0.00,
  `ot_rate` decimal(10,2) DEFAULT 0.00,
  `ot_amount` decimal(10,2) DEFAULT 0.00,
  
  -- Allowances
  `performance_allowance` decimal(10,2) DEFAULT 0.00,
  `gross_pay` decimal(10,2) DEFAULT 0.00,
  `gross_plus_allowance` decimal(10,2) DEFAULT 0.00,
  
  -- Deductions (CA is editable)
  `ca_deduction` decimal(10,2) DEFAULT 0.00 COMMENT 'Cash Advance - Fillable',
  `sss_deduction` decimal(10,2) DEFAULT 0.00,
  `philhealth_deduction` decimal(10,2) DEFAULT 0.00,
  `pagibig_deduction` decimal(10,2) DEFAULT 0.00,
  `sss_loan` decimal(10,2) DEFAULT 0.00,
  `total_deductions` decimal(10,2) DEFAULT 0.00,
  
  -- Net Pay
  `take_home_pay` decimal(10,2) DEFAULT 0.00,
  
  -- Report Metadata
  `status` enum('Draft','Finalized','Processed') DEFAULT 'Draft',
  `created_by` int DEFAULT NULL,
  `finalized_by` int DEFAULT NULL,
  `finalized_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_year_month_week` (`report_year`,`report_month`,`week_number`),
  KEY `idx_branch_id` (`branch_id`),
  KEY `idx_status` (`status`),
  KEY `idx_view_type` (`view_type`),
  UNIQUE KEY `unique_employee_period_week` (`employee_id`,`report_year`,`report_month`,`week_number`,`view_type`,`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Weekly Report Summary Table (for report-level metadata)
CREATE TABLE IF NOT EXISTS `weekly_report_summaries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `report_year` int NOT NULL,
  `report_month` int NOT NULL,
  `week_number` int NOT NULL,
  `view_type` enum('weekly','monthly') DEFAULT 'weekly',
  `branch_id` int DEFAULT NULL,
  `branch_filter_name` varchar(100) DEFAULT NULL,
  
  -- Summary Totals
  `total_employees` int DEFAULT 0,
  `total_days_worked` int DEFAULT 0,
  `total_basic_pay` decimal(12,2) DEFAULT 0.00,
  `total_ot_amount` decimal(12,2) DEFAULT 0.00,
  `total_allowances` decimal(12,2) DEFAULT 0.00,
  `total_gross_pay` decimal(12,2) DEFAULT 0.00,
  `total_ca_deductions` decimal(12,2) DEFAULT 0.00,
  `total_sss_deductions` decimal(12,2) DEFAULT 0.00,
  `total_philhealth_deductions` decimal(12,2) DEFAULT 0.00,
  `total_pagibig_deductions` decimal(12,2) DEFAULT 0.00,
  `total_sss_loans` decimal(12,2) DEFAULT 0.00,
  `total_deductions` decimal(12,2) DEFAULT 0.00,
  `total_take_home_pay` decimal(12,2) DEFAULT 0.00,
  
  -- Report Status
  `status` enum('Draft','Finalized','Exported') DEFAULT 'Draft',
  `exported_at` timestamp NULL DEFAULT NULL,
  `exported_by` int DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  KEY `idx_year_month_week` (`report_year`,`report_month`,`week_number`),
  KEY `idx_branch_id` (`branch_id`),
  KEY `idx_status` (`status`),
  UNIQUE KEY `unique_period_view_branch` (`report_year`,`report_month`,`week_number`,`view_type`,`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Weekly Report Audit Log (track changes to CA and other editable fields)
CREATE TABLE IF NOT EXISTS `weekly_report_audit_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `weekly_report_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `field_name` varchar(50) NOT NULL,
  `old_value` decimal(10,2) DEFAULT NULL,
  `new_value` decimal(10,2) DEFAULT NULL,
  `changed_by` int DEFAULT NULL,
  `changed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `change_reason` varchar(255) DEFAULT NULL,
  
  PRIMARY KEY (`id`),
  KEY `idx_weekly_report_id` (`weekly_report_id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_changed_at` (`changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Foreign Key Constraints (optional - add if needed)
-- ALTER TABLE `weekly_payroll_reports`
--   ADD CONSTRAINT `fk_wpr_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
--   ADD CONSTRAINT `fk_wpr_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
--   ADD CONSTRAINT `fk_wpr_created_by` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

-- ALTER TABLE `weekly_report_summaries`
--   ADD CONSTRAINT `fk_wrs_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
--   ADD CONSTRAINT `fk_wrs_created_by` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

-- ALTER TABLE `weekly_report_audit_log`
--   ADD CONSTRAINT `fk_wpal_report` FOREIGN KEY (`weekly_report_id`) REFERENCES `weekly_payroll_reports` (`id`) ON DELETE CASCADE,
--   ADD CONSTRAINT `fk_wpal_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;
