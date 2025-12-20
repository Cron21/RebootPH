<?php
header('Content-Type: application/json');
session_start();

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $action = $input['action'];

    switch ($action) {
        case 'register':
            $memberId = $input['memberId'] ?? null;
            $eventId = $input['eventId'] ?? null;

            if (!$memberId || !$eventId) {
                throw new Exception('Missing required fields');
            }

            // Check if already registered
            $checkStmt = $pdo->prepare("SELECT RegistrationID FROM registration WHERE MemberID = ? AND EventID = ?");
            $checkStmt->execute([$memberId, $eventId]);
            
            if ($checkStmt->rowCount() > 0) {
                throw new Exception('Already registered for this event');
            }

            // Get event details for QR code
            $eventStmt = $pdo->prepare("SELECT QRCode, SerialNumber FROM event WHERE EventID = ?");
            $eventStmt->execute([$eventId]);
            $event = $eventStmt->fetch(PDO::FETCH_ASSOC);

            if (!$event) {
                throw new Exception('Event not found');
            }

            // Insert registration
            $stmt = $pdo->prepare("INSERT INTO registration (MemberID, EventID, RegistrationDate) VALUES (?, ?, NOW())");
            $stmt->execute([$memberId, $eventId]);

            // Return QR code (blob data as base64)
            $qrCodeBase64 = $event['QRCode'] ? base64_encode($event['QRCode']) : null;

            echo json_encode([
                'success' => true,
                'message' => 'Registered successfully',
                'qrCodeImage' => $qrCodeBase64,
                'serialNumber' => $event['SerialNumber']
            ]);
            break;

        case 'unregister':
            $memberId = $input['memberId'] ?? null;
            $eventId = $input['eventId'] ?? null;

            if (!$memberId || !$eventId) {
                throw new Exception('Missing required fields');
            }

            $stmt = $pdo->prepare("DELETE FROM registration WHERE MemberID = ? AND EventID = ?");
            $stmt->execute([$memberId, $eventId]);

            echo json_encode([
                'success' => true,
                'message' => 'Unregistered successfully'
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>