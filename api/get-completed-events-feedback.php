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

$memberId = $_SESSION['memberID'];

try {
    // Get completed events that the member attended and can provide feedback for
    $stmt = $conn->prepare("
        SELECT 
            e.EventID,
            e.SerialNumber,
            p.Title,
            p.ProposedDate,
            p.StartTime,
            p.EndTime,
            p.Venue,
            ea.AttendanceID,
            ea.AttendanceTime,
            f.FeedbackID,
            f.Rating,
            f.Comments,
            f.SubmissionDate as FeedbackDate
        FROM registration r
        JOIN event e ON r.EventID = e.EventID
        JOIN proposal p ON e.ProposalID = p.ProposalID
        JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
        LEFT JOIN feedback f ON ea.AttendanceID = f.AttendanceID
        WHERE r.MemberID = ?
            AND e.status = 'Completed'
            AND p.Status = 'Approved'
        ORDER BY p.ProposedDate DESC, ea.AttendanceTime DESC
    ");
    
    $stmt->execute([$memberId]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the response
    foreach ($events as &$event) {
        $event['hasFeedback'] = !empty($event['FeedbackID']);
        $event['canProvideFeedback'] = true; // Can always provide/update feedback if attended
    }
    
    echo json_encode([
        'success' => true,
        'events' => $events,
        'count' => count($events)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

