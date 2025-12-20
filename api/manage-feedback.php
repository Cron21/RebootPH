<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Submit feedback
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($_SESSION['memberID'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
    
    $memberId = $_SESSION['memberID'];
    $attendanceId = isset($data['attendanceId']) ? (int)$data['attendanceId'] : null;
    $eventId = isset($data['eventId']) ? (int)$data['eventId'] : null;
    $rating = isset($data['rating']) ? (int)$data['rating'] : null;
    $comments = isset($data['comments']) ? trim($data['comments']) : '';
    $isAnonymous = isset($data['isAnonymous']) ? (int)$data['isAnonymous'] : 0;
    
    // Optional fields from feedback form
    $overallExperience = isset($data['overallExperience']) ? $data['overallExperience'] : null;
    $impact = isset($data['impact']) ? (int)$data['impact'] : null;
    $knowledge = isset($data['knowledge']) ? (int)$data['knowledge'] : null;
    
    try {
        // If attendanceId is not provided, try to find it from eventId and memberId
        if (!$attendanceId && $eventId) {
            $attendanceStmt = $conn->prepare("
                SELECT ea.AttendanceID
                FROM eventattendance ea
                JOIN registration r ON ea.RegistrationID = r.RegistrationID
                WHERE r.EventID = ? AND r.MemberID = ?
                ORDER BY ea.AttendanceTime DESC
                LIMIT 1
            ");
            $attendanceStmt->execute([$eventId, $memberId]);
            $attendance = $attendanceStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($attendance) {
                $attendanceId = (int)$attendance['AttendanceID'];
            } else {
                throw new Exception('No attendance record found for this event. You must have attended the event to provide feedback.');
            }
        }
        
        if (!$attendanceId) {
            throw new Exception('Attendance ID is required');
        }
        
        // Check if feedback already exists for this attendance
        $checkStmt = $conn->prepare("
            SELECT FeedbackID FROM feedback WHERE AttendanceID = ?
        ");
        $checkStmt->execute([$attendanceId]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing feedback
            // Use impact as rating if provided, otherwise use rating
            $finalRating = $impact ?? $rating;
            
            if ($finalRating && ($finalRating < 1 || $finalRating > 5)) {
                throw new Exception('Rating must be between 1 and 5');
            }
            
            // Check if table has additional columns (OverallExperience, KnowledgeGained)
            $tableInfo = $conn->query("SHOW COLUMNS FROM feedback")->fetchAll(PDO::FETCH_COLUMN);
            $hasOverallExperience = in_array('OverallExperience', $tableInfo);
            $hasKnowledgeGained = in_array('KnowledgeGained', $tableInfo);
            
            if ($hasOverallExperience && $hasKnowledgeGained) {
                // Table has enhanced structure
                $updateStmt = $conn->prepare("
                    UPDATE feedback 
                    SET Rating = ?, 
                        Comments = ?, 
                        IsAnonymous = ?,
                        OverallExperience = ?,
                        KnowledgeGained = ?,
                        SubmissionDate = NOW()
                    WHERE AttendanceID = ?
                ");
                $updateStmt->execute([
                    $finalRating,
                    $comments,
                    $isAnonymous,
                    $overallExperience,
                    $knowledge,
                    $attendanceId
                ]);
            } else {
                // Basic table structure
                $updateStmt = $conn->prepare("
                    UPDATE feedback 
                    SET Rating = ?, 
                        Comments = ?, 
                        IsAnonymous = ?,
                        SubmissionDate = NOW()
                    WHERE AttendanceID = ?
                ");
                $updateStmt->execute([
                    $finalRating,
                    $comments,
                    $isAnonymous,
                    $attendanceId
                ]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Feedback updated successfully',
                'feedbackId' => $existing['FeedbackID']
            ]);
        } else {
            // Insert new feedback
            // Use impact as rating if provided, otherwise use rating
            $finalRating = $impact ?? $rating;
            
            if (!$finalRating || $finalRating < 1 || $finalRating > 5) {
                throw new Exception('Rating must be between 1 and 5');
            }
            
            // Check if table has additional columns
            $tableInfo = $conn->query("SHOW COLUMNS FROM feedback")->fetchAll(PDO::FETCH_COLUMN);
            $hasOverallExperience = in_array('OverallExperience', $tableInfo);
            $hasKnowledgeGained = in_array('KnowledgeGained', $tableInfo);
            
            if ($hasOverallExperience && $hasKnowledgeGained) {
                // Table has enhanced structure
                $insertStmt = $conn->prepare("
                    INSERT INTO feedback (AttendanceID, Rating, Comments, IsAnonymous, OverallExperience, KnowledgeGained, SubmissionDate)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $insertStmt->execute([
                    $attendanceId,
                    $finalRating,
                    $comments,
                    $isAnonymous,
                    $overallExperience,
                    $knowledge
                ]);
            } else {
                // Basic table structure
                $insertStmt = $conn->prepare("
                    INSERT INTO feedback (AttendanceID, Rating, Comments, IsAnonymous, SubmissionDate)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $insertStmt->execute([
                    $attendanceId,
                    $finalRating,
                    $comments,
                    $isAnonymous
                ]);
            }
            
            $feedbackId = $conn->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Feedback submitted successfully',
                'feedbackId' => $feedbackId
            ]);
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get feedback for a specific event or attendance
    if (!isset($_SESSION['memberID'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
    
    $memberId = $_SESSION['memberID'];
    $eventId = isset($_GET['eventId']) ? (int)$_GET['eventId'] : null;
    $attendanceId = isset($_GET['attendanceId']) ? (int)$_GET['attendanceId'] : null;
    
    try {
        if ($attendanceId) {
            $stmt = $conn->prepare("
                SELECT f.*, ea.AttendanceID, r.EventID
                FROM feedback f
                JOIN eventattendance ea ON f.AttendanceID = ea.AttendanceID
                JOIN registration r ON ea.RegistrationID = r.RegistrationID
                WHERE f.AttendanceID = ? AND r.MemberID = ?
            ");
            $stmt->execute([$attendanceId, $memberId]);
        } elseif ($eventId) {
            $stmt = $conn->prepare("
                SELECT f.*, ea.AttendanceID, r.EventID
                FROM feedback f
                JOIN eventattendance ea ON f.AttendanceID = ea.AttendanceID
                JOIN registration r ON ea.RegistrationID = r.RegistrationID
                WHERE r.EventID = ? AND r.MemberID = ?
                ORDER BY f.SubmissionDate DESC
                LIMIT 1
            ");
            $stmt->execute([$eventId, $memberId]);
        } else {
            throw new Exception('Event ID or Attendance ID is required');
        }
        
        $feedback = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($feedback) {
            echo json_encode([
                'success' => true,
                'feedback' => $feedback
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'feedback' => null
            ]);
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>

