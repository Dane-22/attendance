-- E-Signatures table for cash advance and other documents
CREATE TABLE IF NOT EXISTS `e_signatures` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `signature_type` varchar(50) NOT NULL DEFAULT 'employee', -- 'employee', 'admin', 'hr'
  `signature_image` varchar(255) NOT NULL, -- path to signature image
  `signature_data` longtext, -- base64 encoded signature data
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_signature_type` (`signature_type`),
  KEY `idx_is_active` (`is_active`),
  UNIQUE KEY `unique_employee_type` (`employee_id`, `signature_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
