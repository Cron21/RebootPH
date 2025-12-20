<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

session_start();

try {
    // Get all event proposals
    $stmt = $conn->prepare("
        SELECT 
            p.ProposalID,
            p.Title,
            p.SubmittedByMemberID,
            p.Description,
            p.ProposedDate,
            p.Venue,
            p.TargetParticipants,
            p.Status,
            p.SubmissionDate,
            p.ReviewByAdminID,
            p.ReviewDate,
            a.FName,
            a.LName
        FROM proposal p
        LEFT JOIN member m ON p.SubmittedByMemberID = m.MemberID
        LEFT JOIN application a ON m.ApplicationID = a.ApplicationID
        ORDER BY p.SubmissionDate DESC
    ");
    $stmt->execute();
    $proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'proposals' => $proposals,
        'count' => count($proposals)
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>