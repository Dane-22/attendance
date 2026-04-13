-- Migration: Add void attendance columns to attendance table
-- Date: April 13, 2026
-- Description: Adds columns to support voiding attendance records

ALTER TABLE `attendance` 
ADD COLUMN `is_voided` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Flag indicating if record is voided (0=active, 1=voided)' AFTER `total_ot_hrs`,
ADD COLUMN `void_reason` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Reason for voiding the record' AFTER `is_voided`,
ADD COLUMN `voided_by` INT NULL DEFAULT NULL COMMENT 'User ID of admin who voided the record' AFTER `void_reason`,
ADD COLUMN `voided_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Timestamp when record was voided' AFTER `voided_by`,
ADD INDEX `idx_voided` (`is_voided`) COMMENT 'Index for filtering voided records';

-- Verify migration
DESCRIBE `attendance`;
