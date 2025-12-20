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

    if (!$action) {
        throw new Exception('Action is required');
    }

    $adminId = $_SESSION['memberID'] ?? null;
    if (!$adminId) throw new Exception('Not authenticated');

    if ($action === 'create') {
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $isActive = (int)($data['isActive'] ?? 0);

        if (!$title) throw new Exception('Title is required');
        if (!$description) throw new Exception('Description is required');

        // If setting as active, deactivate all others
        if ($isActive) {
            $stmt = $conn->prepare("UPDATE hero_section SET isActive = 0");
            $stmt->execute();
        }

        $stmt = $conn->prepare("
            INSERT INTO hero_section (Title, description, isActive, PublishDate, CreateByAdminID, LastModifiedBy, LastModifiedDate)
            VALUES (?, ?, ?, NOW(), ?, ?, NOW())
        ");
        $stmt->execute([$title, $description, $isActive, $adminId, $adminId]);

        echo json_encode([
            'success' => true,
            'message' => 'Hero section created successfully',
            'heroId' => $conn->lastInsertId()
        ]);

    } elseif ($action === 'update') {
        $heroId = (int)($data['heroId'] ?? 0);
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $isActive = (int)($data['isActive'] ?? 0);

        if ($heroId <= 0) throw new Exception('Invalid hero section ID');
        if (!$title) throw new Exception('Title is required');
        if (!$description) throw new Exception('Description is required');

        // If setting as active, deactivate all others
        if ($isActive) {
            $stmt = $conn->prepare("UPDATE hero_section SET isActive = 0");
            $stmt->execute();
        }

        $stmt = $conn->prepare("
            UPDATE hero_section 
            SET Title = ?, description = ?, isActive = ?, LastModifiedBy = ?, LastModifiedDate = NOW()
            WHERE heroID = ?
        ");
        $stmt->execute([$title, $description, $isActive, $adminId, $heroId]);

        echo json_encode([
            'success' => true,
            'message' => 'Hero section updated successfully'
        ]);

    } elseif ($action === 'delete') {
        $heroId = (int)($data['heroId'] ?? 0);
        if ($heroId <= 0) throw new Exception('Invalid hero section ID');

        $stmt = $conn->prepare("DELETE FROM hero_section WHERE heroID = ?");
        $stmt->execute([$heroId]);

        echo json_encode([
            'success' => true,
            'message' => 'Hero section deleted successfully'
        ]);

    } elseif ($action === 'setActive') {
        $heroId = (int)($data['heroId'] ?? 0);
        if ($heroId <= 0) throw new Exception('Invalid hero section ID');

        // Deactivate all hero sections
        $stmt = $conn->prepare("UPDATE hero_section SET isActive = 0");
        $stmt->execute();

        // Activate the selected one
        $stmt = $conn->prepare("UPDATE hero_section SET isActive = 1, LastModifiedBy = ?, LastModifiedDate = NOW() WHERE heroID = ?");
        $stmt->execute([$adminId, $heroId]);

        echo json_encode([
            'success' => true,
            'message' => 'Hero section set as active successfully'
        ]);

    } else {
        throw new Exception('Unknown action: ' . $action);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>