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
            // Already registered - resend email with attendance and feedback links
            // Get non-member details
            $nmStmt = $conn->prepare("SELECT Fname, Lname, Email FROM non_member WHERE non_memberID = ?");
            $nmStmt->execute([$nonMemberId]);
            $nonMember = $nmStmt->fetch(PDO::FETCH_ASSOC);
            
            // Get event details
            $eventStmt = $conn->prepare("
                SELECT 
                    e.EventID, e.SerialNumber,
                    p.Title, p.ProposedDate, p.StartTime, p.EndTime, p.Venue
                FROM event e
                JOIN proposal p ON e.ProposalID = p.ProposalID
                WHERE e.EventID = ?
            ");
            $eventStmt->execute([$eventId]);
            $event = $eventStmt->fetch(PDO::FETCH_ASSOC);
            
            // Resend email
            if ($nonMember && $event) {
                // Get sender email (with fallback)
                $senderEmail = 'noreply@rebootph.com';
                try {
                    $settingsStmt = $conn->prepare("SELECT SettingValue FROM systemsettings WHERE SettingName = 'SenderEmail' LIMIT 1");
                    if ($settingsStmt) {
                        $settingsStmt->execute();
                        $setting = $settingsStmt->fetch(PDO::FETCH_ASSOC);
                        if ($setting && $setting['SettingValue']) {
                            $senderEmail = $setting['SettingValue'];
                        }
                    }
                } catch (Exception $e) {
                    // Table doesn't exist, use default
                    error_log("systemsettings table not found, using default sender email");
                }
                
                $eventDate = new DateTime($event['ProposedDate']);
                $formattedDate = $eventDate->format('F j, Y');
                
                $toEmail = $nonMember['Email'];
                $subject = "Event Details Reminder - " . htmlspecialchars($event['Title']);
                
                $attendanceLink = "https://" . $_SERVER['HTTP_HOST'] . "/non-member-checkin.html?eventId=" . $eventId . "&nonMemberId=" . $nonMemberId;
                $feedbackLink = "https://" . $_SERVER['HTTP_HOST'] . "/feedback.html?eventId=" . $eventId . "&nonMemberId=" . $nonMemberId;
                
                $htmlBody = "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='UTF-8'>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #2B702A; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
                        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 0 0 5px 5px; }
                        .event-details { background-color: white; padding: 15px; margin: 15px 0; border-left: 4px solid #2B702A; }
                        .btn { display: inline-block; padding: 12px 24px; margin: 10px 5px 10px 0; text-decoration: none; border-radius: 5px; }
                        .btn-primary { background-color: #2B702A; color: white; }
                        .btn-secondary { background-color: #0066cc; color: white; }
                        .footer { text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Event Details Reminder</h2>
                        </div>
                        <div class='content'>
                            <p>Hi " . htmlspecialchars($nonMember['Fname']) . ",</p>
                            
                            <p>We noticed you've already registered for this event. Here are the details and links you need:</p>
                            
                            <div class='event-details'>
                                <p><strong>Event:</strong> " . htmlspecialchars($event['Title']) . "</p>
                                <p><strong>Date:</strong> " . $formattedDate . "</p>
                                <p><strong>Time:</strong> " . $event['StartTime'] . " - " . $event['EndTime'] . "</p>
                                <p><strong>Venue:</strong> " . htmlspecialchars($event['Venue']) . "</p>
                                <p><strong>Your ID:</strong> " . $nonMemberId . "</p>
                            </div>
                            
                            <p>You can use these links to manage your attendance:</p>
                            <div style='text-align: center;'>
                                <a href='" . $attendanceLink . "' class='btn btn-primary'>Mark Attendance</a>
                                <a href='" . $feedbackLink . "' class='btn btn-secondary'>Submit Feedback</a>
                            </div>
                            
                            <p style='margin-top: 30px; font-size: 14px;'>If you have any questions, please contact us.</p>
                            
                            <div class='footer'>
                                <p>&copy; 2024 Reboot PH. All rights reserved.</p>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type: text/html; charset=UTF-8\r\n";
                $headers .= "From: " . $senderEmail . "\r\n";
                
                @mail($toEmail, $subject, $htmlBody, $headers);
            }
            
            echo json_encode([
                'success' => true,
                'alreadyRegistered' => true,
                'message' => 'You are already registered for this event. We have resent the event details and links to your email.',
                'non_memberID' => $nonMemberId
            ]);
            exit;
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
            'alreadyRegistered' => false,
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
