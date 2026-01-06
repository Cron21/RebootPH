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
    
    $action = $input['action'] ?? null;
    if (!$action) throw new Exception('Action required');

    if ($action === 'create') {
        // Create non-member record
        $fname = trim($input['fname'] ?? '');
        $mname = trim($input['mname'] ?? '');
        $lname = trim($input['lname'] ?? '');
        $email = trim($input['email'] ?? '');

        if (!$fname || !$lname || !$email) {
            throw new Exception('First name, last name, and email are required');
        }

        // Check if non-member already exists with this email
        $checkStmt = $conn->prepare("SELECT non_memberID FROM non_member WHERE Email = ?");
        $checkStmt->execute([$email]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            echo json_encode([
                'success' => true,
                'message' => 'Non-member exists',
                'non_memberID' => $existing['non_memberID']
            ]);
            exit;
        }

        // Create new non-member
        $stmt = $conn->prepare("INSERT INTO non_member (Fname, Mname, Lname, Email) VALUES (?, ?, ?, ?)");
        $stmt->execute([$fname, $mname, $lname, $email]);

        echo json_encode([
            'success' => true,
            'message' => 'Non-member created',
            'non_memberID' => $conn->lastInsertId()
        ]);

    } elseif ($action === 'register') {
        // Register non-member for event
        $nonMemberId = (int)($input['non_memberID'] ?? 0);
        $eventId = (int)($input['eventID'] ?? 0);
        $registrationType = trim($input['registrationType'] ?? 'Attendee');

        if ($nonMemberId <= 0 || $eventId <= 0) {
            throw new Exception('Invalid non-member or event ID');
        }

        if (!in_array($registrationType, ['Staff', 'Attendee'])) {
            throw new Exception('Invalid registration type');
        }

        // Check if already registered
        $checkStmt = $conn->prepare("SELECT RegistrationID FROM registration WHERE non_MemberID = ? AND EventID = ?");
        $checkStmt->execute([$nonMemberId, $eventId]);

        if ($checkStmt->rowCount() > 0) {
            throw new Exception('Already registered for this event');
        }

        // Insert registration
        $stmt = $conn->prepare("INSERT INTO registration (non_MemberID, EventID, RegistrationDate, RegistrationType) VALUES (?, ?, NOW(), ?)");
        $stmt->execute([$nonMemberId, $eventId, $registrationType]);

        // Get event details for QR code
        $eventStmt = $conn->prepare("SELECT QRCode, SerialNumber FROM event WHERE EventID = ?");
        $eventStmt->execute([$eventId]);
        $event = $eventStmt->fetch(PDO::FETCH_ASSOC);

        $qrCodeBase64 = $event && $event['QRCode'] ? base64_encode($event['QRCode']) : null;

        echo json_encode([
            'success' => true,
            'message' => 'Registered successfully',
            'registrationID' => $conn->lastInsertId(),
            'qrCodeImage' => $qrCodeBase64,
            'serialNumber' => $event ? $event['SerialNumber'] : null
        ]);

    } else {
        throw new Exception('Unknown action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
