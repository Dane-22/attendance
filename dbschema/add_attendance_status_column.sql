-- Migration: Add status column to attendance table
-- This fixes the "Unknown column 'a.status' in 'WHERE'" error

-- First check if column exists, then add if not
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'attendance' 
    AND COLUMN_NAME = 'status'
);

-- Add status column if it doesn't exist
DELIMITER //
CREATE PROCEDURE AddAttendanceStatusColumn()
BEGIN
    IF @column_exists = 0 THEN
        ALTER TABLE `attendance` 
        ADD COLUMN `status` ENUM('Present','Late','Absent','System') 
        CHARACTER SET utf8mb4 
        COLLATE utf8mb4_unicode_ci 
        DEFAULT NULL 
        AFTER `employee_id`;
        
        SELECT 'Status column added successfully' AS result;
    ELSE
        SELECT 'Status column already exists' AS result;
    END IF;
END //
DELIMITER ;

CALL AddAttendanceStatusColumn();
DROP PROCEDURE IF EXISTS AddAttendanceStatusColumn;

-- Also ensure other required columns exist (for reference)
-- These should already exist from the main schema
SHOW COLUMNS FROM `attendance`;
