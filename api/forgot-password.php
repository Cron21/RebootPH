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

if (!$input || !isset($input['email'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

$email = trim($input['email']);

try {
    // Check if email exists in application table
    $stmt = $conn->prepare("
        SELECT ApplicationID, FName, LName, ApplicantEmail
        FROM application
        WHERE ApplicantEmail = ?
    ");
    $stmt->execute([$email]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        // Don't reveal if email exists or not for security
        echo json_encode([
            'success' => true,
            'message' => 'If the email exists, a password reset link has been sent.'
        ]);
        exit;
    }

    // Generate reset token
    $resetToken = bin2hex(random_bytes(32));
    $expiryTime = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Store reset token in database (you may need to add a password_reset_tokens table)
    // For now, we'll use a simple approach with system_settings or create a token storage
    
    // Check if password_reset_tokens table exists, if not, we'll use a simpler approach
    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS password_reset_tokens (
                TokenID INT PRIMARY KEY AUTO_INCREMENT,
                ApplicationID INT NOT NULL,
                Token VARCHAR(64) UNIQUE NOT NULL,
                ExpiryTime DATETIME NOT NULL,
                Used TINYINT DEFAULT 0,
                CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (ApplicationID) REFERENCES application(ApplicationID) ON DELETE CASCADE
            )
        ");
    } catch (Exception $e) {
        // Table might already exist
    }

    // Delete old tokens for this application
    $deleteStmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE ApplicationID = ? OR ExpiryTime < NOW()");
    $deleteStmt->execute([$application['ApplicationID']]);

    // Insert new token
    $insertStmt = $conn->prepare("
        INSERT INTO password_reset_tokens (ApplicationID, Token, ExpiryTime)
        VALUES (?, ?, ?)
    ");
    $insertStmt->execute([$application['ApplicationID'], $resetToken, $expiryTime]);

    // In a real application, you would send an email here with the reset link
    // For now, we'll just return success
    // The reset link would be: reset-password.html?token=RESET_TOKEN
    
    echo json_encode([
        'success' => true,
        'message' => 'If the email exists, a password reset link has been sent.',
        // In development, you might want to return the token for testing
        // 'token' => $resetToken // Remove this in production!
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>

