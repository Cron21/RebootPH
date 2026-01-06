<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once 'config.php';

try {
    // Get non-member ID from query parameter
    $nonMemberId = (int)($_GET['id'] ?? 0);
    
    if ($nonMemberId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Non-member ID required']);
        exit;
    }
    
    // Verify non-member exists
    $nmStmt = $conn->prepare("SELECT non_memberID, Fname, Lname, Email FROM non_member WHERE non_memberID = ?");
    $nmStmt->execute([$nonMemberId]);
    $nonMember = $nmStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$nonMember) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Non-member not found']);
        exit;
    }
    
    // Get all events registered/attended by this non-member
    $stmt = $conn->prepare("
        SELECT 
            e.EventID,
            e.SerialNumber,
            e.status as EventStatus,
            p.Title,
            p.ProposedDate,
            p.StartTime,
            p.EndTime,
            p.Venue,
            p.Description,
            r.RegistrationID,
            r.RegistrationType,
            r.RegistrationDate,
            CASE WHEN ea.AttendanceID IS NOT NULL THEN 1 ELSE 0 END as HasAttended,
            ea.AttendanceTime,
            CASE WHEN f.FeedbackID IS NOT NULL THEN 1 ELSE 0 END as HasFeedback,
            f.Rating,
            f.Comments
        FROM registration r
        JOIN event e ON r.EventID = e.EventID
        JOIN proposal p ON e.ProposalID = p.ProposalID
        LEFT JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
        LEFT JOIN feedback f ON ea.AttendanceID = f.AttendanceID
        WHERE r.non_MemberID = ?
        ORDER BY p.ProposedDate DESC
    ");
    
    $stmt->execute([$nonMemberId]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format response
    $formattedEvents = [];
    foreach ($events as $event) {
        $formattedEvents[] = [
            'EventID' => (int)$event['EventID'],
            'Title' => $event['Title'],
            'ProposedDate' => $event['ProposedDate'],
            'StartTime' => $event['StartTime'],
            'EndTime' => $event['EndTime'],
            'Venue' => $event['Venue'],
            'Description' => $event['Description'],
            'SerialNumber' => $event['SerialNumber'],
            'EventStatus' => $event['EventStatus'],
            'RegistrationType' => $event['RegistrationType'],
            'RegistrationDate' => $event['RegistrationDate'],
            'HasAttended' => (int)$event['HasAttended'],
            'AttendanceTime' => $event['AttendanceTime'],
            'HasFeedback' => (int)$event['HasFeedback'],
            'Rating' => $event['Rating'],
            'Comments' => $event['Comments']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'nonMember' => [
            'non_memberID' => (int)$nonMember['non_memberID'],
            'Fname' => $nonMember['Fname'],
            'Lname' => $nonMember['Lname'],
            'Email' => $nonMember['Email']
        ],
        'events' => $formattedEvents
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
