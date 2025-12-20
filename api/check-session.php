<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

if (isset($_SESSION['memberID'])) {
    echo json_encode([
        'success' => true,
        'authenticated' => true,
        'memberID' => $_SESSION['memberID'],
        'email' => $_SESSION['email'] ?? null,
        'firstName' => $_SESSION['firstName'] ?? null,
        'lastName' => $_SESSION['lastName'] ?? null,
        'role' => $_SESSION['role'] ?? null
    ]);
} else {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'authenticated' => false,
        'message' => 'Not authenticated'
    ]);
}
exit;
?>