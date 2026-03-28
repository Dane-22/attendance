-- Add leave_request_id column to employee_notifications table
-- This column links notifications to leave requests

ALTER TABLE `employee_notifications` 
ADD COLUMN `leave_request_id` int DEFAULT NULL AFTER `cash_advance_id`,
ADD KEY `leave_request_id` (`leave_request_id`);
