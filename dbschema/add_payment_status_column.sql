-- Add payment_status column to weekly_payroll_reports table
ALTER TABLE `weekly_payroll_reports` 
ADD COLUMN `payment_status` enum('Paid','Not Paid') DEFAULT 'Not Paid' 
COMMENT 'Employee salary payment status' 
AFTER `status`;
