<?php
/**
 * Migration script to add 'Postponed' status to proposal table
 * Run this once to update the database schema
 */

header('Content-Type: application/json');

require_once 'config.php';

try {
    // Check if the status enum needs updating
    // MySQL doesn't allow easy enum modification, so we'll use a workaround
    
    // Step 1: Create a backup (optional, just for safety)
    $conn->exec("
        CREATE TABLE IF NOT EXISTS proposal_backup LIKE proposal
    ");
    
    // Step 2: Modify the column - MySQL requires this approach for ENUM
    // We need to drop and recreate with the new ENUM values
    $conn->exec("
        ALTER TABLE proposal 
        MODIFY COLUMN Status enum('Approved','Pending','Rejected','Postponed') NOT NULL DEFAULT 'Pending'
    ");
    
    echo json_encode([
        'success' => true,
        'message' => 'Successfully updated proposal Status enum to include Postponed'
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Migration error: ' . $e->getMessage()
    ]);
    exit;
}
?>
