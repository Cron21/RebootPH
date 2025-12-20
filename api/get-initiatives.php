<?php
header('Content-Type: application/json');

require_once 'config.php';

try {
    $initiativeId = $_GET['id'] ?? null;

    if ($initiativeId) {
        // Fetch single initiative with category and images
        $stmt = $conn->prepare("
            SELECT 
                i.InitiativeID,
                i.Title,
                i.Description,
                i.CategoryID,
                c.Type as Category,
                i.isHighlighted,
                i.PublishDate,
                i.CreateByAdminID,
                i.LastModifiedBy,
                i.LastModifiedDate
            FROM initiatives i
            LEFT JOIN category c ON i.CategoryID = c.CategoryID
            WHERE i.InitiativeID = ?
        ");
        $stmt->execute([$initiativeId]);
        $initiatives = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch images for this initiative
        if (!empty($initiatives)) {
            $imgStmt = $conn->prepare("SELECT ImagePath FROM images WHERE InitiativeID = ? ORDER BY ImageID ASC");
            $imgStmt->execute([$initiativeId]);
            $images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
            $initiatives[0]['images'] = array_map(function($img) { return $img['ImagePath']; }, $images);
        }
    } else {
        // Fetch all initiatives with category names
        $stmt = $conn->prepare("
            SELECT 
                i.InitiativeID,
                i.Title,
                i.Description,
                i.CategoryID,
                c.Type as Category,
                i.isHighlighted,
                i.PublishDate,
                i.CreateByAdminID,
                i.LastModifiedBy,
                i.LastModifiedDate
            FROM initiatives i
            LEFT JOIN category c ON i.CategoryID = c.CategoryID
            ORDER BY i.PublishDate DESC
        ");
        $stmt->execute();
        $initiatives = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch images for each initiative
        foreach ($initiatives as &$initiative) {
            $imgStmt = $conn->prepare("SELECT ImagePath FROM images WHERE InitiativeID = ? ORDER BY ImageID ASC");
            $imgStmt->execute([$initiative['InitiativeID']]);
            $images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
            $initiative['images'] = array_map(function($img) { return $img['ImagePath']; }, $images);
        }
    }

    echo json_encode([
        'success' => true,
        'initiatives' => $initiatives,
        'count' => count($initiatives)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>