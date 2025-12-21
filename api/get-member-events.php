<?php
header('Content-Type: application/json');
require_once 'config.php';

session_start();

// Check if user is authenticated
if (!isset($_SESSION['memberID'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$memberId = isset($_GET['memberId']) ? (int)$_GET['memberId'] : $_SESSION['memberID'];
$type = $_GET['type'] ?? 'upcoming';

// Helper function to determine status based on current time
function checkEventStatus($proposedDate, $startTime, $endTime) {
    try {
        $now = new DateTime();
        $eventStart = new DateTime($proposedDate . ' ' . $startTime);
        $eventEnd = new DateTime($proposedDate . ' ' . $endTime);
        
        // If event end time has passed, mark as Completed
        if ($now > $eventEnd) {
            return 'Completed';
        }
        
        // If event has started but not ended, mark as Ongoing
        if ($now >= $eventStart && $now <= $eventEnd) {
            return 'Ongoing';
        }
        
        // Event is scheduled
        return 'Scheduled';
    } catch (Exception $e) {
        return 'Scheduled';
    }
}

try {
    if ($type === 'upcoming') {
        $stmt = $conn->prepare("
            SELECT 
                e.EventID,
                e.ProposalID,
                e.SerialNumber,
                e.QRCode,
                e.RegistrationDeadline,
                p.Title,
                p.ProposedDate,
                p.StartTime,
                p.EndTime,
                p.Venue,
                p.TargetParticipants as Capacity,
                p.StaffRequired,
                p.Status as ProposalStatus,
                p.Description,
                COUNT(DISTINCT r.RegistrationID) as RegisteredCount,
                MAX(CASE WHEN r2.MemberID = ? THEN 1 ELSE 0 END) as MemberRegistered
            FROM event e
            JOIN proposal p ON e.ProposalID = p.ProposalID
            LEFT JOIN registration r ON e.EventID = r.EventID
            LEFT JOIN registration r2 ON e.EventID = r2.EventID
            WHERE p.Status = 'Approved'
                AND p.ProposedDate >= CURDATE()
            GROUP BY e.EventID, e.ProposalID, e.SerialNumber, e.QRCode, 
                     e.RegistrationDeadline, p.Title, 
                     p.ProposedDate, p.StartTime, p.EndTime, p.Venue, 
                     p.TargetParticipants, p.StaffRequired, p.Status, p.Description
            ORDER BY p.ProposedDate ASC, p.StartTime ASC
        ");
        
        $stmt->execute([$memberId]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate status dynamically for each event
        foreach ($events as &$event) {
            $event['EventStatus'] = checkEventStatus($event['ProposedDate'], $event['StartTime'], $event['EndTime']);
        }
        
        foreach ($events as &$event) {
            $event['RegisteredCount'] = (int)$event['RegisteredCount'];
            $event['MemberRegistered'] = (int)$event['MemberRegistered'];
            $event['Capacity'] = (int)$event['Capacity'];
            $event['StaffRequired'] = (int)$event['StaffRequired'];
        }
        
        echo json_encode([
            'success' => true,
            'type' => 'upcoming',
            'events' => $events,
            'count' => count($events)
        ]);
        
    } elseif ($type === 'registered') {
        $stmt = $conn->prepare("
            SELECT 
                e.EventID,
                e.ProposalID,
                e.SerialNumber,
                e.QRCode,
                e.RegistrationDeadline,
                p.Title,
                p.ProposedDate,
                p.StartTime,
                p.EndTime,
                p.Venue,
                p.TargetParticipants as Capacity,
                p.StaffRequired,
                p.Status as ProposalStatus,
                p.Description,
                r.RegistrationID,
                r.RegistrationDate,
                COUNT(DISTINCT ea.AttendanceID) as AttendanceCount
            FROM registration r
            JOIN event e ON r.EventID = e.EventID
            JOIN proposal p ON e.ProposalID = p.ProposalID
            LEFT JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            WHERE r.MemberID = ?
                AND p.Status = 'Approved'
            GROUP BY e.EventID, e.ProposalID, e.SerialNumber, e.QRCode, 
                     e.RegistrationDeadline, p.ProposalID, p.Title, 
                     p.ProposedDate, p.StartTime, p.EndTime, p.Venue, 
                     p.TargetParticipants, p.StaffRequired, p.Status, p.Description,
                     r.RegistrationID, r.RegistrationDate
            ORDER BY p.ProposedDate DESC, p.StartTime DESC
        ");
        
        $stmt->execute([$memberId]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Filter out completed events - only show upcoming and ongoing events
        $filteredEvents = [];
        foreach ($events as &$event) {
            $eventStatus = checkEventStatus($event['ProposedDate'], $event['StartTime'], $event['EndTime']);
            $event['EventStatus'] = $eventStatus;
            
            // Only include events that are NOT completed
            if ($eventStatus !== 'Completed') {
                $event['Capacity'] = (int)$event['Capacity'];
                $event['StaffRequired'] = (int)$event['StaffRequired'];
                $event['AttendanceCount'] = (int)$event['AttendanceCount'];
                $filteredEvents[] = $event;
            }
        }
        
        echo json_encode([
            'success' => true,
            'type' => 'registered',
            'events' => $filteredEvents,
            'count' => count($filteredEvents)
        ]);
        
    } elseif ($type === 'completed') {
        $stmt = $conn->prepare("
            SELECT 
                e.EventID,
                e.ProposalID,
                e.SerialNumber,
                p.Title,
                p.ProposedDate,
                p.StartTime,
                p.EndTime,
                p.Venue,
                p.TargetParticipants as Capacity,
                p.Status as ProposalStatus,
                r.RegistrationID,
                r.RegistrationDate,
                MAX(ea.AttendanceID) as AttendanceID,
                MAX(ea.AttendanceTime) as AttendanceTime,
                MAX(f.FeedbackID) as FeedbackID,
                MAX(f.Rating) as Rating,
                MAX(f.Comments) as Comments
            FROM registration r
            JOIN event e ON r.EventID = e.EventID
            JOIN proposal p ON e.ProposalID = p.ProposalID
            LEFT JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            LEFT JOIN feedback f ON ea.AttendanceID = f.AttendanceID
            WHERE r.MemberID = ?
                AND p.Status = 'Approved'
                AND p.ProposedDate <= CURDATE()
            GROUP BY e.EventID, e.ProposalID, e.SerialNumber, p.ProposalID, p.Title, 
                     p.ProposedDate, p.StartTime, p.EndTime, p.Venue, 
                     p.TargetParticipants, p.Status, r.RegistrationID, r.RegistrationDate
            ORDER BY p.ProposedDate DESC
        ");
        
        $stmt->execute([$memberId]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Filter for events that have ended and calculate statuses
        $filteredEvents = [];
        foreach ($events as &$event) {
            $eventStatus = checkEventStatus($event['ProposedDate'], $event['StartTime'], $event['EndTime']);
            
            // Only include completed or ongoing events
            if ($eventStatus === 'Completed' || $eventStatus === 'Ongoing') {
                $event['EventStatus'] = $eventStatus;
                $event['Capacity'] = (int)$event['Capacity'];
                $event['Rating'] = $event['Rating'] ? (int)$event['Rating'] : null;
                $filteredEvents[] = $event;
            }
        }
        
        echo json_encode([
            'success' => true,
            'type' => 'completed',
            'events' => $filteredEvents,
            'count' => count($filteredEvents)
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