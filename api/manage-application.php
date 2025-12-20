<?php
header('Content-Type: application/json');

require_once 'config.php';
require_once 'send-password-email.php';

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
        case 'approve':
            $applicationId = $input['applicationId'] ?? null;
            $customPassword = $input['customPassword'] ?? null; // Optional: admin can provide custom password

            if (!$applicationId) {
                throw new Exception('Application ID is required');
            }

            // Get applicant details and current status
            $appStmt = $pdo->prepare("SELECT ApplicationID, FName, ApplicantEmail, ApplicationStatus FROM application WHERE ApplicationID = ?");
            $appStmt->execute([$applicationId]);
            $applicant = $appStmt->fetch(PDO::FETCH_ASSOC);

            if (!$applicant) {
                throw new Exception('Application not found');
            }

            // Check if already approved
            if ($applicant['ApplicationStatus'] == 1) {
                throw new Exception('This application has already been approved. Cannot approve duplicate.');
            }

            // Check if member already exists for this application (prevent duplicates)
            $memberCheckStmt = $pdo->prepare("SELECT MemberID FROM member WHERE ApplicationID = ?");
            $memberCheckStmt->execute([$applicationId]);
            $existingMember = $memberCheckStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingMember) {
                throw new Exception('A member record already exists for this application. Cannot create duplicate.');
            }

            // Use transaction to ensure atomicity
            $pdo->beginTransaction();

            try {
                // Generate password or use the one provided by admin
                $tempPassword = $customPassword ?: generateRandomPassword(12);
                $passwordHash = password_hash($tempPassword, PASSWORD_BCRYPT);

                // Update application status to 1 (approved), set ReviewDate and PasswordHash
                $stmt = $pdo->prepare("UPDATE application SET ApplicationStatus = 1, ReviewDate = NOW(), PasswordHash = ? WHERE ApplicationID = ?");
                $stmt->execute([$passwordHash, $applicationId]);

                // Create member record
                $memberStmt = $pdo->prepare("INSERT INTO member (ApplicationID, Role, isActive, JoinDate) VALUES (?, 'member', 1, NOW())");
                $memberStmt->execute([$applicationId]);

                // Commit transaction
                $pdo->commit();

                // Send password email to applicant
                $emailSent = sendPasswordEmail($applicant['ApplicantEmail'], $applicant['FName'], $tempPassword);

                echo json_encode([
                    'success' => true,
                    'message' => 'Application approved successfully. Password email has been sent to the applicant.',
                    'emailSent' => $emailSent
                ]);
            } catch (Exception $transactionError) {
                $pdo->rollBack();
                throw $transactionError;
            }
            break;

        case 'reject':
            $applicationId = $input['applicationId'] ?? null;
            $rejectionReason = $input['rejectionReason'] ?? 'Your application was not approved.';

            if (!$applicationId) {
                throw new Exception('Application ID is required');
            }

            // Get applicant email for notification
            $appStmt = $pdo->prepare("SELECT ApplicantEmail, FName FROM application WHERE ApplicationID = ?");
            $appStmt->execute([$applicationId]);
            $applicant = $appStmt->fetch(PDO::FETCH_ASSOC);

            // Update application status to 2 (rejected) and set ReviewDate
            $stmt = $pdo->prepare("UPDATE application SET ApplicationStatus = 2, ReviewDate = NOW() WHERE ApplicationID = ?");
            $stmt->execute([$applicationId]);

            // Send rejection email (optional - you can implement this separately)
            // For now, just return success message
            echo json_encode([
                'success' => true,
                'message' => 'Application rejected successfully.'
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