-- Add is_manual_adjustment column to daily_payroll_reports
-- This flag prevents attendance sync from overwriting manual changes

ALTER TABLE daily_payroll_reports 
ADD COLUMN IF NOT EXISTS is_manual_adjustment TINYINT(1) DEFAULT 0;

-- Create index for faster queries
CREATE INDEX IF NOT EXISTS idx_manual_adjustment ON daily_payroll_reports(is_manual_adjustment);
