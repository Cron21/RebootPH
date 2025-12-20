<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

try {
    $newsletterId = $_GET['id'] ?? null;

    if (!$newsletterId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Newsletter ID is required']);
        exit;
    }

    // Fetch newsletter details
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
    $newsletter = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$newsletter) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Newsletter not found']);
        exit;
    }

    // Fetch images for this newsletter
    $imgStmt = $conn->prepare("SELECT ImagePath FROM images WHERE NewsletterID = ? ORDER BY ImageID ASC");
    $imgStmt->execute([$newsletterId]);
    $images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
    $newsletter['images'] = array_map(function($img) { return $img['ImagePath']; }, $images);

    // Format publish date
    if ($newsletter['PublishDate']) {
        $date = new DateTime($newsletter['PublishDate']);
        $newsletter['PublishDateFormatted'] = $date->format('F d, Y');
    }

    echo json_encode([
        'success' => true,
        'newsletter' => $newsletter
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>