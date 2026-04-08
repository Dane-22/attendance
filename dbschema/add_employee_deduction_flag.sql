-- Migration: Add has_deduction column to employees table
-- Date: April 8, 2026
-- Purpose: Allow marking specific employees as excluded from government deductions

-- Add the column with default value of 1 (has deductions)
ALTER TABLE employees 
ADD COLUMN has_deduction TINYINT(1) NOT NULL DEFAULT 1 
COMMENT 'Whether employee is subject to SSS/PhilHealth/PagIBIG deductions (1=yes, 0=no)';

-- Create index for performance when filtering
CREATE INDEX idx_has_deduction ON employees(has_deduction);

-- Verify the column was added
DESCRIBE employees;

-- Optional: Set specific employees to no deduction (uncomment and customize as needed)
-- UPDATE employees SET has_deduction = 0 WHERE employee_code IN ('EMP001', 'EMP002');
