<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

try {
    $stmt = $conn->prepare("SELECT BenefitID, Title, Description, IconClass, `Order`, isActive FROM member_benefits ORDER BY `Order` ASC");
    $stmt->execute();
    $benefits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'benefits' => $benefits
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}