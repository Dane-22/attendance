-- ============================================================
-- GeoLocator Implementation - Database Migration
-- Phase 1: Foundation
-- Created: March 28, 2026
-- ============================================================

-- Use the attendance database
USE `attendance_db`;

-- ============================================================
-- 1. Update existing branches table - Add geofence columns
--    (lat/long already exist as VARCHAR(20))
-- ============================================================

-- Add geofence radius column (default 200 meters)
ALTER TABLE `branches` 
ADD COLUMN `geofence_radius_meters` INT DEFAULT 200 
AFTER `long`;

-- Add location verified flag
ALTER TABLE `branches` 
ADD COLUMN `location_verified` TINYINT(1) DEFAULT 0 
AFTER `geofence_radius_meters`;

-- Add index for geofence queries
ALTER TABLE `branches` 
ADD INDEX `idx_branch_location` (`lat`, `long`);

-- ============================================================
-- 2. Update attendance table - Add location tracking columns
-- ============================================================

-- Add clock-in location columns
ALTER TABLE `attendance` 
ADD COLUMN `clock_in_lat` DECIMAL(10, 8) NULL AFTER `time_in`,
ADD COLUMN `clock_in_lng` DECIMAL(11, 8) NULL AFTER `clock_in_lat`;

-- Add clock-out location columns
ALTER TABLE `attendance` 
ADD COLUMN `clock_out_lat` DECIMAL(10, 8) NULL AFTER `time_out`,
ADD COLUMN `clock_out_lng` DECIMAL(11, 8) NULL AFTER `clock_out_lat`;

-- Add location verification flag
ALTER TABLE `attendance` 
ADD COLUMN `location_verified` TINYINT(1) DEFAULT 0 
AFTER `clock_out_lng`;

-- Add location accuracy (meters)
ALTER TABLE `attendance` 
ADD COLUMN `location_accuracy` FLOAT NULL 
AFTER `location_verified`;

-- Add index for location queries
ALTER TABLE `attendance` 
ADD INDEX `idx_attendance_location` (`clock_in_lat`, `clock_in_lng`);

-- ============================================================
-- 3. Create location_logs table for audit trail
-- ============================================================

CREATE TABLE IF NOT EXISTS `location_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `attendance_id` INT NULL,
  `action_type` ENUM('clock_in', 'clock_out', 'qr_scan', 'manual_override') NOT NULL,
  `latitude` DECIMAL(10, 8) NOT NULL,
  `longitude` DECIMAL(11, 8) NOT NULL,
  `accuracy_meters` FLOAT NULL,
  `branch_id` INT NULL,
  `distance_from_branch_meters` INT NULL,
  `device_info` VARCHAR(255) NULL,
  `ip_address` VARCHAR(45) NULL,
  `is_validated` TINYINT(1) DEFAULT 0,
  `validation_failure_reason` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_employee_date` (`employee_id`, `created_at`),
  INDEX `idx_location` (`latitude`, `longitude`),
  INDEX `idx_attendance_id` (`attendance_id`),
  INDEX `idx_branch_id` (`branch_id`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Audit trail for geolocation data - 90 day retention';

-- ============================================================
-- 4. Create employee_location_consent table
-- ============================================================

CREATE TABLE IF NOT EXISTS `employee_location_consent` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL UNIQUE,
  `consent_given` TINYINT(1) DEFAULT 0,
  `consent_date` TIMESTAMP NULL,
  `consent_ip` VARCHAR(45) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Track employee consent for location tracking';

-- ============================================================
-- 5. Update notifications table type enum (if needed)
--    Add new types for geolocation alerts
-- ============================================================

-- Note: Modify the existing notifications table to support geolocation types
-- This requires checking current enum values first
-- If needed, run: 
-- ALTER TABLE `notifications` 
-- MODIFY COLUMN `type` ENUM('PR Created','PR Approved','PR Rejected','PO Created',
--   'Item Received','System','Geofence Alert','Branch Location Missing') 
--   DEFAULT 'System';

-- ============================================================
-- 6. Set default geofence radius for existing branches
-- ============================================================

-- Update all active branches with default 200m radius
UPDATE `branches` 
SET `geofence_radius_meters` = 200 
WHERE `geofence_radius_meters` IS NULL OR `geofence_radius_meters` = 0;

-- ============================================================
-- 7. Set geofence radius by branch type (based on your decisions)
-- ============================================================

-- Outdoor sites (BCDA locations, Sto. Rosario, Panicsican, etc.): 250m
UPDATE `branches` 
SET `geofence_radius_meters` = 250 
WHERE `branch_name` LIKE 'BCDA%' 
   OR `branch_name` IN ('Sto. Rosario', 'Panicsican', 'Dallangican', 'Maintenance');

-- Indoor offices (Capitol - Accounting, Pias - Office): 150m
UPDATE `branches` 
SET `geofence_radius_meters` = 150 
WHERE `branch_name` LIKE '%Office%' 
   OR `branch_name` LIKE '%Accounting%';

-- Large/Main office: 300m
UPDATE `branches` 
SET `geofence_radius_meters` = 300 
WHERE `branch_name` = 'MAIN OFFICE';

-- ============================================================
-- 8. Mark branches with existing coordinates as verified
-- ============================================================

UPDATE `branches` 
SET `location_verified` = 1 
WHERE `lat` IS NOT NULL 
  AND `long` IS NOT NULL 
  AND `lat` != '' 
  AND `long` != '';

-- ============================================================
-- 9. Create event for 90-day location_logs cleanup (optional)
-- ============================================================

-- Enable event scheduler if not already enabled
-- SET GLOBAL event_scheduler = ON;

-- Create cleanup event (runs daily at 3 AM)
-- DELIMITER //
-- CREATE EVENT IF NOT EXISTS `cleanup_location_logs`
-- ON SCHEDULE EVERY 1 DAY
-- STARTS CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 3 HOUR
-- DO
--   DELETE FROM `location_logs` 
--   WHERE `created_at` < DATE_SUB(NOW(), INTERVAL 90 DAY);
-- //
-- DELIMITER ;

-- ============================================================
-- Migration Complete
-- ============================================================

SELECT 'Migration complete! Summary:' AS message;

SELECT 
  COUNT(*) as total_branches,
  SUM(CASE WHEN lat IS NOT NULL AND lat != '' THEN 1 ELSE 0 END) as branches_with_coords,
  SUM(CASE WHEN geofence_radius_meters IS NOT NULL THEN 1 ELSE 0 END) as branches_with_radius
FROM `branches`;
