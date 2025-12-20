<?php
header('Content-Type: application/json');
session_start();

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['applicationId'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Application ID is required']);
    exit;
}

$applicationId = (int)$input['applicationId'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Start transaction to ensure all or nothing deletion
    $pdo->beginTransaction();

    // 1. Delete from applicationanswer table (answers to questionnaire)
    $stmt = $pdo->prepare("DELETE FROM applicationanswer WHERE ApplicationID = ?");
    $stmt->execute([$applicationId]);

    // 2. Get the member ID if this application is approved and has a member record
    $memberStmt = $pdo->prepare("SELECT MemberID FROM member WHERE ApplicationID = ?");
    $memberStmt->execute([$applicationId]);
    $memberResult = $memberStmt->fetch(PDO::FETCH_ASSOC);

    // 3. Delete from member table if exists
    if ($memberResult) {
        $memberId = $memberResult['MemberID'];
        
        // Delete any member-related records first (events, feedback, etc.)
        // These are typically handled by foreign keys with CASCADE, but we'll be explicit
        
        $memberDeleteStmt = $pdo->prepare("DELETE FROM member WHERE MemberID = ?");
        $memberDeleteStmt->execute([$memberId]);
    }

    // 4. Delete from application table
    $appStmt = $pdo->prepare("DELETE FROM application WHERE ApplicationID = ?");
    $appStmt->execute([$applicationId]);

    // Commit the transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Application and all related records deleted successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting application: ' . $e->getMessage()
    ]);
}
?>
