<?php
header('Content-Type: application/json');

require_once 'config.php';

// Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$action = $input['action'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    switch ($action) {
        case 'add':
            // Add new questionnaire
            $questionText = $input['questionText'] ?? '';
            $isActive = isset($input['isActive']) ? (int)$input['isActive'] : 0;
            $memberID = 1; // You may want to pass this from session/auth

            if (empty($questionText)) {
                throw new Exception('Question text is required');
            }

            // If setting as active, check how many are currently active
            if ($isActive) {
                $activeCount = $pdo->query("SELECT COUNT(*) FROM applicationquestionnaire WHERE IsActive = 1")->fetchColumn();
                if ($activeCount >= 2) {
                    throw new Exception('Maximum 2 active questions allowed. Please deactivate one first.');
                }
            }

            $stmt = $pdo->prepare("INSERT INTO applicationquestionnaire (QuestionText, IsActive, UpdatedByMemberID, LastDateUpdated) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$questionText, $isActive, $memberID]);

            echo json_encode([
                'success' => true,
                'message' => 'Question added successfully',
                'questionId' => $pdo->lastInsertId()
            ]);
            break;

        case 'update':
            // Update existing questionnaire
            $questionId = $input['questionId'] ?? null;
            $questionText = $input['questionText'] ?? '';
            $isActive = isset($input['isActive']) ? (int)$input['isActive'] : 0;
            $memberID = 1; // You may want to pass this from session/auth

            if (!$questionId || empty($questionText)) {
                throw new Exception('Invalid input');
            }

            // Get current active status
            $current = $pdo->prepare("SELECT IsActive FROM applicationquestionnaire WHERE QuestionID = ?");
            $current->execute([$questionId]);
            $currentActive = $current->fetchColumn();

            // If changing to active and currently inactive, check limit
            if ($isActive && !$currentActive) {
                $activeCount = $pdo->query("SELECT COUNT(*) FROM applicationquestionnaire WHERE IsActive = 1")->fetchColumn();
                if ($activeCount >= 2) {
                    throw new Exception('Maximum 2 active questions allowed. Please deactivate one first.');
                }
            }

            $stmt = $pdo->prepare("UPDATE applicationquestionnaire SET QuestionText = ?, IsActive = ?, UpdatedByMemberID = ?, LastDateUpdated = NOW() WHERE QuestionID = ?");
            $stmt->execute([$questionText, $isActive, $memberID, $questionId]);

            echo json_encode([
                'success' => true,
                'message' => 'Question updated successfully'
            ]);
            break;

        case 'delete':
            // Delete questionnaire
            $questionId = $input['questionId'] ?? null;

            if (!$questionId) {
                throw new Exception('Question ID is required');
            }

            // Check if question is active
            $check = $pdo->prepare("SELECT IsActive FROM applicationquestionnaire WHERE QuestionID = ?");
            $check->execute([$questionId]);
            $isActive = $check->fetchColumn();

            if ($isActive) {
                throw new Exception('Cannot delete an active question. Deactivate it first.');
            }

            $stmt = $pdo->prepare("DELETE FROM applicationquestionnaire WHERE QuestionID = ?");
            $stmt->execute([$questionId]);

            echo json_encode([
                'success' => true,
                'message' => 'Question deleted successfully'
            ]);
            break;

        case 'setActive':
            // Set which 2 questions are active
            $activeIds = $input['activeQuestionIds'] ?? [];
            $memberID = 1; // You may want to pass this from session/auth

            if (count($activeIds) !== 2) {
                throw new Exception('Exactly 2 questions must be selected');
            }

            // Deactivate all first
            $pdo->exec("UPDATE applicationquestionnaire SET IsActive = 0, UpdatedByMemberID = $memberID, LastDateUpdated = NOW()");

            // Activate the selected ones
            $stmt = $pdo->prepare("UPDATE applicationquestionnaire SET IsActive = 1, UpdatedByMemberID = ?, LastDateUpdated = NOW() WHERE QuestionID = ?");
            foreach ($activeIds as $id) {
                $stmt->execute([$memberID, (int)$id]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Active questions updated successfully'
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>