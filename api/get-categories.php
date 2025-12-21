<?php
header('Content-Type: application/json');

require_once 'config.php';

try {
    $stmt = $conn->prepare("
        SELECT 
            CategoryID,
            Type
        FROM category
        ORDER BY Type ASC
    ");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'categories' => $categories,
        'count' => count($categories)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>