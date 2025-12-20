<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if specific ID is requested
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT QuestionID, QuestionText, IsActive, LastDateUpdated FROM applicationquestionnaire WHERE QuestionID = ?");
        $stmt->execute([$id]);
    } else {
        // Get all questionnaires ordered by active first, then by date
        $stmt = $pdo->prepare("SELECT QuestionID, QuestionText, IsActive, LastDateUpdated FROM applicationquestionnaire ORDER BY IsActive DESC, LastDateUpdated DESC");
        $stmt->execute();
    }

    $questionnaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'questionnaires' => $questionnaires
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>