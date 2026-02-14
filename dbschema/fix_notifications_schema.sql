-- Fix employee_notifications table to support cash advance notifications

-- 1. Make overtime_request_id nullable (since cash advances don't have overtime_request_id)
ALTER TABLE `employee_notifications` 
MODIFY COLUMN `overtime_request_id` int DEFAULT NULL;

-- 2. Update notification_type ENUM to include cash advance types
-- First convert to VARCHAR to allow new values, then we can use check constraints if needed
ALTER TABLE `employee_notifications` 
MODIFY COLUMN `notification_type` varchar(50) NOT NULL;

-- 3. Ensure cash_advance_id column exists (already there based on the schema shown)
-- If it doesn't exist, uncomment the line below:
-- ALTER TABLE `employee_notifications` ADD COLUMN `cash_advance_id` int DEFAULT NULL;

-- 4. Add index for cash_advance_id for faster lookups
ALTER TABLE `employee_notifications` 
ADD KEY `cash_advance_id` (`cash_advance_id`);
