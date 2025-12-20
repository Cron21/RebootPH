<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

session_start();

// Check if user is authenticated
if (!isset($_SESSION['memberID'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$eventId = isset($_GET['eventId']) ? (int)$_GET['eventId'] : null;
$memberId = isset($_GET['memberId']) ? (int)$_GET['memberId'] : $_SESSION['memberID'];

try {
    if ($eventId) {
        // Get registration for specific event
        $stmt = $conn->prepare("
            SELECT 
                r.RegistrationID,
                r.MemberID,
                r.EventID,
                r.RegistrationDate,
                e.SerialNumber,
                e.status as EventStatus,
                p.Title as EventTitle,
                p.ProposedDate,
                p.StartTime,
                p.EndTime,
                p.Venue,
                CASE 
                    WHEN ea.AttendanceID IS NOT NULL THEN 1
                    ELSE 0
                END as HasAttended,
                ea.AttendanceTime
            FROM registration r
            JOIN event e ON r.EventID = e.EventID
            JOIN proposal p ON e.ProposalID = p.ProposalID
            LEFT JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            WHERE r.MemberID = ? AND r.EventID = ?
            LIMIT 1
        ");
        $stmt->execute([$memberId, $eventId]);
        $registration = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($registration) {
            echo json_encode([
                'success' => true,
                'registration' => $registration
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'registration' => null,
                'message' => 'Not registered for this event'
            ]);
        }
    } else {
        // Get all registrations for member
        $stmt = $conn->prepare("
            SELECT 
                r.RegistrationID,
                r.MemberID,
                r.EventID,
                r.RegistrationDate,
                e.SerialNumber,
                e.status as EventStatus,
                p.Title as EventTitle,
                p.ProposedDate,
                p.StartTime,
                p.EndTime,
                p.Venue,
                CASE 
                    WHEN ea.AttendanceID IS NOT NULL THEN 1
                    ELSE 0
                END as HasAttended,
                ea.AttendanceTime
            FROM registration r
            JOIN event e ON r.EventID = e.EventID
            JOIN proposal p ON e.ProposalID = p.ProposalID
            LEFT JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            WHERE r.MemberID = ?
            ORDER BY p.ProposedDate DESC
        ");
        $stmt->execute([$memberId]);
        $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'registrations' => $registrations,
            'count' => count($registrations)
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
