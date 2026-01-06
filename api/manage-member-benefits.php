<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? null;
    if (!$action) throw new Exception('Action required');

    $adminId = $_SESSION['memberID'] ?? null;
    if (!$adminId) throw new Exception('Not authenticated');

    if ($action === 'create') {
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $isActive = (int)($data['isActive'] ?? 0);

        if (!$title) throw new Exception('Title is required');

        $stmt = $conn->prepare("INSERT INTO member_benefits (Title, Description, isActive, CreateByAdminID, CreateDate) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$title, $description, $isActive, $adminId]);

        echo json_encode(['success' => true, 'message' => 'Benefit created', 'id' => $conn->lastInsertId()]);

    } elseif ($action === 'update') {
        $id = (int)($data['id'] ?? 0);
        if ($id <= 0) throw new Exception('Invalid ID');
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $isActive = (int)($data['isActive'] ?? 0);

        if (!$title) throw new Exception('Title is required');

        $stmt = $conn->prepare("UPDATE member_benefits SET Title = ?, Description = ?, isActive = ?, LastModifiedBy = ?, LastModifiedDate = NOW() WHERE BenefitsID = ?");
        $stmt->execute([$title, $description, $isActive, $adminId, $id]);

        echo json_encode(['success' => true, 'message' => 'Benefit updated']);

    } elseif ($action === 'delete') {
        $id = (int)($data['id'] ?? 0);
        if ($id <= 0) throw new Exception('Invalid ID');
        $stmt = $conn->prepare("DELETE FROM member_benefits WHERE BenefitsID = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Benefit deleted']);

    } elseif ($action === 'setActive') {
        $id = (int)($data['id'] ?? 0);
        if ($id <= 0) throw new Exception('Invalid ID');

        $stmt = $conn->prepare("UPDATE member_benefits SET isActive = 1, LastModifiedBy = ?, LastModifiedDate = NOW() WHERE BenefitsID = ?");
        $stmt->execute([$adminId, $id]);

        echo json_encode(['success' => true, 'message' => 'Benefit activated']);
    } else {
        throw new Exception('Unknown action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}