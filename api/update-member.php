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
    $currentMemberId = $_SESSION['memberID'] ?? null;

    switch ($action) {
        case 'updateRole':
            $memberId = $input['memberId'] ?? null;
            $newRole = $input['role'] ?? null;

            if (!$memberId || !$newRole) {
                throw new Exception('Missing required fields');
            }

            // Prevent user from changing their own role
            if ($currentMemberId == $memberId) {
                throw new Exception('You cannot change your own role');
            }

            if (!in_array($newRole, ['Member', 'Admin'])) {
                throw new Exception('Invalid role');
            }

            $stmt = $pdo->prepare("UPDATE member SET Role = ? WHERE MemberID = ?");
            $stmt->execute([$newRole, $memberId]);

            echo json_encode([
                'success' => true,
                'message' => 'Role updated successfully'
            ]);
            break;

        case 'deactivateMember':
            $memberId = $input['memberId'] ?? null;

            if (!$memberId) {
                throw new Exception('Member ID is required');
            }

            // Prevent user from deactivating themselves
            if ($currentMemberId == $memberId) {
                throw new Exception('You cannot deactivate your own account');
            }

            $stmt = $pdo->prepare("UPDATE member SET isActive = 0 WHERE MemberID = ?");
            $stmt->execute([$memberId]);

            echo json_encode([
                'success' => true,
                'message' => 'Member deactivated successfully'
            ]);
            break;

        case 'activateMember':
            $memberId = $input['memberId'] ?? null;

            if (!$memberId) {
                throw new Exception('Member ID is required');
            }

            // Prevent user from activating themselves (though they shouldn't be deactivated)
            if ($currentMemberId == $memberId) {
                throw new Exception('You cannot modify your own account status');
            }

            $stmt = $pdo->prepare("UPDATE member SET isActive = 1 WHERE MemberID = ?");
            $stmt->execute([$memberId]);

            echo json_encode([
                'success' => true,
                'message' => 'Member activated successfully'
            ]);
            break;

        case 'updateProfile':
            $memberId = $input['memberId'] ?? null;
            $firstName = $input['firstName'] ?? null;
            $lastName = $input['lastName'] ?? null;
            $email = $input['email'] ?? null;
            $profileImagePath = $input['profileImage'] ?? null;

            if (!$memberId || !$firstName || !$lastName || !$email) {
                throw new Exception('Missing required fields');
            }

            // PERMISSION CHECK: Only allow users to update their own profile
            if (!$currentMemberId) {
                throw new Exception('Not authenticated');
            }

            if ((int)$currentMemberId !== (int)$memberId) {
                throw new Exception('You can only update your own profile');
            }

            // Get ApplicationID from member table
            $stmt = $pdo->prepare("SELECT ApplicationID FROM member WHERE MemberID = ?");
            $stmt->execute([$memberId]);
            $memberRow = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$memberRow) {
                throw new Exception('Member not found');
            }

            $applicationId = $memberRow['ApplicationID'];

            // Check if email already exists for another application
            $checkEmail = $pdo->prepare("SELECT ApplicationID FROM application WHERE ApplicantEmail = ? AND ApplicationID != ?");
            $checkEmail->execute([$email, $applicationId]);
            
            if ($checkEmail->rowCount() > 0) {
                throw new Exception('Email already in use');
            }

            // Update application table with name and email
            $stmt = $pdo->prepare("UPDATE application SET FName = ?, LName = ?, ApplicantEmail = ? WHERE ApplicationID = ?");
            $stmt->execute([$firstName, $lastName, $email, $applicationId]);

            // Update profile image in member table if provided
            if ($profileImagePath) {
                $updateImage = $pdo->prepare("UPDATE member SET ProfileImage = ? WHERE MemberID = ?");
                $updateImage->execute([$profileImagePath, $memberId]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Profile updated successfully'
            ]);
            break;

        case 'updatePassword':
            $memberId = $input['memberId'] ?? null;
            $currentPassword = $input['currentPassword'] ?? null;
            $newPassword = $input['newPassword'] ?? null;

            if (!$memberId || !$currentPassword || !$newPassword) {
                throw new Exception('Missing required fields');
            }

            if (strlen($newPassword) < 8) {
                throw new Exception('Password must be at least 8 characters long');
            }

            // Get ApplicationID from member table
            $stmt = $pdo->prepare("SELECT ApplicationID FROM member WHERE MemberID = ?");
            $stmt->execute([$memberId]);
            $memberRow = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$memberRow) {
                throw new Exception('Member not found');
            }

            $applicationId = $memberRow['ApplicationID'];

            // Get current password hash from application table
            $stmt = $pdo->prepare("SELECT PasswordHash FROM application WHERE ApplicationID = ?");
            $stmt->execute([$applicationId]);
            $application = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$application) {
                throw new Exception('Application record not found');
            }

            // Verify current password against PasswordHash
            if (!password_verify($currentPassword, $application['PasswordHash'])) {
                throw new Exception('Current password is incorrect');
            }

            // Hash and update new password in application table
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $updateStmt = $pdo->prepare("UPDATE application SET PasswordHash = ? WHERE ApplicationID = ?");
            $updateStmt->execute([$hashedPassword, $applicationId]);

            echo json_encode([
                'success' => true,
                'message' => 'Password updated successfully'
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