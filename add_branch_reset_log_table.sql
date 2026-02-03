-- Add branch_reset_log table for daily employee branch reset functionality
-- Run this script to add the table to your database

DROP TABLE IF EXISTS `branch_reset_log`;
CREATE TABLE IF NOT EXISTS `branch_reset_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reset_date` date NOT NULL,
  `employees_affected` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reset_date` (`reset_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Insert initial record for today
INSERT INTO `branch_reset_log` (`id`, `reset_date`, `employees_affected`, `created_at`) VALUES
(1, CURDATE(), 0, NOW());

-- Optional: Reset all employees to 'none' for initial setup
-- UPDATE employees SET branch_name = 'none' WHERE status = 'Active';