-- Fix view_type enum to include 'range'
ALTER TABLE weekly_payroll_reports 
MODIFY COLUMN view_type ENUM('weekly', 'monthly', 'range') DEFAULT 'weekly';

-- Also fix the summary_reports table if it exists
ALTER TABLE summary_reports 
MODIFY COLUMN view_type ENUM('weekly', 'monthly', 'range') DEFAULT 'weekly';
