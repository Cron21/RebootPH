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
        $content = trim($data['content'] ?? '');
        $imagePaths = $data['imagePaths'] ?? [];

        if (!$title) throw new Exception('Title is required');
        if (!$content) throw new Exception('Content is required');

        $stmt = $conn->prepare("
            INSERT INTO newsletter (Title, Content, PublishDate, CreatedByAdminID, LastModifiedBy, LastModifiedDate)
            VALUES (?, ?, NOW(), ?, ?, NOW())
        ");
        $stmt->execute([$title, $content, $adminId, $adminId]);

        $newsletterId = $conn->lastInsertId();

        // Link images to initiative
        $imageStmt = $conn->prepare("
            INSERT INTO images (InitiativeID, ImagePath)
            VALUES (?, ?)
        ");

        foreach ($imagePaths as $imagePath) {
            $imageStmt->execute([$newsletterId, $imagePath]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Newsletter published successfully',
            'newsletterId' => $newsletterId
        ]);

    } elseif ($action === 'update') {
        $newsletterId = (int)($data['newsletterId'] ?? 0);
        $title = trim($data['title'] ?? '');
        $content = trim($data['content'] ?? '');
        $imagePaths = $data['imagePaths'] ?? [];
        $existingImages = $data['existingImages'] ?? []; // Images that were already in DB

        if ($newsletterId <= 0) throw new Exception('Invalid newsletter ID');
        if (!$title) throw new Exception('Title is required');
        if (!$content) throw new Exception('Content is required');

        $stmt = $conn->prepare("
            UPDATE newsletter 
            SET Title = ?, Content = ?, LastModifiedBy = ?, LastModifiedDate = NOW()
            WHERE NewsletterID = ?
        ");
        $stmt->execute([$title, $content, $adminId, $newsletterId]);

        // Update images if provided
        if (!empty($imagePaths) || !empty($existingImages)) {
            // Combine existing and new images
            $allImagesToKeep = array_merge($existingImages, $imagePaths);

            // Remove images that are NOT in the combined list (user deleted them)
            if (!empty($allImagesToKeep)) {
                $deleteStmt = $conn->prepare("DELETE FROM images WHERE InitiativeID = ? AND ImagePath NOT IN (" . implode(',', array_fill(0, count($allImagesToKeep), '?')) . ")");
                $deleteStmt->execute(array_merge([$newsletterId], $allImagesToKeep));
            } else {
                // If no images to keep, delete all
                $deleteStmt = $conn->prepare("DELETE FROM images WHERE NewsletterID = ?");
                $deleteStmt->execute([$newsletterId]);
            }

            // Add only new images (not already in database)
            $imageStmt = $conn->prepare("
                INSERT INTO images (NewsletterID, ImagePath)
                VALUES (?, ?)
            ");

            foreach ($imagePaths as $imagePath) {
                // Only insert if it's not an existing image
                if (!in_array($imagePath, $existingImages)) {
                    $imageStmt->execute([$newsletterId, $imagePath]);
                }
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Newsletter updated successfully'
        ]);

    } elseif ($action === 'delete') {
        $newsletterId = (int)($data['newsletterId'] ?? 0);
        if ($newsletterId <= 0) throw new Exception('Invalid newsletter ID');

        // Delete associated images
        $stmt = $conn->prepare("DELETE FROM images WHERE NewsletterID = ?");
        $stmt->execute([$newsletterId]);

        // Delete newsletter
        $stmt = $conn->prepare("DELETE FROM newsletter WHERE NewsletterID = ?");
        $stmt->execute([$newsletterId]);

        echo json_encode([
            'success' => true,
            'message' => 'Newsletter deleted successfully'
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