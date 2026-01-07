<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Get member ID from request
    $memberId = $_GET['id'] ?? null;
    
    if (!$memberId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Member ID is required']);
        exit;
    }
    
    // Remove the "RPH-" prefix if present
    if (strpos($memberId, 'RPH-') === 0) {
        $memberId = substr($memberId, 4);
    }
    
    // Fetch member details
    $stmt = $conn->prepare("
        SELECT 
            MemberID,
            FirstName,
            LastName,
            EmailAddress,
            PhoneNumber,
            Position,
            Organization,
            MembershipStatus,
            JoinDate,
            ProfileImage
        FROM member 
        WHERE MemberID = ?
    ");
    
    $stmt->execute([$memberId]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$member) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Member not found']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $member
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching member data: ' . $e->getMessage()
    ]);
}
?>
