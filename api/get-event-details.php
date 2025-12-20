<?php
header('Content-Type: application/json');

require_once 'config.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Event ID is required']);
    exit;
}

$eventId = (int)$_GET['id'];

try {
    $stmt = $conn->prepare("
        SELECT 
            e.EventID,
            e.ProposalID,
            e.RegistrationDeadline,
            e.SerialNumber,
            e.QRCode,
            e.CreatedByAdminID,
            p.ProposalID,
            p.Title,
            p.ProposedDate,
            p.StartTime,
            p.EndTime,
            p.Venue,
            p.StaffRequired,
            p.TargetParticipants,
            p.Status,
            p.Description,
            p.EventType,
            p.BudgetEstimate,
            p.EquipmentNeeded,
            p.Objectives,
            p.Department,
            p.PartnersSponsor,
            p.AdditionalNotes,
            (SELECT COUNT(DISTINCT r2.RegistrationID)
             FROM registration r2
             WHERE r2.EventID = e.EventID) as StaffRegistered,
            (SELECT COUNT(DISTINCT r3.RegistrationID)
             FROM registration r3
             WHERE r3.EventID = e.EventID) as RegisteredCount
        FROM event e
        LEFT JOIN proposal p ON e.ProposalID = p.ProposalID
        WHERE e.EventID = ?
    ");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        throw new Exception('Event not found');
    }

    // Ensure numeric values
    $event['StaffRegistered'] = (int)($event['StaffRegistered'] ?? 0);
    $event['RegisteredCount'] = (int)($event['RegisteredCount'] ?? 0);
    $event['StaffRequired'] = (int)($event['StaffRequired'] ?? 0);
    $event['TargetParticipants'] = (int)($event['TargetParticipants'] ?? 0);

    echo json_encode([
        'success' => true,
        'event' => $event
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>