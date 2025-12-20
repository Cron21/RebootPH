<?php
/**
 * Migration script to add UNIQUE constraint on ApplicationID in member table
 * This prevents duplicate members from being created for the same application
 * Run once to ensure data integrity
 */

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if constraint already exists
    $checkConstraintStmt = $pdo->prepare("
        SELECT CONSTRAINT_NAME 
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE TABLE_NAME = 'member' 
        AND COLUMN_NAME = 'ApplicationID' 
        AND CONSTRAINT_NAME != 'PRIMARY'
        AND TABLE_SCHEMA = ?
    ");
    $checkConstraintStmt->execute([$dbname]);
    $constraintExists = $checkConstraintStmt->fetch();

    if ($constraintExists) {
        echo json_encode([
            'success' => true,
            'message' => 'Unique constraint on ApplicationID already exists.'
        ]);
        exit;
    }

    // First, identify and remove duplicate members (keep the oldest one)
    $findDuplicatesStmt = $pdo->prepare("
        SELECT ApplicationID, COUNT(*) as count 
        FROM member 
        WHERE ApplicationID IS NOT NULL 
        GROUP BY ApplicationID 
        HAVING count > 1
    ");
    $findDuplicatesStmt->execute();
    $duplicates = $findDuplicatesStmt->fetchAll();

    if (!empty($duplicates)) {
        echo "Found " . count($duplicates) . " duplicate(s). Cleaning up...\n";
        
        foreach ($duplicates as $dup) {
            $appId = $dup['ApplicationID'];
            
            // Keep the first (oldest) member, delete the rest
            $deleteStmt = $pdo->prepare("
                DELETE FROM member 
                WHERE ApplicationID = ? 
                AND MemberID NOT IN (
                    SELECT MemberID FROM (
                        SELECT MemberID FROM member 
                        WHERE ApplicationID = ? 
                        ORDER BY JoinDate ASC 
                        LIMIT 1
                    ) as oldest
                )
            ");
            $deleteStmt->execute([$appId, $appId]);
            echo "Cleaned up duplicates for ApplicationID $appId\n";
        }
    }

    // Now add the unique constraint
    $addConstraintStmt = $pdo->prepare("
        ALTER TABLE member 
        ADD UNIQUE KEY uk_application_id (ApplicationID)
    ");
    $addConstraintStmt->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Successfully added UNIQUE constraint on ApplicationID in member table. Duplicate records removed.'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
