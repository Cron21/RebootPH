<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

try {
    // Get active benefits for display (optional filter parameter)
    $onlyActive = isset($_GET['active']) && $_GET['active'] === '1';
    
    if ($onlyActive) {
        $stmt = $conn->prepare("SELECT BenefitsID, Title, Description, isActive FROM member_benefits WHERE isActive = 1 ORDER BY BenefitsID ASC");
    } else {
        $stmt = $conn->prepare("SELECT BenefitsID, Title, Description, isActive FROM member_benefits ORDER BY BenefitsID ASC");
    }
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