<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';
session_start();

// Check if user is authenticated
if (!isset($_SESSION['memberID'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    $eventId = isset($_GET['eventId']) ? (int)$_GET['eventId'] : null;
    $action = isset($_GET['action']) ? $_GET['action'] : 'list';
    
    if ($action === 'events') {
        // Get all completed events (where the end time has passed)
        $stmt = $conn->prepare("
            SELECT DISTINCT
                e.EventID,
                p.Title,
                p.ProposedDate,
                p.StartTime,
                p.EndTime,
                p.Venue,
                COUNT(DISTINCT ea.AttendanceID) as TotalAttendees,
                COUNT(DISTINCT f.FeedbackID) as FeedbackCount
            FROM event e
            JOIN proposal p ON e.ProposalID = p.ProposalID
            LEFT JOIN registration r ON e.EventID = r.EventID
            LEFT JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            LEFT JOIN feedback f ON ea.AttendanceID = f.AttendanceID
            WHERE p.Status = 'Approved' 
              AND CONCAT(p.ProposedDate, ' ', p.EndTime) < NOW()
            GROUP BY e.EventID, p.Title, p.ProposedDate, p.StartTime, p.EndTime, p.Venue
            ORDER BY p.ProposedDate DESC
        ");
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'events' => $events,
            'count' => count($events)
        ]);
        
    } elseif ($action === 'members' && $eventId) {
        // Get all attendees (members + non-members) who attended the event with their feedback status
        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all'; // 'all', 'members', or 'non-members'
        
        if ($filter === 'members') {
            // Members only
            $stmt = $conn->prepare("
                SELECT
                    m.MemberID,
                    NULL as non_MemberID,
                    CONCAT(app.FName, ' ', app.LName) as MemberName,
                    app.FName,
                    app.LName,
                    app.ApplicantEmail as Email,
                    'Member' as UserType,
                    ea.AttendanceID,
                    ea.AttendanceTime,
                    f.FeedbackID,
                    f.Rating,
                    f.Comments,
                    f.OverallExperience,
                    f.KnowledgeGained,
                    f.SubmissionDate as FeedbackDate,
                    CASE WHEN f.FeedbackID IS NOT NULL THEN 1 ELSE 0 END as HasFeedback
                FROM registration r
                INNER JOIN member m ON r.MemberID = m.MemberID
                INNER JOIN application app ON m.ApplicationID = app.ApplicationID
                INNER JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
                LEFT JOIN feedback f ON ea.AttendanceID = f.AttendanceID
                WHERE r.EventID = ?
                ORDER BY app.FName ASC, app.LName ASC
            ");
            $stmt->execute([$eventId]);
        } elseif ($filter === 'non-members') {
            // Non-members only
            $stmt = $conn->prepare("
                SELECT
                    NULL as MemberID,
                    nm.non_memberID as non_MemberID,
                    CONCAT(nm.Fname, ' ', nm.Lname) as MemberName,
                    nm.Fname as FName,
                    nm.Lname as LName,
                    nm.Email,
                    'Non-Member' as UserType,
                    ea.AttendanceID,
                    ea.AttendanceTime,
                    f.FeedbackID,
                    f.Rating,
                    f.Comments,
                    f.OverallExperience,
                    f.KnowledgeGained,
                    f.SubmissionDate as FeedbackDate,
                    CASE WHEN f.FeedbackID IS NOT NULL THEN 1 ELSE 0 END as HasFeedback
                FROM registration r
                INNER JOIN non_member nm ON r.non_MemberID = nm.non_memberID
                INNER JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
                LEFT JOIN feedback f ON ea.AttendanceID = f.AttendanceID
                WHERE r.EventID = ?
                ORDER BY nm.Fname ASC, nm.Lname ASC
            ");
            $stmt->execute([$eventId]);
        } else {
            // All - combine both members and non-members
            $stmt = $conn->prepare("
                SELECT
                    m.MemberID,
                    NULL as non_MemberID,
                    CONCAT(app.FName, ' ', app.LName) as MemberName,
                    app.FName,
                    app.LName,
                    app.ApplicantEmail as Email,
                    'Member' as UserType,
                    ea.AttendanceID,
                    ea.AttendanceTime,
                    f.FeedbackID,
                    f.Rating,
                    f.Comments,
                    f.OverallExperience,
                    f.KnowledgeGained,
                    f.SubmissionDate as FeedbackDate,
                    CASE WHEN f.FeedbackID IS NOT NULL THEN 1 ELSE 0 END as HasFeedback
                FROM registration r
                INNER JOIN member m ON r.MemberID = m.MemberID
                INNER JOIN application app ON m.ApplicationID = app.ApplicationID
                INNER JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
                LEFT JOIN feedback f ON ea.AttendanceID = f.AttendanceID
                WHERE r.EventID = ?
                
                UNION ALL
                
                SELECT
                    NULL as MemberID,
                    nm.non_memberID as non_MemberID,
                    CONCAT(nm.Fname, ' ', nm.Lname) as MemberName,
                    nm.Fname as FName,
                    nm.Lname as LName,
                    nm.Email,
                    'Non-Member' as UserType,
                    ea.AttendanceID,
                    ea.AttendanceTime,
                    f.FeedbackID,
                    f.Rating,
                    f.Comments,
                    f.OverallExperience,
                    f.KnowledgeGained,
                    f.SubmissionDate as FeedbackDate,
                    CASE WHEN f.FeedbackID IS NOT NULL THEN 1 ELSE 0 END as HasFeedback
                FROM registration r
                INNER JOIN non_member nm ON r.non_MemberID = nm.non_memberID
                INNER JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
                LEFT JOIN feedback f ON ea.AttendanceID = f.AttendanceID
                WHERE r.EventID = ?
                
                ORDER BY FName ASC, LName ASC
            ");
            $stmt->execute([$eventId, $eventId]);
        }
        
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'members' => $members,
            'count' => count($members),
            'feedbackCount' => count(array_filter($members, fn($m) => $m['HasFeedback']))
        ]);
        
    } elseif ($action === 'view-feedback' && isset($_GET['feedbackId'])) {
        // Get detailed feedback
        $feedbackId = (int)$_GET['feedbackId'];
        
        $stmt = $conn->prepare("
            SELECT
                f.FeedbackID,
                f.AttendanceID,
                f.Rating,
                f.Comments,
                f.OverallExperience,
                f.KnowledgeGained,
                f.IsAnonymous,
                f.SubmissionDate,
                m.MemberID,
                CONCAT(app.FName, ' ', app.LName) as MemberName,
                app.FName,
                app.LName,
                app.ApplicantEmail as Email,
                p.Title as EventTitle,
                p.ProposedDate,
                e.EventID
            FROM feedback f
            JOIN eventattendance ea ON f.AttendanceID = ea.AttendanceID
            JOIN registration r ON ea.RegistrationID = r.RegistrationID
            JOIN member m ON r.MemberID = m.MemberID
            JOIN application app ON m.ApplicationID = app.ApplicationID
            JOIN event e ON r.EventID = e.EventID
            JOIN proposal p ON e.ProposalID = p.ProposalID
            WHERE f.FeedbackID = ?
        ");
        $stmt->execute([$feedbackId]);
        $feedback = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($feedback) {
            echo json_encode([
                'success' => true,
                'feedback' => $feedback
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Feedback not found']);
        }

    } elseif ($action === 'average-rating' && $eventId) {
        // Get average rating for an event
        $stmt = $conn->prepare("
            SELECT
                AVG(f.Rating) as AverageRating,
                COUNT(f.FeedbackID) as TotalRatings
            FROM feedback f
            JOIN eventattendance ea ON f.AttendanceID = ea.AttendanceID
            JOIN registration r ON ea.RegistrationID = r.RegistrationID
            WHERE r.EventID = ? AND f.Rating IS NOT NULL
        ");
        $stmt->execute([$eventId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['TotalRatings'] > 0) {
            echo json_encode([
                'success' => true,
                'averageRating' => (float)$result['AverageRating'],
                'totalRatings' => (int)$result['TotalRatings']
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'averageRating' => null,
                'totalRatings' => 0
            ]);
        }
        
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>