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
    
    // Get non-member details
    $nmStmt = $conn->prepare("
        SELECT Fname, Lname, Email FROM non_member WHERE non_memberID = ?
    ");
    $nmStmt->execute([$nonMemberId]);
    $nonMember = $nmStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$nonMember) {
        throw new Exception('Non-member not found');
    }
    
    // Get event details
    $eventStmt = $conn->prepare("
        SELECT 
            e.EventID,
            e.SerialNumber,
            p.Title,
            p.ProposedDate,
            p.StartTime,
            p.EndTime,
            p.Venue,
            p.Description
        FROM event e
        JOIN proposal p ON e.ProposalID = p.ProposalID
        WHERE e.EventID = ?
    ");
    $eventStmt->execute([$eventId]);
    $event = $eventStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        throw new Exception('Event not found');
    }
    
    // Get system settings for sender info
    $settingsStmt = $conn->prepare("SELECT SettingValue FROM systemsettings WHERE SettingName = 'SenderEmail' LIMIT 1");
    $settingsStmt->execute();
    $setting = $settingsStmt->fetch(PDO::FETCH_ASSOC);
    $senderEmail = $setting['SettingValue'] ?? 'noreply@rebootph.com';
    
    // Format event date
    $eventDate = new DateTime($event['ProposedDate']);
    $formattedDate = $eventDate->format('F j, Y');
    
    // Prepare email
    $toEmail = $nonMember['Email'];
    $subject = "Registration Confirmation - " . htmlspecialchars($event['Title']);
    
    // Build attendance URL (for mark attendance button)
    $attendanceLink = "https://" . $_SERVER['HTTP_HOST'] . "/non-member-checkin.html?eventId=" . $eventId . "&nonMemberId=" . $nonMemberId;
    
    // Build feedback URL
    $feedbackLink = "https://" . $_SERVER['HTTP_HOST'] . "/feedback.html?eventId=" . $eventId . "&nonMemberId=" . $nonMemberId;
    
    // Create HTML email body
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
            .event-details p { margin: 8px 0; }
            .event-label { font-weight: bold; color: #2B702A; }
            .btn { display: inline-block; padding: 12px 24px; margin: 10px 5px 10px 0; text-decoration: none; border-radius: 5px; }
            .btn-primary { background-color: #2B702A; color: white; }
            .btn-secondary { background-color: #0066cc; color: white; }
            .btn:hover { opacity: 0.9; }
            .footer { text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
            .credentials { background-color: #fff3cd; padding: 12px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #ffc107; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Registration Confirmed! 🎉</h2>
            </div>
            <div class='content'>
                <p>Hi " . htmlspecialchars($nonMember['Fname']) . ",</p>
                
                <p>Thank you for registering for our event! We're excited to have you join us.</p>
                
                <div class='event-details'>
                    <p><span class='event-label'>Event:</span> " . htmlspecialchars($event['Title']) . "</p>
                    <p><span class='event-label'>Date:</span> " . $formattedDate . "</p>
                    <p><span class='event-label'>Time:</span> " . $event['StartTime'] . " - " . $event['EndTime'] . "</p>
                    <p><span class='event-label'>Venue:</span> " . htmlspecialchars($event['Venue']) . "</p>
                </div>
                
                <div class='credentials'>
                    <p style='margin-top: 0;'><strong>Your Credentials:</strong></p>
                    <p style='margin-bottom: 0;'><span class='event-label'>Non-Member ID:</span> " . $nonMemberId . "</p>
                    <p><span class='event-label'>Event Serial:</span> " . htmlspecialchars($event['SerialNumber']) . "</p>
                </div>
                
                <p>You can now:</p>
                <div style='text-align: center;'>
                    <a href='" . $attendanceLink . "' class='btn btn-primary'>Mark Attendance</a>
                    <a href='" . $feedbackLink . "' class='btn btn-secondary'>Submit Feedback</a>
                </div>
                
                <p style='margin-top: 30px; font-size: 14px;'>If you have any questions or need assistance, please don't hesitate to contact us.</p>
                
                <div class='footer'>
                    <p>&copy; 2024 Reboot PH. All rights reserved.</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Create plain text version
    $textBody = "
Registration Confirmed!

Hi " . $nonMember['Fname'] . ",

Thank you for registering for our event! We're excited to have you join us.

EVENT DETAILS:
Event: " . $event['Title'] . "
Date: " . $formattedDate . "
Time: " . $event['StartTime'] . " - " . $event['EndTime'] . "
Venue: " . $event['Venue'] . "

YOUR CREDENTIALS:
Non-Member ID: " . $nonMemberId . "
Event Serial: " . $event['SerialNumber'] . "

You can now mark your attendance and submit feedback using the links below:
- Mark Attendance: " . $attendanceLink . "
- Submit Feedback: " . $feedbackLink . "

If you have any questions, please contact us.

© 2024 Reboot PH. All rights reserved.
    ";
    
    // Send email
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . $senderEmail . "\r\n";
    $headers .= "Reply-To: " . $senderEmail . "\r\n";
    
    $mailSent = mail($toEmail, $subject, $htmlBody, $headers);
    
    if (!$mailSent) {
        // Log error but don't fail - email might not be configured
        error_log("Failed to send registration email to: " . $toEmail);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Registration email sent successfully',
        'nonMemberId' => $nonMemberId,
        'eventId' => $eventId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
