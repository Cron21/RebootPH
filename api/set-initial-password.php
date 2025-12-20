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

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['email']) || !isset($input['tempPassword']) || !isset($input['newPassword'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $email = trim($input['email']);
    $tempPassword = $input['tempPassword'];
    $newPassword = $input['newPassword'];

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Validate new password strength
    if (strlen($newPassword) < 8) {
        throw new Exception('Password must be at least 8 characters');
    }

    // Find application by email
    $stmt = $conn->prepare("SELECT ApplicationID, PasswordHash, ApplicationStatus FROM application WHERE ApplicantEmail = :email");
    $stmt->execute([':email' => $email]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        throw new Exception('Email not found in system');
    }

    // Check if application is approved (status = 1)
    if ($application['ApplicationStatus'] != 1) {
        throw new Exception('This account has not been approved yet or has been rejected');
    }

    // Verify temporary password
    if (!password_verify($tempPassword, $application['PasswordHash'])) {
        throw new Exception('Invalid temporary password');
    }

    // Hash new password
    $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);

    // Update password
    $updateStmt = $conn->prepare("UPDATE application SET PasswordHash = :hash WHERE ApplicationID = :appId");
    $updateStmt->execute([':hash' => $newPasswordHash, ':appId' => $application['ApplicationID']]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Password has been set successfully. You can now log in with your new password.'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
