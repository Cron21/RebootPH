<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

session_start();

if (!isset($_SESSION['memberID'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$eventId = isset($_GET['eventId']) ? (int)$_GET['eventId'] : null;
$memberId = isset($_GET['memberId']) ? (int)$_GET['memberId'] : $_SESSION['memberID'];

if (!$eventId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Event ID is required']);
    exit;
}

try {
    // Check if member is registered for the event
    $stmt = $conn->prepare("
        SELECT 
            r.RegistrationID,
            r.RegistrationDate,
            e.EventID,
            e.status as EventStatus,
            p.Title as EventTitle,
            p.ProposedDate,
            p.StartTime,
            p.EndTime,
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
            'registered' => true,
            'registration' => $registration
        ]);
    } else {
        // Check if event exists and get details
        $eventStmt = $conn->prepare("
            SELECT 
                e.EventID,
                e.status as EventStatus,
                e.RegistrationDeadline,
                p.Title as EventTitle,
                p.ProposedDate,
                p.StartTime,
                p.EndTime,
                p.TargetParticipants as Capacity,
                COUNT(DISTINCT r2.RegistrationID) as RegisteredCount
            FROM event e
            JOIN proposal p ON e.ProposalID = p.ProposalID
            LEFT JOIN registration r2 ON e.EventID = r2.EventID
            WHERE e.EventID = ?
            GROUP BY e.EventID, e.status, e.RegistrationDeadline, p.Title, 
                     p.ProposedDate, p.StartTime, p.EndTime, p.TargetParticipants
        ");
        $eventStmt->execute([$eventId]);
        $event = $eventStmt->fetch(PDO::FETCH_ASSOC);

        if ($event) {
            // Check if registration deadline has passed
            $deadlinePassed = false;
            if ($event['RegistrationDeadline']) {
                $deadline = new DateTime($event['RegistrationDeadline']);
                $now = new DateTime();
                $deadlinePassed = $now > $deadline;
            }

            // Check if event is full
            $isFull = ($event['RegisteredCount'] >= $event['Capacity']);

            echo json_encode([
                'success' => true,
                'registered' => false,
                'event' => $event,
                'canRegister' => !$deadlinePassed && !$isFull,
                'deadlinePassed' => $deadlinePassed,
                'isFull' => $isFull
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Event not found']);
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

