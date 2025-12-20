<?php
header('Content-Type: application/json');

require_once 'config.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Application ID is required']);
    exit;
}

$applicationId = (int)$_GET['id'];

try {
    // Get application details
    $stmt = $conn->prepare("
        SELECT 
            ApplicationID,
            FName,
            LName,
            ApplicantEmail,
            Gender,
            specify_gender,
            MarginalizedSector,
            specify_marginalizedsector,
            BirthDate,
            SubmissionDate,
            ApplicationStatus,
            ReviewDate
        FROM application
        WHERE ApplicationID = ?
    ");
    $stmt->execute([$applicationId]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        throw new Exception('Application not found');
    }

    // Get application questionnaire answers - JOIN applicationanswer with applicationquestionnaire
    $answersStmt = $conn->prepare("
        SELECT 
            aa.AnswerID,
            aa.QuestionID,
            aa.ApplicationID,
            aa.AnswerText,
            q.QuestionText
        FROM applicationanswer aa
        LEFT JOIN applicationquestionnaire q ON aa.QuestionID = q.QuestionID
        WHERE aa.ApplicationID = ?
        ORDER BY aa.QuestionID ASC
    ");
    $answersStmt->execute([$applicationId]);
    $answers = $answersStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'application' => $application,
        'answers' => $answers
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>