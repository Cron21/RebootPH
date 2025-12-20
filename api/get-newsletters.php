<?php
header('Content-Type: application/json');

require_once 'config.php';

try {
    $newsletterId = $_GET['id'] ?? null;

    if ($newsletterId) {
        // Fetch single newsletter with images
        $stmt = $conn->prepare("
            SELECT 
                NewsletterID,
                Title,
                Content,
                PublishDate,
                CreatedByAdminID,
                LastModifiedBy,
                LastModifiedDate
            FROM newsletter
            WHERE NewsletterID = ?
        ");
        $stmt->execute([$newsletterId]);
        $newsletters = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch images for this newsletter
        if (!empty($newsletters)) {
            $imgStmt = $conn->prepare("SELECT ImagePath FROM images WHERE NewsletterID = ? ORDER BY ImageID ASC");
            $imgStmt->execute([$newsletterId]);
            $images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
            $newsletters[0]['images'] = array_map(function($img) { return $img['ImagePath']; }, $images);
        }
    } else {
        // Fetch all newsletters
        $stmt = $conn->prepare("
            SELECT 
                NewsletterID,
                Title,
                Content,
                PublishDate,
                CreatedByAdminID,
                LastModifiedBy,
                LastModifiedDate
            FROM newsletter
            ORDER BY PublishDate DESC
        ");
        $stmt->execute();
        $newsletters = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch images for each newsletter
        foreach ($newsletters as &$newsletter) {
            $imgStmt = $conn->prepare("SELECT ImagePath FROM images WHERE NewsletterID = ? ORDER BY ImageID ASC");
            $imgStmt->execute([$newsletter['NewsletterID']]);
            $images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
            $newsletter['images'] = array_map(function($img) { return $img['ImagePath']; }, $images);
        }
    }

    echo json_encode([
        'success' => true,
        'newsletters' => $newsletters,
        'count' => count($newsletters)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>