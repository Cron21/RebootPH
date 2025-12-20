-- Check and verify the database trigger that creates member records
-- This script helps verify that newly approved applicants get 'Member Staff' as their default role

-- 1. View the current trigger definition
SELECT TRIGGER_SCHEMA, TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE, ACTION_STATEMENT
FROM INFORMATION_SCHEMA.TRIGGERS
WHERE TRIGGER_SCHEMA = DATABASE()
AND EVENT_OBJECT_TABLE = 'application'
AND EVENT_MANIPULATION = 'UPDATE';

-- 2. If the trigger doesn't exist or needs updating, create/recreate it with proper role assignment:
-- DELIMITER $$
-- 
-- CREATE TRIGGER application_approval_trigger
-- AFTER UPDATE ON application
-- FOR EACH ROW
-- BEGIN
--     IF NEW.ApplicationStatus = 1 AND OLD.ApplicationStatus != 1 THEN
--         INSERT INTO member (ApplicationID, Role, isActive, JoinDate)
--         VALUES (NEW.ApplicationID, 'Member Staff', 1, NOW());
--     END IF;
-- END$$
-- 
-- DELIMITER ;

-- 3. Test the trigger by checking recently created members
SELECT m.MemberID, m.ApplicationID, m.Role, m.JoinDate, a.FName, a.ApplicantEmail
FROM member m
JOIN application a ON m.ApplicationID = a.ApplicationID
WHERE m.JoinDate >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY m.JoinDate DESC;

-- 4. Verify all members have the correct roles (should see 'Member Staff' for most recent approvals)
SELECT Role, COUNT(*) as count
FROM member
GROUP BY Role;
