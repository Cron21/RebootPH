-- Migration: Add 'Postponed' status to proposal table
-- This script updates the Status enum column to include the 'Postponed' value
-- Run this in phpMyAdmin or your MySQL client

-- Step 1: Modify the Status column to include 'Postponed'
ALTER TABLE proposal 
MODIFY COLUMN Status enum('Approved','Pending','Rejected','Postponed') NOT NULL DEFAULT 'Pending';

-- Verify the change
SELECT COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'proposal' AND COLUMN_NAME = 'Status';
