-- Fix for missing 'employees' table on production server
-- Run this SQL on the production database to create the table

DROP TABLE IF EXISTS `employees`;

CREATE TABLE IF NOT EXISTS `employees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `middle_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Employee',
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `profile_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `daily_rate` decimal(10,2) DEFAULT '600.00',
  `branch_id` int DEFAULT NULL,
  `has_deduction` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Whether employee is subject to SSS/PhilHealth/PagIBIG deduction (1=yes, 0=no)',
  `sss_loan` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_code` (`employee_code`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_employees_branch` (`branch_id`),
  KEY `idx_has_deduction` (`has_deduction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert a default Super Admin user (password: admin123)
-- Change the password hash and email as needed
INSERT INTO `employees` (`employee_code`, `first_name`, `middle_name`, `last_name`, `email`, `password_hash`, `position`, `status`, `daily_rate`, `branch_id`, `has_deduction`, `sss_loan`) VALUES
('SA001', 'Super', 'Admin', 'User', 'admin@jajrconstruction.com', '$2y$10$RSHOb3hskFZueMLlCycFuua/4EwcxGmAIzpcl8ixQpEXY3tfu9LYi', 'Super Admin', 'Active', 600.00, NULL, 1, 0.00);
