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
        $categoryId = (int)($data['categoryId'] ?? 0);
        $isHighlighted = (int)($data['isHighlighted'] ?? 0);
        $imagePaths = $data['imagePaths'] ?? []; // Array of image paths

        if (!$title) throw new Exception('Title is required');
        if (!$description) throw new Exception('Description is required');
        if ($categoryId <= 0) throw new Exception('Valid category is required');
        if (empty($imagePaths)) throw new Exception('At least one image is required');

        $stmt = $conn->prepare("
            INSERT INTO initiatives (Title, Description, CategoryID, isHighlighted, CreateByAdminID, PublishDate, LastModifiedBy, LastModifiedDate)
            VALUES (?, ?, ?, ?, ?, NOW(), ?, NOW())
        ");
        $stmt->execute([$title, $description, $categoryId, $isHighlighted, $adminId, $adminId]);

        $initiativeId = $conn->lastInsertId();

        // Link images to initiative
        $imageStmt = $conn->prepare("
            INSERT INTO images (InitiativeID, ImagePath)
            VALUES (?, ?)
        ");

        foreach ($imagePaths as $imagePath) {
            $imageStmt->execute([$initiativeId, $imagePath]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Initiative created successfully',
            'initiativeId' => $initiativeId
        ]);

    } elseif ($action === 'update') {
        $initiativeId = (int)($data['initiativeId'] ?? 0);
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $categoryId = (int)($data['categoryId'] ?? 0);
        $isHighlighted = (int)($data['isHighlighted'] ?? 0);
        $imagePaths = $data['imagePaths'] ?? [];
        $existingImages = $data['existingImages'] ?? []; // Images that were already in DB

        if ($initiativeId <= 0) throw new Exception('Invalid initiative ID');
        if (!$title) throw new Exception('Title is required');
        if ($categoryId <= 0) throw new Exception('Valid category is required');

        $stmt = $conn->prepare("
            UPDATE initiatives 
            SET Title = ?, Description = ?, CategoryID = ?, isHighlighted = ?, LastModifiedBy = ?, LastModifiedDate = NOW()
            WHERE InitiativeID = ?
        ");
        $stmt->execute([$title, $description, $categoryId, $isHighlighted, $adminId, $initiativeId]);

        // Update images if provided
        if (!empty($imagePaths)) {
            // Combine existing and new images
            $allImagesToKeep = array_merge($existingImages, $imagePaths);

            // Remove images that are NOT in the combined list (user deleted them)
            $deleteStmt = $conn->prepare("DELETE FROM images WHERE InitiativeID = ? AND ImagePath NOT IN (" . implode(',', array_fill(0, count($allImagesToKeep), '?')) . ")");
            $deleteStmt->execute(array_merge([$initiativeId], $allImagesToKeep));

            // Add only new images (not already in database)
            $imageStmt = $conn->prepare("
                INSERT INTO images (InitiativeID, ImagePath)
                VALUES (?, ?)
            ");

            foreach ($imagePaths as $imagePath) {
                // Only insert if it's not an existing image
                if (!in_array($imagePath, $existingImages)) {
                    $imageStmt->execute([$initiativeId, $imagePath]);
                }
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Initiative updated successfully'
        ]);

    } elseif ($action === 'delete') {
        $initiativeId = (int)($data['initiativeId'] ?? 0);
        if ($initiativeId <= 0) throw new Exception('Invalid initiative ID');

        // Delete associated images
        $stmt = $conn->prepare("DELETE FROM images WHERE InitiativeID = ?");
        $stmt->execute([$initiativeId]);

        // Delete initiative
        $stmt = $conn->prepare("DELETE FROM initiatives WHERE InitiativeID = ?");
        $stmt->execute([$initiativeId]);

        echo json_encode([
            'success' => true,
            'message' => 'Initiative deleted successfully'
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