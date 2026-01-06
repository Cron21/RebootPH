<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $nonMemberId = (int)($input['non_memberID'] ?? 0);
    $eventId = (int)($input['eventID'] ?? 0);
    
    if ($nonMemberId <= 0 || $eventId <= 0) {
        throw new Exception('Invalid non-member or event ID');
    }
    
    // Verify non-member exists
    $nmStmt = $conn->prepare("SELECT non_memberID, Fname, Lname FROM non_member WHERE non_memberID = ?");
    $nmStmt->execute([$nonMemberId]);
    $nonMember = $nmStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$nonMember) {
        throw new Exception('Non-member not found');
    }
    
    // Verify non-member is registered for this event
    $regStmt = $conn->prepare("
        SELECT RegistrationID FROM registration 
        WHERE non_MemberID = ? AND EventID = ?
    ");
    $regStmt->execute([$nonMemberId, $eventId]);
    $registration = $regStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registration) {
        throw new Exception('Non-member is not registered for this event');
    }
    
    // Check if already marked attendance
    $checkStmt = $conn->prepare("
        SELECT AttendanceID FROM eventattendance 
        WHERE RegistrationID = ?
    ");
    $checkStmt->execute([$registration['RegistrationID']]);
    
    if ($checkStmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'alreadyMarked' => true,
            'message' => 'Attendance already marked for this event'
        ]);
        exit;
    }
    
    // Record attendance
    $insertStmt = $conn->prepare("
        INSERT INTO eventattendance (RegistrationID, AttendanceTime, ScanType)
        VALUES (?, NOW(), 'NonMemberCheckIn')
    ");
    $insertStmt->execute([$registration['RegistrationID']]);
    $attendanceId = $conn->lastInsertId();
    
    // Get event details for response
    $eventStmt = $conn->prepare("
        SELECT p.Title FROM event e
        JOIN proposal p ON e.ProposalID = p.ProposalID
        WHERE e.EventID = ?
    ");
    $eventStmt->execute([$eventId]);
    $event = $eventStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Attendance marked successfully',
        'attendanceId' => $attendanceId,
        'nonMemberName' => $nonMember['Fname'] . ' ' . $nonMember['Lname'],
        'eventName' => $event['Title'] ?? 'Event',
        'alreadyMarked' => false
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
