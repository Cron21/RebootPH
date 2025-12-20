<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

session_start();

// Check if user is authenticated
if (!isset($_SESSION['memberID'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    // Total Members
    $membersStmt = $conn->prepare("SELECT COUNT(*) as count FROM member WHERE isActive = 1");
    $membersStmt->execute();
    $totalMembers = $membersStmt->fetch(PDO::FETCH_ASSOC)['count'];

    // New members this month
    $newMembersStmt = $conn->prepare("
        SELECT COUNT(*) as count FROM member 
        WHERE isActive = 1 AND MONTH(JoinDate) = MONTH(NOW()) AND YEAR(JoinDate) = YEAR(NOW())
    ");
    $newMembersStmt->execute();
    $newMembersMonth = $newMembersStmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Active Initiatives
    $initiativesStmt = $conn->prepare("
        SELECT COUNT(*) as count FROM initiatives 
        WHERE isHighlighted = 1
    ");
    $initiativesStmt->execute();
    $activeInitiatives = $initiativesStmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Pending Applications
    $applicationsStmt = $conn->prepare("
        SELECT COUNT(*) as count FROM application 
        WHERE ApplicationStatus = 0
    ");
    $applicationsStmt->execute();
    $pendingApplications = $applicationsStmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Event Proposals - awaiting approval
    $proposalsStmt = $conn->prepare("
        SELECT COUNT(*) as count FROM proposal 
        WHERE Status = 'Pending'
    ");
    $proposalsStmt->execute();
    $awaitingProposals = $proposalsStmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Recent Applications (last 5)
    $recentAppsStmt = $conn->prepare("
        SELECT 
            a.ApplicationID,
            a.FName,
            a.LName,
            a.ApplicantEmail,
            a.SubmissionDate
        FROM application a
        WHERE a.ApplicationStatus = 0
        ORDER BY a.SubmissionDate DESC
        LIMIT 5
    ");
    $recentAppsStmt->execute();
    $recentApplications = $recentAppsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Upcoming Events (next 5 events)
    $upcomingEventsStmt = $conn->prepare("
        SELECT 
            e.EventID,
            p.Title,
            p.ProposedDate,
            p.StartTime,
            p.Venue,
            p.TargetParticipants,
            COUNT(DISTINCT ea.MemberID) as registeredCount
        FROM event e
        LEFT JOIN proposal p ON e.ProposalID = p.ProposalID
        LEFT JOIN event_attendance ea ON e.EventID = ea.EventID
        WHERE p.ProposedDate >= NOW()
        GROUP BY e.EventID, p.Title, p.ProposedDate, p.StartTime, p.Venue, p.TargetParticipants
        ORDER BY p.ProposedDate ASC
        LIMIT 5
    ");
    $upcomingEventsStmt->execute();
    $upcomingEvents = $upcomingEventsStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'totalMembers' => $totalMembers,
        'newMembersMonth' => $newMembersMonth,
        'activeInitiatives' => $activeInitiatives,
        'pendingApplications' => $pendingApplications,
        'awaitingProposals' => $awaitingProposals,
        'recentApplications' => $recentApplications,
        'upcomingEvents' => $upcomingEvents
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>