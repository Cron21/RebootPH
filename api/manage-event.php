<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? null;

    if (!$action) {
        throw new Exception('Action is required');
    }

    if ($action === 'createFromProposal') {
        $proposalId = (int)($data['proposalId'] ?? 0);
        if ($proposalId <= 0) throw new Exception('Invalid proposal ID');

        // Get proposal details
        $propStmt = $conn->prepare("
            SELECT Title, ProposedDate, StartTime, EndTime, Venue, 
                   StaffRequired, ReviewByAdminID, TargetParticipants
            FROM proposal
            WHERE ProposalID = ? AND Status = 'Approved'
        ");
        $propStmt->execute([$proposalId]);
        $proposal = $propStmt->fetch(PDO::FETCH_ASSOC);

        if (!$proposal) {
            throw new Exception('Proposal not found or not approved');
        }

        // Check if event already exists
        $checkStmt = $conn->prepare("SELECT EventID FROM event WHERE ProposalID = ?");
        $checkStmt->execute([$proposalId]);
        if ($checkStmt->rowCount() > 0) {
            throw new Exception('Event already created for this proposal');
        }

        // Calculate registration deadline (12 hours before event start)
        try {
            $eventDateTime = $proposal['ProposedDate'] . ' ' . $proposal['StartTime'];
            $deadline = new DateTime($eventDateTime);
            $deadline->modify('-12 hours');
            $registrationDeadline = $deadline->format('Y-m-d');
        } catch (Exception $e) {
            $registrationDeadline = date('Y-m-d', strtotime('-12 hours'));
        }

        // Generate serial number - simple unique number
        $serialNumber = mt_rand(100000, 999999);
        $qrData = "EVENT-{$proposalId}-{$serialNumber}";

        // Get admin ID (either from ReviewByAdminID or from current session)
        $adminId = $proposal['ReviewByAdminID'] ?? $_SESSION['memberID'] ?? null;
        if (!$adminId) throw new Exception('Not authenticated');

        // Create event WITH CreatedByAdminID (status will be calculated dynamically)
        $stmt = $conn->prepare("
            INSERT INTO event 
            (ProposalID, CreatedByAdminID, RegistrationDeadline, SerialNumber, QRCode, LastModifiedBy, LastModifiedDate)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $proposalId,
            $adminId,
            $registrationDeadline,
            $serialNumber,
            $qrData,
            $adminId
        ]);

        $eventId = $conn->lastInsertId();

        // Insert announcement with priority 0 (normal) only if one doesn't already exist
        $annCheck = $conn->prepare("SELECT AnnouncementID FROM announcement WHERE ProposalID = ?");
        $annCheck->execute([$proposalId]);
        if ($annCheck->rowCount() === 0) {
            $announcementStmt = $conn->prepare("\
                INSERT INTO announcement (ProposalID, IsPriority, CreatedByAdminID, LastModifiedBy, LastModifiedDate)\
                VALUES (?, 0, ?, ?, NOW())\
            ");
            $announcementStmt->execute([$proposalId, $adminId, $adminId]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Event created successfully from proposal',
            'eventId' => $eventId,
            'serialNumber' => $serialNumber,
            'qrData' => $qrData,
            'registrationDeadline' => $registrationDeadline
        ]);
        exit;

    } elseif ($action === 'update') {
        $eventId = (int)($data['eventId'] ?? 0);
        $eventDate = $data['eventDate'] ?? '';
        $startTime = $data['startTime'] ?? '';
        $endTime = $data['endTime'] ?? '';
        $location = $data['location'] ?? '';
        $capacity = (int)($data['capacity'] ?? 0);
        $registrationDeadline = $data['registrationDeadline'] ?? '';

        if ($eventId <= 0) throw new Exception('Invalid event ID');
        if (empty($eventDate) || empty($startTime) || empty($endTime)) throw new Exception('Date and time are required');

        // Update the proposal with the new values (since event data comes from proposal via JOIN)
        $proposalId = 0;
        $getProposalStmt = $conn->prepare("SELECT ProposalID FROM event WHERE EventID = ?");
        $getProposalStmt->execute([$eventId]);
        $eventRow = $getProposalStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($eventRow) {
            $proposalId = $eventRow['ProposalID'];
            
            // Update proposal table with new values
            $updateStmt = $conn->prepare("
                UPDATE proposal 
                SET ProposedDate = ?, StartTime = ?, EndTime = ?, Venue = ?, TargetParticipants = ?
                WHERE ProposalID = ?
            ");
            $updateStmt->execute([$eventDate, $startTime, $endTime, $location, $capacity, $proposalId]);
            
            // Update event table with new registration deadline
            $updateEventStmt = $conn->prepare("
                UPDATE event 
                SET RegistrationDeadline = ?, LastModifiedBy = ?, LastModifiedDate = NOW()
                WHERE EventID = ?
            ");
            $updateEventStmt->execute([$registrationDeadline, $_SESSION['memberID'] ?? 1, $eventId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Event updated successfully'
            ]);
        } else {
            throw new Exception('Event not found');
        }
        exit;

    } elseif ($action === 'delete') {
        $eventId = (int)($data['eventId'] ?? 0);

        if ($eventId <= 0) throw new Exception('Invalid event ID');

        // Get event and proposal details before deleting
        $getEventStmt = $conn->prepare("SELECT ProposalID FROM event WHERE EventID = ?");
        $getEventStmt->execute([$eventId]);
        $eventRow = $getEventStmt->fetch(PDO::FETCH_ASSOC);

        if (!$eventRow) {
            throw new Exception('Event not found');
        }

        $proposalId = $eventRow['ProposalID'];

        // Delete attendance records first (they reference registrations)
        $deleteAttendanceStmt = $conn->prepare("
            DELETE FROM eventattendance 
            WHERE RegistrationID IN (SELECT RegistrationID FROM registration WHERE EventID = ?)
        ");
        $deleteAttendanceStmt->execute([$eventId]);

        // Delete registrations related to this event
        $deleteRegStmt = $conn->prepare("DELETE FROM registration WHERE EventID = ?");
        $deleteRegStmt->execute([$eventId]);

        // Delete feedback related to this event
        $deleteFeedbackStmt = $conn->prepare("DELETE FROM feedback WHERE EventID = ?");
        $deleteFeedbackStmt->execute([$eventId]);

        // Delete the event itself
        $deleteEventStmt = $conn->prepare("DELETE FROM event WHERE EventID = ?");
        $deleteEventStmt->execute([$eventId]);

        echo json_encode([
            'success' => true,
            'message' => 'Event deleted successfully along with all related data'
        ]);
        exit;

    } else {
        throw new Exception('Unknown action: ' . $action);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>