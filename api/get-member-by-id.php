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
    
    // Fetch member details by joining member and application tables
    $stmt = $conn->prepare("
        SELECT 
            m.MemberID,
            a.FName AS FirstName,
            a.LName AS LastName,
            a.ApplicantEmail AS EmailAddress,
            a.Phone AS PhoneNumber,
            m.Role AS Position,
            'Reboot Philippines' AS Organization,
            CASE WHEN m.isActive = 1 THEN 'Active' ELSE 'Inactive' END AS MembershipStatus,
            m.JoinDate,
            m.ProfileImage
        FROM member m
        LEFT JOIN application a ON m.ApplicationID = a.ApplicationID
        WHERE m.MemberID = ?
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
