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

    // Get user's role from session
    if (!isset($_SESSION['role'])) {
        throw new Exception('Not authenticated - no role in session');
    }
    $userRole = $_SESSION['role'];

    $action = $input['action'];

    switch ($action) {
        case 'approve':
            $applicationId = $input['applicationId'] ?? null;
            $customPassword = $input['customPassword'] ?? null; // Optional: admin can provide custom password

            if (!$applicationId) {
                throw new Exception('Application ID is required');
            }

            // Only Admin and Executive Director can approve members
            $rolesCanApprove = ['Admin', 'Executive Director'];
            if (!in_array($userRole, $rolesCanApprove)) {
                throw new Exception('Unauthorized: Only Admin and Executive Director can approve members');
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

            // Use transaction to ensure atomicity
            $pdo->beginTransaction();

            try {
                // Generate password or use the one provided by admin
                $tempPassword = $customPassword ?: generateRandomPassword(12);
                $passwordHash = password_hash($tempPassword, PASSWORD_BCRYPT);

                // Update application status to 1 (approved) ONLY if it's currently 0
                // This prevents race conditions - if 2 requests try to approve simultaneously,
                // only the first will succeed in updating the status
                $stmt = $pdo->prepare("UPDATE application SET ApplicationStatus = 1, ReviewDate = NOW(), PasswordHash = ? WHERE ApplicationID = ? AND ApplicationStatus = 0");
                $stmt->execute([$passwordHash, $applicationId]);
                $rowsAffected = $stmt->rowCount();

                // If no rows were affected, another approval is already in progress or completed
                if ($rowsAffected === 0) {
                    $pdo->rollBack();
                    throw new Exception('This application is being processed or has already been approved.');
                }

                // NOTE: Member record is created automatically by database trigger
                // when ApplicationStatus is set to 1. No manual insert needed.

                // Commit transaction
                $pdo->commit();

                // Send password email to applicant
                $emailSent = sendPasswordEmail($applicant['ApplicantEmail'], $applicant['FName'], $tempPassword);

                echo json_encode([
                    'success' => true,
                    'message' => 'Application approved successfully. Password email has been sent to the applicant.',
                    'emailSent' => $emailSent
                ]);
            } catch (PDOException $dbError) {
                // Check for duplicate key constraint error (SQLSTATE 23000)
                if ($dbError->getCode() == 23000) {
                    $pdo->rollBack();
                    throw new Exception('A member record already exists for this application. The application may have been approved simultaneously.');
                }
                $pdo->rollBack();
                throw $dbError;
            } catch (Exception $transactionError) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
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