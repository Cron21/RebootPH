<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

    // Check if user is logged in
    if (!isset($_SESSION['memberID'])) {
        throw new Exception('Not authenticated');
    }

    if ($action === 'create') {
        // --- CREATE CATEGORY ---
        $type = trim($data['categoryName'] ?? '');

        if (empty($type)) {
            throw new Exception('Type is required');
        }

        $stmt = $conn->prepare("
            INSERT INTO category (Type)
            VALUES (?)
        ");
        $stmt->execute([$type]);

        echo json_encode([
            'success' => true,
            'message' => 'Category created successfully',
            'categoryID' => $conn->lastInsertId()
        ]);

    } elseif ($action === 'update') {
        // --- UPDATE CATEGORY ---
        $categoryId = (int)($data['categoryId'] ?? 0);
        $type = trim($data['categoryName'] ?? '');

        if ($categoryId <= 0) {
            throw new Exception('Invalid category ID');
        }

        if (empty($type)) {
            throw new Exception('Type is required');
        }

        // Check if category exists
        $checkStmt = $conn->prepare("SELECT CategoryID FROM category WHERE CategoryID = ?");
        $checkStmt->execute([$categoryId]);
        if ($checkStmt->rowCount() === 0) {
            throw new Exception('Category not found');
        }

        $stmt = $conn->prepare("
            UPDATE category 
            SET Type = ?
            WHERE CategoryID = ?
        ");
        $stmt->execute([$type, $categoryId]);

        echo json_encode([
            'success' => true,
            'message' => 'Category updated successfully'
        ]);

    } elseif ($action === 'delete') {
        // --- DELETE CATEGORY ---
        $categoryId = (int)($data['categoryId'] ?? 0);

        if ($categoryId <= 0) {
            throw new Exception('Invalid category ID');
        }

        // Check if category exists
        $checkStmt = $conn->prepare("SELECT CategoryID FROM category WHERE CategoryID = ?");
        $checkStmt->execute([$categoryId]);
        if ($checkStmt->rowCount() === 0) {
            throw new Exception('Category not found');
        }

        // Check if category is used by any initiatives
        $useCheckStmt = $conn->prepare("SELECT COUNT(*) as count FROM initiative WHERE CategoryID = ?");
        $useCheckStmt->execute([$categoryId]);
        $useResult = $useCheckStmt->fetch();

        if ($useResult['count'] > 0) {
            throw new Exception('Cannot delete category - it is in use by ' . $useResult['count'] . ' initiative(s)');
        }

        // Delete the category
        $stmt = $conn->prepare("DELETE FROM category WHERE CategoryID = ?");
        $stmt->execute([$categoryId]);

        echo json_encode([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);

    } else {
        throw new Exception('Unknown action: ' . $action);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
