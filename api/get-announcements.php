<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

try {
    $announcementId = $_GET['id'] ?? null;

    if ($announcementId) {
        // Fetch single announcement with proposal data
        $stmt = $conn->prepare("
            SELECT 
                ann.AnnouncementID,
                ann.ProposalID,
                ann.IsPriority,
                ann.CreatedByAdminID,
                ann.LastModifiedBy,
                ann.LastModifiedDate,
                p.Title,
                p.Description,
                p.ProposedDate,
                p.StartTime,
                p.EndTime,
                p.Venue,
                p.Status,
                p.ReviewDate,
                p.StaffRequired,
                p.TargetParticipants,
                a.FName,
                a.LName,
                COALESCE((SELECT COUNT(DISTINCT r.RegistrationID)
                 FROM registration r
                 JOIN event e ON r.EventID = e.EventID
                 WHERE e.ProposalID = p.ProposalID), 0) as StaffRegistered,
                COALESCE((SELECT COUNT(DISTINCT r.RegistrationID)
                 FROM registration r
                 JOIN event e ON r.EventID = e.EventID
                 WHERE e.ProposalID = p.ProposalID), 0) as RegisteredCount
            FROM announcement ann
            LEFT JOIN proposal p ON ann.ProposalID = p.ProposalID
            LEFT JOIN member m ON p.SubmittedByMemberID = m.MemberID
            LEFT JOIN application a ON m.ApplicationID = a.ApplicationID
            WHERE ann.AnnouncementID = ?
        ");
        $stmt->execute([$announcementId]);
    } else {
        // Fetch all announcements with proposal data, ordered by priority and date
        $stmt = $conn->prepare("
            SELECT 
                ann.AnnouncementID,
                ann.ProposalID,
                ann.IsPriority,
                ann.CreatedByAdminID,
                ann.LastModifiedBy,
                ann.LastModifiedDate,
                p.Title,
                p.Description,
                p.ProposedDate,
                p.StartTime,
                p.EndTime,
                p.Venue,
                p.Status,
                p.ReviewDate,
                p.StaffRequired,
                p.TargetParticipants,
                a.FName,
                a.LName,
                COALESCE((SELECT COUNT(DISTINCT r.RegistrationID)
                 FROM registration r
                 JOIN event e ON r.EventID = e.EventID
                 WHERE e.ProposalID = p.ProposalID), 0) as StaffRegistered,
                COALESCE((SELECT COUNT(DISTINCT r.RegistrationID)
                 FROM registration r
                 JOIN event e ON r.EventID = e.EventID
                 WHERE e.ProposalID = p.ProposalID), 0) as RegisteredCount
            FROM announcement ann
            LEFT JOIN proposal p ON ann.ProposalID = p.ProposalID
            LEFT JOIN member m ON p.SubmittedByMemberID = m.MemberID
            LEFT JOIN application a ON m.ApplicationID = a.ApplicationID
            ORDER BY ann.IsPriority DESC, p.ReviewDate DESC
        ");
        $stmt->execute();
    }

    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ensure numeric values are properly cast
    foreach ($announcements as &$announcement) {
        $announcement['StaffRegistered'] = (int)($announcement['StaffRegistered'] ?? 0);
        $announcement['RegisteredCount'] = (int)($announcement['RegisteredCount'] ?? 0);
        $announcement['StaffRequired'] = (int)($announcement['StaffRequired'] ?? 0);
        $announcement['TargetParticipants'] = (int)($announcement['TargetParticipants'] ?? 0);
    }
    unset($announcement); // Break reference

    echo json_encode([
        'success' => true,
        'announcements' => $announcements,
        'count' => count($announcements)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>