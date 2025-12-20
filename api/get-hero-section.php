<?php
header('Content-Type: application/json');

require_once 'config.php';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password_db);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $heroId = $_GET['id'] ?? null;

    if ($heroId) {
        // Fetch single hero section
        $stmt = $conn->prepare("
            SELECT 
                heroID,
                Title,
                description,
                isActive,
                PublishDate,
                CreateByAdminID,
                LastModifiedBy,
                LastModifiedDate
            FROM hero_section
            WHERE heroID = ?
        ");
        $stmt->execute([$heroId]);
    } else {
        // Fetch all hero sections
        $stmt = $conn->prepare("
            SELECT 
                heroID,
                Title,
                description,
                isActive,
                PublishDate,
                CreateByAdminID,
                LastModifiedBy,
                LastModifiedDate
            FROM hero_section
            ORDER BY PublishDate DESC
        ");
        $stmt->execute();
    }

    $heroSections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'heroSections' => $heroSections,
        'count' => count($heroSections)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>