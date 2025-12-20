<?php
header('Content-Type: application/json');

require_once 'config.php';

// Enable error reporting for debugging
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    // Check if POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }

    $email = isset($input['email']) ? trim($input['email']) : '';
    $tempPassword = isset($input['tempPassword']) ? $input['tempPassword'] : '';

    // Validate input
    if (!$email || !$tempPassword) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email and temporary password are required']);
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }

    // Query application table for approved applications
    $stmt = $conn->prepare("
        SELECT ApplicationID, FName, LName, PasswordHash, ApplicationStatus 
        FROM application 
        WHERE ApplicantEmail = :email 
        LIMIT 1
    ");
    $stmt->execute([':email' => $email]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        // Don't reveal if email exists or not for security
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid email or temporary password']);
        exit;
    }

    // Check if application is approved (status = 1)
    if ($application['ApplicationStatus'] != 1) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Your application has not been approved yet or has been rejected']);
        exit;
    }

    // Check if password is set (not null/empty)
    if (!$application['PasswordHash']) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Temporary password not yet assigned. Please contact administrator.']);
        exit;
    }

    // Verify the temporary password using password_verify
    if (!password_verify($tempPassword, $application['PasswordHash'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid email or temporary password']);
        exit;
    }

    // Successfully verified
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Credentials verified successfully',
        'applicant' => [
            'applicationId' => $application['ApplicationID'],
            'firstName' => $application['FName'],
            'lastName' => $application['LName']
        ]
    ]);

} catch (PDOException $e) {
    error_log('Database error in verify-temp-password.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('Error in verify-temp-password.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
