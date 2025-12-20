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

if (!$input || !isset($input['token']) || !isset($input['newPassword'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Token and new password are required']);
    exit;
}

$token = trim($input['token']);
$newPassword = $input['newPassword'];

// Validate password
if (strlen($newPassword) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
    exit;
}

try {
    // Verify token exists and is valid
    $stmt = $conn->prepare("
        SELECT prt.ApplicationID, prt.ExpiryTime, prt.Used
        FROM password_reset_tokens prt
        WHERE prt.Token = ? AND prt.Used = 0 AND prt.ExpiryTime > NOW()
    ");
    $stmt->execute([$token]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tokenData) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid or expired reset token']);
        exit;
    }

    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

    // Update password in application table
    $updateStmt = $conn->prepare("UPDATE application SET PasswordHash = ? WHERE ApplicationID = ?");
    $updateStmt->execute([$hashedPassword, $tokenData['ApplicationID']]);

    // Mark token as used
    $markUsedStmt = $conn->prepare("UPDATE password_reset_tokens SET Used = 1 WHERE Token = ?");
    $markUsedStmt->execute([$token]);

    echo json_encode([
        'success' => true,
        'message' => 'Password reset successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>

