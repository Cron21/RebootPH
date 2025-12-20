<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password_db);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch highlights with JOIN to get image data
    $stmt = $conn->prepare("
        SELECT 
            i.InitiativeID as id,
            i.Title as title,
            i.Description as description,
            i.Category as category,
            img.image_URL as image
        FROM initiatives i
        LEFT JOIN image_URL img ON i.Image = img.ImageID
        WHERE i.isHighlighted = 1
        ORDER BY i.PublishDate DESC
    ");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group images by initiative
    $highlights = [];
    foreach($results as $row) {
        $initiativeId = $row['id'];
        
        if (!isset($highlights[$initiativeId])) {
            $highlights[$initiativeId] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'category' => $row['category'],
                'images' => []
            ];
        }
        
        if ($row['image']) {
            $highlights[$initiativeId]['images'][] = $row['image'];
        }
    }
    
    echo json_encode(array_values($highlights));
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>