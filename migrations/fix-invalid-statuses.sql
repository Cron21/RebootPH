-- Fix invalid proposal statuses (empty strings)
-- This handles the case where proposals have empty Status values

-- First, update any proposals with empty Status to a default value
UPDATE proposal 
SET Status = 'Pending' 
WHERE Status = '' OR Status IS NULL;

-- Verify the changes
SELECT ProposalID, Title, Status 
FROM proposal 
WHERE Status = '' OR Status IS NULL;
