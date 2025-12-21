-- Migration: Find and Fix Orphaned Events
-- This script identifies and helps clean up events that are causing issues

-- Step 1: Find duplicate events for the same proposal
-- (Should only have 1 event per proposal)
SELECT 
    ProposalID, 
    COUNT(*) as event_count,
    GROUP_CONCAT(EventID) as EventIDs
FROM event
GROUP BY ProposalID
HAVING COUNT(*) > 1;

-- Step 2: Find events with no corresponding proposal
SELECT e.EventID, e.ProposalID, p.ProposalID as proposal_exists
FROM event e
LEFT JOIN proposal p ON e.ProposalID = p.ProposalID
WHERE p.ProposalID IS NULL;

-- Step 3: Find events where the proposal is not 'Approved'
SELECT e.EventID, e.ProposalID, p.Title, p.Status
FROM event e
LEFT JOIN proposal p ON e.ProposalID = p.ProposalID
WHERE p.Status NOT IN ('Approved', 'Postponed', 'Moved');

-- Step 4: Delete duplicate events (keep the one with the lowest EventID)
-- WARNING: Only run this if Step 1 shows duplicates!
-- DELETE FROM event 
-- WHERE EventID NOT IN (
--     SELECT MIN(EventID) 
--     FROM (
--         SELECT e.EventID
--         FROM event e
--         GROUP BY e.ProposalID
--     ) AS subquery
-- );

-- Step 5: Delete orphaned events (with no valid proposal)
-- WARNING: Only run this if Step 2 shows orphaned events!
-- DELETE FROM event 
-- WHERE ProposalID NOT IN (
--     SELECT DISTINCT ProposalID FROM proposal
-- );

-- Step 6: Verify the Status column in proposal table has correct enum values
DESCRIBE proposal;

-- Should show: enum('Approved','Pending','Rejected','Postponed')
-- If not, you need to run the migration in add-postponed-status.sql
