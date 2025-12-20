<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['email']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

$email = trim($input['email']);
$password = $input['password'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // First check if it's an approved applicant (ApplicationStatus = 1)
    $stmt = $pdo->prepare("
        SELECT ApplicationID, FName, LName, ApplicantEmail, PasswordHash
        FROM application
        WHERE ApplicantEmail = ? AND ApplicationStatus = 1
    ");
    $stmt->execute([$email]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($application && password_verify($password, $application['PasswordHash'])) {
        // Check member account status by joining with application table
        $memberStmt = $pdo->prepare("
            SELECT m.MemberID, m.Role, m.isActive 
            FROM member m
            JOIN application a ON m.ApplicationID = a.ApplicationID
            WHERE a.ApplicantEmail = ? AND m.isActive = 1
        ");
        $memberStmt->execute([$email]);
        $member = $memberStmt->fetch(PDO::FETCH_ASSOC);

        if ($member) {
            // Login successful - redirect based on role
            $_SESSION['memberID'] = $member['MemberID'];
            $_SESSION['email'] = $email;
            $_SESSION['firstName'] = $application['FName'];
            $_SESSION['lastName'] = $application['LName'];
            $_SESSION['role'] = $member['Role'];

            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'memberID' => $member['MemberID'],
                'email' => $email,
                'firstName' => $application['FName'],
                'lastName' => $application['LName'],
                'role' => $member['Role']
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Your account is not yet active. Please wait for admin approval.']);
        }
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>