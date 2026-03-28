-- Create leave_requests table for engineer leave requests
CREATE TABLE IF NOT EXISTS `leave_requests` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `employee_id` INT NOT NULL,
  `leave_date` DATE NOT NULL,
  `leave_type` ENUM('Sick', 'Vacation', 'Personal', 'Emergency') NOT NULL DEFAULT 'Personal',
  `days_requested` DECIMAL(3,1) NOT NULL DEFAULT 1.0,
  `reason` TEXT NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `requested_by` VARCHAR(100) DEFAULT NULL,
  `requested_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `approved_by` INT DEFAULT NULL,
  `approved_at` TIMESTAMP NULL DEFAULT NULL,
  `rejection_reason` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_leave_date` (`leave_date`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
