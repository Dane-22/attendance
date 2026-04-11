-- Migration: Add sss_loan column to employees table
-- This makes SSS loan permanent (like performance_allowance) but still editable per report

ALTER TABLE employees
ADD COLUMN sss_loan DECIMAL(10,2) DEFAULT 0.00
AFTER performance_allowance;
