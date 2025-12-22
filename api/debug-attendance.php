<?php
/**
 * Debug endpoint to check if member has attendance record for event
 * GET /api/debug-attendance.php?eventId=4
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

if (!isset($_SESSION['memberID'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$memberId = $_SESSION['memberID'];
$eventId = isset($_GET['eventId']) ? (int)$_GET['eventId'] : null;

if (!$eventId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'eventId required']);
    exit;
}

try {
    error_log("Debug: Checking attendance for memberId=$memberId, eventId=$eventId");
    
    // Check if member has registration for this event
    $regStmt = $conn->prepare("
        SELECT r.RegistrationID, r.EventID, r.MemberID, r.RegistrationDate
        FROM registration r
        WHERE r.EventID = ? AND r.MemberID = ?
    ");
    $regStmt->execute([$eventId, $memberId]);
    $registration = $regStmt->fetch(PDO::FETCH_ASSOC);
    error_log("Registration lookup: " . ($registration ? "Found RegistrationID=" . $registration['RegistrationID'] : "No registration found"));
    
    // Check if member has attendance for this event
    $attStmt = $conn->prepare("
        SELECT ea.AttendanceID, ea.RegistrationID, ea.AttendanceTime
        FROM eventattendance ea
        WHERE ea.RegistrationID IN (
            SELECT r.RegistrationID FROM registration r 
            WHERE r.EventID = ? AND r.MemberID = ?
        )
        ORDER BY ea.AttendanceTime DESC
    ");
    $attStmt->execute([$eventId, $memberId]);
    $attendance = $attStmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Attendance lookup: Found " . count($attendance) . " record(s)");
    
    // Check if member has feedback for this event
    $fbStmt = $conn->prepare("
        SELECT f.FeedbackID, f.SubmissionDate, f.Rating, f.Comments
        FROM feedback f
        WHERE f.AttendanceID IN (
            SELECT ea.AttendanceID FROM eventattendance ea
            WHERE ea.RegistrationID IN (
                SELECT r.RegistrationID FROM registration r 
                WHERE r.EventID = ? AND r.MemberID = ?
            )
        )
    ");
    $fbStmt->execute([$eventId, $memberId]);
    $feedback = $fbStmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Feedback lookup: Found " . count($feedback) . " record(s)");
    
    echo json_encode([
        'success' => true,
        'memberId' => $memberId,
        'eventId' => $eventId,
        'registration' => $registration ?: null,
        'attendance' => $attendance ?: [],
        'feedback' => $feedback ?: [],
        'attendanceCount' => count($attendance)
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log('Debug attendance error: ' . $e->getMessage());
    error_log('Debug attendance trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}
?>
