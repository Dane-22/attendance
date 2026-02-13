-- Add requested_by_user_id column to overtime_requests table
-- Run this if you get "Unknown column 'requested_by_user_id'" error

ALTER TABLE overtime_requests 
ADD COLUMN IF NOT EXISTS requested_by_user_id INT NULL AFTER requested_by,
ADD INDEX IF NOT EXISTS idx_requested_by_user (requested_by_user_id);
