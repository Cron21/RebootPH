<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get all members with their application details
    $stmt = $pdo->prepare("
        SELECT 
            m.MemberID,
            m.ApplicationID,
            m.Role,
            m.isActive,
            m.JoinDate,
            m.ProfileImage,
            a.FName,
            a.LName,
            a.ApplicantEmail,
            a.BirthDate
        FROM member m
        JOIN application a ON m.ApplicationID = a.ApplicationID
        ORDER BY m.JoinDate DESC
    ");
    $stmt->execute();
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'members' => $members,
        'currentMemberId' => $_SESSION['memberID'] ?? null
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>