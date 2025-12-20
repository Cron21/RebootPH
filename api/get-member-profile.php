<?php
header('Content-Type: application/json');
session_start();

require_once 'config.php';

if (!isset($_SESSION['memberID'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $memberId = $_GET['memberId'] ?? null;

    if (!$memberId) {
        throw new Exception('Member ID is required');
    }

    $stmt = $pdo->prepare("SELECT ProfileImage FROM member WHERE MemberID = ?");
    $stmt->execute([$memberId]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        throw new Exception('Member not found');
    }

    $profileImage = $member['ProfileImage'];

    // Return null or empty string if no image
    if (!$profileImage || $profileImage === 'null') {
        $profileImage = null;
    }

    echo json_encode([
        'success' => true,
        'profileImage' => $profileImage
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>