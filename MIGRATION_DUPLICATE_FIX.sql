-- SQL Migration Script to fix duplicate members
-- Run this in phpMyAdmin or your database tool

-- Step 1: Identify duplicates (view only)
SELECT ApplicationID, COUNT(*) as count, GROUP_CONCAT(MemberID) as member_ids
FROM member 
WHERE ApplicationID IS NOT NULL 
GROUP BY ApplicationID 
HAVING count > 1;

-- Step 2: Keep the oldest member record, delete duplicates
DELETE FROM member 
WHERE MemberID IN (
    SELECT MemberID FROM (
        SELECT m1.MemberID 
        FROM member m1
        INNER JOIN (
            SELECT ApplicationID 
            FROM member 
            WHERE ApplicationID IS NOT NULL 
            GROUP BY ApplicationID 
            HAVING COUNT(*) > 1
        ) dup ON m1.ApplicationID = dup.ApplicationID
        WHERE m1.MemberID NOT IN (
            SELECT MemberID FROM (
                SELECT MIN(MemberID) as MemberID
                FROM member 
                WHERE ApplicationID IS NOT NULL 
                GROUP BY ApplicationID 
            ) as oldest
        )
    ) as to_delete
);

-- Step 3: Add UNIQUE constraint on ApplicationID to prevent future duplicates
ALTER TABLE member 
ADD UNIQUE KEY uk_application_id (ApplicationID);

-- Verification: Check no more duplicates exist
SELECT ApplicationID, COUNT(*) as count
FROM member 
WHERE ApplicationID IS NOT NULL 
GROUP BY ApplicationID 
HAVING count > 1;
