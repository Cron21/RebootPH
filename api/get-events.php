<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

try {
    $stmt = $conn->prepare("
        SELECT 
            e.EventID,
            e.ProposalID,
            e.RegistrationDeadline,
            e.SerialNumber,
            e.QRCode,
            e.CreatedByAdminID,
            e.LastModifiedDate,
            e.LastModifiedBy,
            p.Title,
            p.ProposedDate,
            p.StartTime,
            p.EndTime,
            p.Venue,
            p.TargetParticipants as Capacity,
            p.StaffRequired,
            p.Status as ProposalStatus,
            COUNT(DISTINCT r.RegistrationID) as RegisteredCount
        FROM event e
        LEFT JOIN proposal p ON e.ProposalID = p.ProposalID
        LEFT JOIN registration r ON e.EventID = r.EventID
        GROUP BY e.EventID, e.ProposalID, e.RegistrationDeadline, e.SerialNumber, 
                 e.QRCode, e.CreatedByAdminID, e.LastModifiedDate, 
                 e.LastModifiedBy, p.Title, p.ProposedDate, p.StartTime, p.EndTime, 
                 p.Venue, p.TargetParticipants, p.StaffRequired, p.Status
        ORDER BY p.ProposedDate DESC
    ");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Helper function to determine status
    function checkEventStatus($proposedDate, $startTime, $endTime, $currentStatus) {
        try {
            $now = new DateTime();
            $eventStart = new DateTime($proposedDate . ' ' . $startTime);
            $eventEnd = new DateTime($proposedDate . ' ' . $endTime);
            
            // Don't override manually set statuses
            if (in_array($currentStatus, ['Postponed', 'Moved'])) {
                return $currentStatus;
            }
            
            // If event end time has passed, mark as Completed
            if ($now > $eventEnd) {
                return 'Completed';
            }
            
            // If event is near (within 24 hours and not started yet)
            $twentyFourHoursFromNow = clone $now;
            $twentyFourHoursFromNow->modify('+24 hours');
            if ($now < $eventStart && $eventStart <= $twentyFourHoursFromNow) {
                return 'Near';
            }
            
            // If event has started but not ended
            if ($now >= $eventStart && $now <= $eventEnd) {
                return 'Ongoing';
            }
            
            // Event is scheduled (more than 24 hours away)
            return 'Scheduled';
        } catch (Exception $e) {
            return $currentStatus;
        }
    }

    // Calculate statuses dynamically and ensure RegisteredCount is an integer
    foreach ($events as &$event) {
        $event['status'] = checkEventStatus($event['ProposedDate'], $event['StartTime'], $event['EndTime'], $event['status']);
        
        // Ensure RegisteredCount is an integer
        $event['RegisteredCount'] = (int)($event['RegisteredCount'] ?? 0);
        $event['Capacity'] = (int)($event['Capacity'] ?? 0);
    }

    echo json_encode([
        'success' => true,
        'events' => $events,
        'count' => count($events)
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