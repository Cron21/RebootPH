<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

session_start();

try {
    // Get status parameter - if 'all' is requested, get all applications
    $status = $_GET['status'] ?? 'pending';

    if ($status === 'all') {
        // Get ALL applications regardless of status
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
            ORDER BY SubmissionDate DESC
        ");
    } else {
        // Get only pending applications (default behavior)
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
            WHERE ApplicationStatus = 0
            ORDER BY SubmissionDate DESC
        ");
    }
    
    $stmt->execute();
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'applications' => $applications,
        'count' => count($applications)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>