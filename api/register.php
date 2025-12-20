<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Check if registration is enabled
    $settingsStmt = $conn->prepare("
        SELECT SettingValue FROM system_settings 
        WHERE SettingKey = 'enable_member_registration'
    ");
    $settingsStmt->execute();
    $registrationSetting = $settingsStmt->fetch(PDO::FETCH_ASSOC);
    
    $registrationEnabled = $registrationSetting ? ($registrationSetting['SettingValue'] === '1' || $registrationSetting['SettingValue'] === 'true') : true;
    
    if (!$registrationEnabled) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Member registration is currently disabled']);
        exit;
    }

    // Get form data
    $lastName = trim($_POST['lastName'] ?? '');
    $firstName = trim($_POST['firstName'] ?? '');
    $middleName = trim($_POST['middleName'] ?? '');
    $extensionName = trim($_POST['extensionName'] ?? '');
    $birthDate = trim($_POST['birthDate'] ?? '');
    $email = trim($_POST['email'] ?? '');
    // Password not required during registration - will be generated upon admin approval
    $gender = trim($_POST['gender'] ?? '');
    $notApplicable = isset($_POST['notApplicable']) && $_POST['notApplicable'] === 'on';
    $marginalizedSector = trim($_POST['marginalizedSector'] ?? '');
    $otherGender = trim($_POST['otherGender'] ?? '');
    $specifyMarginalizedSector = trim($_POST['specifyMarginalizedSector'] ?? '');

    // Validation
    $errors = [];

    // Validate required fields
    if (empty($lastName)) $errors[] = 'Last name is required';
    if (empty($firstName)) $errors[] = 'First name is required';
    if (empty($birthDate)) $errors[] = 'Birth date is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (empty($gender)) $errors[] = 'Gender is required';
    // Only require marginalized sector if "Not Applicable" is not checked
    if (!$notApplicable && empty($marginalizedSector)) {
        $errors[] = 'Marginalized sector is required';
    }

    // Validate email format
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    // Check if email already exists
    if (!empty($email)) {
        $stmt = $conn->prepare("SELECT ApplicationID FROM application WHERE ApplicantEmail = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = 'Email already registered';
        }
    }

    // Validate gender and marginalized sector if "Other" is selected
    if ($gender === 'Other' && empty($otherGender)) {
        $errors[] = 'Please specify your gender';
    }
    if ($marginalizedSector === 'Other' && empty($specifyMarginalizedSector)) {
        $errors[] = 'Please specify your marginalized sector';
    }

    // Collect answers from dynamic questions
    $answers = [];
    $answerIndex = 1;
    while (isset($_POST["question$answerIndex"])) {
        $answer = trim($_POST["question$answerIndex"] ?? '');
        
        if (empty($answer)) {
            $errors[] = "Answer to question $answerIndex is required";
        } elseif (strlen($answer) < 100) {
            $errors[] = "Answer to question $answerIndex must be at least 100 characters";
        } else {
            $answers[] = [
                'index' => $answerIndex,
                'text' => $answer
            ];
        }
        $answerIndex++;
    }

    // Check if we got at least some answers
    if (empty($answers)) {
        $errors[] = 'Please answer all assessment questions';
    }

    // If there are validation errors, return them
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    // Check max members limit
    $maxMembersStmt = $conn->prepare("
        SELECT SettingValue FROM system_settings 
        WHERE SettingKey = 'max_auto_approved_members'
    ");
    $maxMembersStmt->execute();
    $maxMembersSetting = $maxMembersStmt->fetch(PDO::FETCH_ASSOC);
    $maxMembers = $maxMembersSetting ? (int)$maxMembersSetting['SettingValue'] : 1000;

    // Get auto-approve setting
    $autoApproveStmt = $conn->prepare("
        SELECT SettingValue FROM system_settings 
        WHERE SettingKey = 'auto_approve_members'
    ");
    $autoApproveStmt->execute();
    $autoApproveSetting = $autoApproveStmt->fetch(PDO::FETCH_ASSOC);
    $autoApprove = $autoApproveSetting ? ($autoApproveSetting['SettingValue'] === '1' || $autoApproveSetting['SettingValue'] === 'true') : false;

    // Check if max members reached (only matters for auto-approve)
    if ($autoApprove) {
        $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM member WHERE isActive = 1");
        $countStmt->execute();
        $memberCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($memberCount >= $maxMembers) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Maximum member limit reached. New registrations cannot be auto-approved.']);
            exit;
        }
    }

    // No password hashing during registration - password will be generated upon admin approval
    // Set passwordHash to NULL initially
    $passwordHash = NULL;

    // Determine application status based on auto-approve setting
    $applicationStatus = $autoApprove ? 1 : 0;

    // Insert into application table
    $stmt = $conn->prepare("
        INSERT INTO application 
        (FName, MName, LName, ExtensionName, Gender, specify_gender, BirthDate, ApplicantEmail, MarginalizedSector, specify_marginalizedsector, PasswordHash, SubmissionDate, ApplicationStatus)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
    ");

    $stmt->execute([
        $firstName,
        $middleName,
        $lastName,
        $extensionName,
        $gender,
        ($gender === 'Other') ? $otherGender : NULL,
        $birthDate,
        $email,
        $notApplicable ? 'Not Applicable' : $marginalizedSector,
        ($marginalizedSector === 'Other') ? $specifyMarginalizedSector : NULL,
        $passwordHash,
        $applicationStatus
    ]);

    $applicationID = $conn->lastInsertId();

    // Get active questionnaires
    $stmtActiveQuestions = $conn->prepare("
        SELECT QuestionID FROM applicationquestionnaire 
        WHERE IsActive = 1 
        ORDER BY QuestionID ASC 
        LIMIT 2
    ");
    $stmtActiveQuestions->execute();
    $activeQuestions = $stmtActiveQuestions->fetchAll(PDO::FETCH_ASSOC);

    if (empty($activeQuestions)) {
        throw new Exception('Application form is not properly configured. Please contact administrator.');
    }

    // Insert answers into applicationanswer table
    $stmtInsertAnswer = $conn->prepare("
        INSERT INTO applicationanswer (ApplicationID, QuestionID, AnswerText) 
        VALUES (?, ?, ?)
    ");
    
    for ($i = 0; $i < count($activeQuestions) && $i < count($answers); $i++) {
        $stmtInsertAnswer->execute([
            $applicationID,
            $activeQuestions[$i]['QuestionID'],
            $answers[$i]['text']
        ]);
    }

    // If auto-approve is enabled, create member record
    if ($autoApprove) {
        $memberStmt = $conn->prepare("
            INSERT INTO member (ApplicationID, Role, isActive, JoinDate)
            VALUES (?, 'member', 1, NOW())
        ");
        $memberStmt->execute([$applicationID]);
        
        $memberId = $conn->lastInsertId();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful! You have been automatically approved as a member.',
            'applicationID' => $applicationID,
            'memberId' => $memberId,
            'autoApproved' => true
        ]);
    } else {
        // Return success - pending approval
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful! Your application has been submitted for review.',
            'applicationID' => $applicationID,
            'autoApproved' => false
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>