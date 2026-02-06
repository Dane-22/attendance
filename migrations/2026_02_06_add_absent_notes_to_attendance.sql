-- Migration: Add absent_notes column to attendance table
ALTER TABLE attendance ADD COLUMN absent_notes TEXT NULL AFTER auto_absent_applied;
