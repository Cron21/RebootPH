<?php
header('Content-Type: application/json');

require_once 'config.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Proposal ID is required']);
    exit;
}

$proposalId = (int)$_GET['id'];

try {
    $stmt = $conn->prepare("
        SELECT 
            p.ProposalID,
            p.Title,
            p.SubmittedByMemberID,
            p.Description,
            p.ProposedDate,
            p.StartTime,
            p.EndTime,
            p.Venue,
            p.TargetParticipants,
            p.EventType,
            p.BudgetEstimate,
            p.StaffRequired,
            p.EquipmentNeeded,
            p.Objectives,
            p.Department,
            p.PartnersSponsor,
            p.AdditionalNotes,
            p.Status,
            p.SubmissionDate,
            p.ReviewByAdminID,
            p.ReviewDate,
            a.FName,
            a.LName,
            a.ApplicantEmail,
            ra.FName as ReviewerFirstName,
            ra.LName as ReviewerLastName
        FROM proposal p
        LEFT JOIN member m ON p.SubmittedByMemberID = m.MemberID
        LEFT JOIN application a ON m.ApplicationID = a.ApplicationID
        LEFT JOIN member rm ON p.ReviewByAdminID = rm.MemberID
        LEFT JOIN application ra ON rm.ApplicationID = ra.ApplicationID
        WHERE p.ProposalID = ?
    ");
    $stmt->execute([$proposalId]);
    $proposal = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$proposal) {
        throw new Exception('Proposal not found');
    }

    echo json_encode([
        'success' => true,
        'proposal' => $proposal
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>