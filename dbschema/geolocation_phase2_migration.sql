-- ============================================================
-- GeoLocator Implementation - Phase 2 Migration
-- Hard Enforcement, Accuracy Flagging, Security Enhancements
-- Created: March 31, 2026
-- ============================================================

-- Use the attendance database
USE `attendance_db`;

-- ============================================================
-- 1. Add accuracy flagging to attendance table
-- ============================================================

-- Add flagged_accuracy column to attendance table
ALTER TABLE `attendance` 
ADD COLUMN `flagged_accuracy` TINYINT(1) DEFAULT 0 
AFTER `location_accuracy`;

-- Add location_timestamp column for server-side validation
ALTER TABLE `attendance` 
ADD COLUMN `location_timestamp` TIMESTAMP NULL 
AFTER `flagged_accuracy`;

-- Add geofence_violation_count for tracking violations
ALTER TABLE `attendance` 
ADD COLUMN `geofence_violation_count` INT DEFAULT 0 
AFTER `location_timestamp`;

-- Add override_reason for manager bypass
ALTER TABLE `attendance` 
ADD COLUMN `override_reason` VARCHAR(255) NULL 
AFTER `geofence_violation_count`;

-- Add override_approved_by for audit trail
ALTER TABLE `attendance` 
ADD COLUMN `override_approved_by` INT NULL 
AFTER `override_reason`;

-- Add override_approved_at timestamp
ALTER TABLE `attendance` 
ADD COLUMN `override_approved_at` TIMESTAMP NULL 
AFTER `override_approved_by`;

-- ============================================================
-- 2. Update location_logs table with Phase 2 enhancements
-- ============================================================

-- Add accuracy flagging
ALTER TABLE `location_logs` 
ADD COLUMN `flagged_accuracy` TINYINT(1) DEFAULT 0 
AFTER `validation_failure_reason`;

-- Add GPS timestamp for spoofing detection
ALTER TABLE `location_logs` 
ADD COLUMN `gps_timestamp` TIMESTAMP NULL 
AFTER `flagged_accuracy`;

-- Add server timestamp comparison
ALTER TABLE `location_logs` 
ADD COLUMN `server_timestamp_diff` INT NULL 
COMMENT='Difference in seconds between GPS timestamp and server time' 
AFTER `gps_timestamp`;

-- Add geofence violation tracking
ALTER TABLE `location_logs` 
ADD COLUMN `is_geofence_violation` TINYINT(1) DEFAULT 0 
AFTER `server_timestamp_diff`;

-- Add manager override tracking
ALTER TABLE `location_logs` 
ADD COLUMN `override_reason` VARCHAR(255) NULL 
AFTER `is_geofence_violation`;

-- Add device fingerprint for security
ALTER TABLE `location_logs` 
ADD COLUMN `device_fingerprint` VARCHAR(128) NULL 
AFTER `device_info`;

-- ============================================================
-- 3. Create geofence_violations table for tracking
-- ============================================================

CREATE TABLE IF NOT EXISTS `geofence_violations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `branch_id` INT NOT NULL,
  `attendance_id` INT NULL,
  `location_log_id` INT NULL,
  `violation_date` DATE NOT NULL,
  `violation_time` TIME NOT NULL,
  `latitude` DECIMAL(10, 8) NOT NULL,
  `longitude` DECIMAL(11, 8) NOT NULL,
  `distance_from_branch` INT NOT NULL,
  `geofence_radius` INT NOT NULL,
  `accuracy_meters` FLOAT NULL,
  `violation_count` INT DEFAULT 1,
  `is_flagged_accuracy` TINYINT(1) DEFAULT 0,
  `device_info` VARCHAR(255) NULL,
  `ip_address` VARCHAR(45) NULL,
  `status` ENUM('active', 'resolved', 'ignored') DEFAULT 'active',
  `resolved_by` INT NULL,
  `resolved_at` TIMESTAMP NULL,
  `resolution_notes` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_employee_violations` (`employee_id`, `violation_date`),
  INDEX `idx_branch_violations` (`branch_id`, `violation_date`),
  INDEX `idx_attendance_id` (`attendance_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Track geofence violations for security monitoring';

-- ============================================================
-- 4. Create manager_overrides table for audit trail
-- ============================================================

CREATE TABLE IF NOT EXISTS `manager_overrides` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL COMMENT='Employee being overridden',
  `manager_id` INT NOT NULL COMMENT='Manager approving override',
  `attendance_id` INT NULL,
  `location_log_id` INT NULL,
  `branch_id` INT NOT NULL,
  `override_type` ENUM('geofence_violation', 'accuracy_flag', 'timestamp_mismatch') NOT NULL,
  `override_reason` VARCHAR(255) NOT NULL,
  `original_distance` INT NULL,
  `geofence_radius` INT NULL,
  `latitude` DECIMAL(10, 8) NOT NULL,
  `longitude` DECIMAL(11, 8) NOT NULL,
  `ip_address` VARCHAR(45) NULL,
  `device_info` VARCHAR(255) NULL,
  `status` ENUM('approved', 'rejected', 'pending') DEFAULT 'approved',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_employee_overrides` (`employee_id`, `created_at`),
  INDEX `idx_manager_overrides` (`manager_id`, `created_at`),
  INDEX `idx_attendance_id` (`attendance_id`),
  INDEX `idx_override_type` (`override_type`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Audit trail for manager override approvals';

-- ============================================================
-- 5. Add geofence violation tracking to employees table
-- ============================================================

-- Add violation tracking columns
ALTER TABLE `employees` 
ADD COLUMN `geofence_violation_count` INT DEFAULT 0 
AFTER `updated_at`;

-- Add last violation date
ALTER TABLE `employees` 
ADD COLUMN `last_geofence_violation` DATE NULL 
AFTER `geofence_violation_count`;

-- Add violation status flag
ALTER TABLE `employees` 
ADD COLUMN `violation_flag` TINYINT(1) DEFAULT 0 
AFTER `last_geofence_violation`;

-- ============================================================
-- 6. Create stored procedures for violation tracking
-- ============================================================

DELIMITER //

-- Procedure to log geofence violation
CREATE PROCEDURE `LogGeofenceViolation`(
    IN p_employee_id INT,
    IN p_branch_id INT,
    IN p_attendance_id INT,
    IN p_location_log_id INT,
    IN p_latitude DECIMAL(10,8),
    IN p_longitude DECIMAL(11,8),
    IN p_distance INT,
    IN p_radius INT,
    IN p_accuracy FLOAT,
    IN p_device_info VARCHAR(255),
    IN p_ip_address VARCHAR(45)
)
BEGIN
    DECLARE v_violation_count INT DEFAULT 0;
    DECLARE v_today DATE DEFAULT CURDATE();
    
    -- Check if employee already has violations today
    SELECT COUNT(*) INTO v_violation_count
    FROM geofence_violations
    WHERE employee_id = p_employee_id 
    AND violation_date = v_today 
    AND status = 'active';
    
    -- Insert new violation record
    INSERT INTO geofence_violations (
        employee_id, branch_id, attendance_id, location_log_id,
        violation_date, violation_time, latitude, longitude,
        distance_from_branch, geofence_radius, accuracy_meters,
        violation_count, is_flagged_accuracy, device_info, ip_address
    ) VALUES (
        p_employee_id, p_branch_id, p_attendance_id, p_location_log_id,
        v_today, CURTIME(), p_latitude, p_longitude,
        p_distance, p_radius, p_accuracy,
        v_violation_count + 1, (p_accuracy > 100), p_device_info, p_ip_address
    );
    
    -- Update employee violation count
    UPDATE employees 
    SET geofence_violation_count = geofence_violation_count + 1,
        last_geofence_violation = v_today,
        violation_flag = CASE WHEN (v_violation_count + 1) >= 3 THEN 1 ELSE violation_flag END
    WHERE id = p_employee_id;
    
    -- Trigger notification if 3+ violations
    IF (v_violation_count + 1) >= 3 THEN
        INSERT INTO admin_notifications (
            type, message, employee_id, branch_id, 
            created_at, status
        ) VALUES (
            'Geofence Violation', 
            CONCAT('Employee has ', (v_violation_count + 1), ' geofence violations today'),
            p_employee_id, p_branch_id,
            NOW(), 'unread'
        );
    END IF;
END //

-- Procedure to log manager override
CREATE PROCEDURE `LogManagerOverride`(
    IN p_employee_id INT,
    IN p_manager_id INT,
    IN p_attendance_id INT,
    IN p_location_log_id INT,
    IN p_branch_id INT,
    IN p_override_type VARCHAR(50),
    IN p_override_reason VARCHAR(255),
    IN p_latitude DECIMAL(10,8),
    IN p_longitude DECIMAL(11,8),
    IN p_distance INT,
    IN p_radius INT,
    IN p_device_info VARCHAR(255),
    IN p_ip_address VARCHAR(45)
)
BEGIN
    -- Insert override record
    INSERT INTO manager_overrides (
        employee_id, manager_id, attendance_id, location_log_id,
        branch_id, override_type, override_reason,
        original_distance, geofence_radius, latitude, longitude,
        device_info, ip_address
    ) VALUES (
        p_employee_id, p_manager_id, p_attendance_id, p_location_log_id,
        p_branch_id, p_override_type, p_override_reason,
        p_distance, p_radius, p_latitude, p_longitude,
        p_device_info, p_ip_address
    );
    
    -- Log activity
    INSERT INTO activity_logs (
        user_id, action, details, ip_address, created_at
    ) VALUES (
        p_manager_id,
        'Geofence Override',
        CONCAT('Approved override for employee ', p_employee_id, ': ', p_override_reason),
        p_ip_address,
        NOW()
    );
END //

DELIMITER ;

-- ============================================================
-- 7. Create indexes for performance
-- ============================================================

-- Add composite indexes for common queries
ALTER TABLE `location_logs` 
ADD INDEX `idx_employee_location_time` (`employee_id`, `created_at`, `is_validated`);

ALTER TABLE `attendance` 
ADD INDEX `idx_location_accuracy` (`flagged_accuracy`, `attendance_date`);

ALTER TABLE `geofence_violations` 
ADD INDEX `idx_employee_date_status` (`employee_id`, `violation_date`, `status`);

-- ============================================================
-- 8. Update existing data for Phase 2
-- ============================================================

-- Set default location_timestamp for existing records
UPDATE `attendance` 
SET `location_timestamp` = `created_at` 
WHERE `location_timestamp` IS NULL 
AND (`clock_in_lat` IS NOT NULL OR `clock_out_lat` IS NOT NULL);

-- Flag existing records with poor accuracy
UPDATE `attendance` 
SET `flagged_accuracy` = 1 
WHERE `location_accuracy` > 100 
OR `location_accuracy` IS NULL;

-- ============================================================
-- Migration Complete
-- ============================================================

SELECT 'Phase 2 Migration complete! Summary:' AS message;

SELECT 
  'attendance' as table_name,
  COUNT(*) as total_records,
  SUM(CASE WHEN flagged_accuracy = 1 THEN 1 ELSE 0 END) as flagged_accuracy_count,
  SUM(CASE WHEN location_timestamp IS NOT NULL THEN 1 ELSE 0 END) as timestamped_count
FROM attendance

UNION ALL

SELECT 
  'location_logs' as table_name,
  COUNT(*) as total_records,
  SUM(CASE WHEN flagged_accuracy = 1 THEN 1 ELSE 0 END) as flagged_accuracy_count,
  SUM(CASE WHEN gps_timestamp IS NOT NULL THEN 1 ELSE 0 END) as timestamped_count
FROM location_logs

UNION ALL

SELECT 
  'employees' as table_name,
  COUNT(*) as total_records,
  SUM(geofence_violation_count) as total_violations,
  SUM(CASE WHEN violation_flag = 1 THEN 1 ELSE 0 END) as flagged_employees
FROM employees;
