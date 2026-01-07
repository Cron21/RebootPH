<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

// Check if user is authenticated
if (!isset($_SESSION['memberID'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Verify connection is established
if (!isset($conn)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection not established']);
    exit;
}

$reportType = $_GET['type'] ?? 'summary';
$startDate = $_GET['startDate'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['endDate'] ?? date('Y-m-d');

// Start output buffering to catch any stray output
ob_start();

try {
    switch ($reportType) {
        case 'summary':
            $response = getSummaryReport($conn, $startDate, $endDate);
            break;
        case 'events':
            $response = getEventsReport($conn, $startDate, $endDate);
            break;
        case 'members':
            $response = getMembersReport($conn, $startDate, $endDate);
            break;
        case 'trends':
            $response = getTrendsReport($conn, $startDate, $endDate);
            break;
        case 'initiatives':
            $response = getInitiativesReport($conn, $startDate, $endDate);
            break;
        default:
            $response = ['success' => false, 'message' => 'Invalid report type'];
    }
    
    // Clear any buffered output
    ob_end_clean();
    
    echo json_encode($response);
} catch (Exception $e) {
    // Clear any buffered output
    ob_end_clean();
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function getSummaryReport($conn, $startDate, $endDate) {
    try {
        // Total Events held in period (events with registrations and attendance)
        $totalEventsStmt = $conn->prepare("
            SELECT COUNT(DISTINCT e.EventID) as count 
            FROM event e
            JOIN proposal p ON e.ProposalID = p.ProposalID
            JOIN registration r ON e.EventID = r.EventID
            JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            WHERE DATE(p.ProposedDate) BETWEEN ? AND ?
        ");
        $totalEventsStmt->execute([$startDate, $endDate]);
        $totalEvents = (int)($totalEventsStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);

        // All Registrations (members + non-members)
        $totalRegistrationsStmt = $conn->prepare("
            SELECT COUNT(DISTINCT r.RegistrationID) as count 
            FROM registration r
            JOIN event e ON r.EventID = e.EventID
            JOIN proposal p ON e.ProposalID = p.ProposalID
            WHERE DATE(p.ProposedDate) BETWEEN ? AND ?
        ");
        $totalRegistrationsStmt->execute([$startDate, $endDate]);
        $totalRegistrations = (int)($totalRegistrationsStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);

        // Actual Attendees (members + non-members)
        $totalAttendeesStmt = $conn->prepare("
            SELECT COUNT(DISTINCT ea.AttendanceID) as count 
            FROM eventattendance ea
            JOIN registration r ON ea.RegistrationID = r.RegistrationID
            JOIN event e ON r.EventID = e.EventID
            JOIN proposal p ON e.ProposalID = p.ProposalID
            WHERE DATE(p.ProposedDate) BETWEEN ? AND ?
        ");
        $totalAttendeesStmt->execute([$startDate, $endDate]);
        $totalAttendees = (int)($totalAttendeesStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);

        // Average Attendance Rate
        $avgAttendanceRate = $totalRegistrations > 0 ? round(($totalAttendees / $totalRegistrations) * 100, 1) : 0;

        // Total Active Members
        $activeMembersStmt = $conn->prepare("
            SELECT COUNT(*) as count FROM member WHERE isActive = 1
        ");
        $activeMembersStmt->execute();
        $totalActiveMembers = (int)($activeMembersStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);

        // Total Non-Members
        $totalNonMembersStmt = $conn->prepare("
            SELECT COUNT(DISTINCT nm.non_memberID) as count 
            FROM non_member nm
            JOIN registration r ON nm.non_memberID = r.non_MemberID
            WHERE DATE(r.RegistrationDate) BETWEEN ? AND ?
        ");
        $totalNonMembersStmt->execute([$startDate, $endDate]);
        $totalNonMembers = (int)($totalNonMembersStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);

        // Average Feedback Rating
        $avgFeedbackStmt = $conn->prepare("
            SELECT 
                COALESCE(ROUND(AVG(f.Rating), 2), 0) as avgRating,
                COUNT(f.FeedbackID) as totalFeedback
            FROM feedback f
            JOIN eventattendance ea ON f.AttendanceID = ea.AttendanceID
            JOIN registration r ON ea.RegistrationID = r.RegistrationID
            JOIN event e ON r.EventID = e.EventID
            JOIN proposal p ON e.ProposalID = p.ProposalID
            WHERE DATE(p.ProposedDate) BETWEEN ? AND ?
        ");
        $avgFeedbackStmt->execute([$startDate, $endDate]);
        $feedbackData = $avgFeedbackStmt->fetch(PDO::FETCH_ASSOC);
        $avgFeedbackRating = (float)($feedbackData['avgRating'] ?? 0);
        $totalFeedback = (int)($feedbackData['totalFeedback'] ?? 0);

        // Total Initiatives
        $totalInitiativesStmt = $conn->prepare("
            SELECT COUNT(*) as count FROM initiatives
            WHERE DATE(PublishDate) BETWEEN ? AND ?
        ");
        $totalInitiativesStmt->execute([$startDate, $endDate]);
        $totalInitiatives = (int)($totalInitiativesStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);

        // Previous period comparison
        $daysDiff = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24);
        $prevStart = date('Y-m-d', strtotime($startDate) - ($daysDiff * 86400) - 86400);
        $prevEnd = date('Y-m-d', strtotime($startDate) - 86400);

        $prevEventsStmt = $conn->prepare("
            SELECT COUNT(DISTINCT e.EventID) as count 
            FROM event e
            JOIN proposal p ON e.ProposalID = p.ProposalID
            JOIN registration r ON e.EventID = r.EventID
            JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            WHERE DATE(p.ProposedDate) BETWEEN ? AND ?
        ");
        $prevEventsStmt->execute([$prevStart, $prevEnd]);
        $prevEvents = (int)($prevEventsStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);

        $eventsChange = $prevEvents > 0 ? round((($totalEvents - $prevEvents) / $prevEvents) * 100, 1) : ($totalEvents > 0 ? 100 : 0);

        $prevRegistrationsStmt = $conn->prepare("
            SELECT COUNT(DISTINCT r.RegistrationID) as count 
            FROM registration r
            JOIN event e ON r.EventID = e.EventID
            JOIN proposal p ON e.ProposalID = p.ProposalID
            WHERE DATE(p.ProposedDate) BETWEEN ? AND ?
        ");
        $prevRegistrationsStmt->execute([$prevStart, $prevEnd]);
        $prevRegistrations = (int)($prevRegistrationsStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);

        $registrationsChange = $prevRegistrations > 0 ? round((($totalRegistrations - $prevRegistrations) / $prevRegistrations) * 100, 1) : ($totalRegistrations > 0 ? 100 : 0);

        return [
            'success' => true,
            'summary' => [
                'totalEvents' => $totalEvents,
                'eventsChange' => (float)$eventsChange,
                'totalRegistrations' => $totalRegistrations,
                'registrationsChange' => (float)$registrationsChange,
                'totalAttendees' => $totalAttendees,
                'avgAttendanceRate' => (float)$avgAttendanceRate,
                'totalActiveMembers' => $totalActiveMembers,
                'totalNonMembers' => $totalNonMembers,
                'avgFeedbackRating' => (float)$avgFeedbackRating,
                'totalFeedback' => $totalFeedback,
                'totalInitiatives' => $totalInitiatives
            ]
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function getEventsReport($conn, $startDate, $endDate) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                e.EventID,
                p.Title as eventName,
                DATE_FORMAT(p.ProposedDate, '%b %d, %Y') as eventDate,
                p.Venue,
                COUNT(DISTINCT r.RegistrationID) as registered,
                SUM(CASE WHEN r.MemberID IS NOT NULL THEN 1 ELSE 0 END) as memberRegistrations,
                SUM(CASE WHEN r.non_MemberID IS NOT NULL THEN 1 ELSE 0 END) as nonMemberRegistrations,
                COUNT(DISTINCT ea.AttendanceID) as attended,
                CASE 
                    WHEN COUNT(DISTINCT r.RegistrationID) > 0 
                    THEN ROUND(COUNT(DISTINCT ea.AttendanceID) / COUNT(DISTINCT r.RegistrationID) * 100, 1)
                    ELSE 0
                END as attendanceRate,
                COALESCE(ROUND(AVG(f.Rating), 2), 0) as avgRating,
                COUNT(f.FeedbackID) as feedbackCount
            FROM event e
            LEFT JOIN proposal p ON e.ProposalID = p.ProposalID
            LEFT JOIN registration r ON e.EventID = r.EventID
            LEFT JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            LEFT JOIN feedback f ON ea.AttendanceID = f.AttendanceID
            WHERE DATE(p.ProposedDate) BETWEEN ? AND ?
            GROUP BY e.EventID, p.Title, p.ProposedDate, p.Venue
            ORDER BY p.ProposedDate DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'data' => $events,
            'count' => count($events)
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function getMembersReport($conn, $startDate, $endDate) {
    try {
        // All Members (including staff, officers, directors)
        $memberStmt = $conn->prepare("
            SELECT 
                'All Members' as type,
                COUNT(*) as totalCount,
                SUM(CASE WHEN isActive = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN isActive = 0 THEN 1 ELSE 0 END) as inactive
            FROM member
        ");
        $memberStmt->execute();
        $memberData = $memberStmt->fetch(PDO::FETCH_ASSOC);

        // Non-Members registered in period
        $nonMemberStmt = $conn->prepare("
            SELECT 
                'Non-Members' as type,
                COUNT(DISTINCT nm.non_memberID) as totalCount,
                COUNT(DISTINCT nm.non_memberID) as active,
                0 as inactive
            FROM non_member nm
            JOIN registration r ON nm.non_memberID = r.non_MemberID
            WHERE DATE(r.RegistrationDate) BETWEEN ? AND ?
        ");
        $nonMemberStmt->execute([$startDate, $endDate]);
        $nonMemberData = $nonMemberStmt->fetch(PDO::FETCH_ASSOC);

        // Member participation
        $memberParticipationStmt = $conn->prepare("
            SELECT 
                COUNT(DISTINCT m.MemberID) as participatingMembers
            FROM member m
            JOIN registration r ON m.MemberID = r.MemberID
            JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            JOIN event e ON r.EventID = e.EventID
            WHERE DATE(ea.AttendanceTime) BETWEEN ? AND ?
        ");
        $memberParticipationStmt->execute([$startDate, $endDate]);
        $memberParticipation = (int)($memberParticipationStmt->fetch(PDO::FETCH_ASSOC)['participatingMembers'] ?? 0);
        $memberAvgParticipation = (int)$memberData['totalCount'] > 0 ? round(($memberParticipation / $memberData['totalCount']) * 100, 1) : 0;

        // Non-member participation  
        $nonMemberParticipationStmt = $conn->prepare("
            SELECT 
                COUNT(DISTINCT nm.non_memberID) as participatingNonMembers
            FROM non_member nm
            JOIN registration r ON nm.non_memberID = r.non_MemberID
            JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            JOIN event e ON r.EventID = e.EventID
            WHERE DATE(ea.AttendanceTime) BETWEEN ? AND ?
        ");
        $nonMemberParticipationStmt->execute([$startDate, $endDate]);
        $nonMemberParticipation = (int)($nonMemberParticipationStmt->fetch(PDO::FETCH_ASSOC)['participatingNonMembers'] ?? 0);
        $nonMemberAvgParticipation = (int)$nonMemberData['totalCount'] > 0 ? round(($nonMemberParticipation / $nonMemberData['totalCount']) * 100, 1) : 0;

        $members = [
            [
                'memberType' => $memberData['type'],
                'totalCount' => (int)$memberData['totalCount'],
                'active' => (int)$memberData['active'],
                'inactive' => (int)$memberData['inactive'],
                'avgParticipation' => (float)$memberAvgParticipation
            ],
            [
                'memberType' => 'Non-Members (registered in period)',
                'totalCount' => (int)$nonMemberData['totalCount'],
                'active' => (int)$nonMemberData['active'],
                'inactive' => (int)$nonMemberData['inactive'],
                'avgParticipation' => (float)$nonMemberAvgParticipation
            ]
        ];

        return [
            'success' => true,
            'data' => $members,
            'count' => 2
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function getTrendsReport($conn, $startDate, $endDate) {
    try {
        $trendStmt = $conn->prepare("
            SELECT 
                DATE_FORMAT(p.ProposedDate, '%Y-%m') as month,
                DATE_FORMAT(p.ProposedDate, '%b %Y') as monthName,
                COUNT(DISTINCT e.EventID) as events,
                COUNT(DISTINCT r.RegistrationID) as registrations,
                COUNT(DISTINCT ea.AttendanceID) as attendees,
                COUNT(DISTINCT CASE WHEN r.MemberID IS NOT NULL THEN ea.AttendanceID END) as memberAttendees,
                COUNT(DISTINCT CASE WHEN r.non_MemberID IS NOT NULL THEN ea.AttendanceID END) as nonMemberAttendees,
                CASE 
                    WHEN COUNT(DISTINCT r.RegistrationID) > 0 
                    THEN ROUND(COUNT(DISTINCT ea.AttendanceID) / COUNT(DISTINCT r.RegistrationID) * 100, 1)
                    ELSE 0
                END as attendanceRate,
                COALESCE(ROUND(AVG(f.Rating), 2), 0) as avgFeedbackRating
            FROM event e
            LEFT JOIN proposal p ON e.ProposalID = p.ProposalID
            LEFT JOIN registration r ON e.EventID = r.EventID
            LEFT JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            LEFT JOIN feedback f ON ea.AttendanceID = f.AttendanceID
            WHERE DATE(p.ProposedDate) BETWEEN ? AND ?
            GROUP BY DATE_FORMAT(p.ProposedDate, '%Y-%m'), p.ProposedDate
            ORDER BY month ASC
        ");
        $trendStmt->execute([$startDate, $endDate]);
        $trends = $trendStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'data' => $trends
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function getInitiativesReport($conn, $startDate, $endDate) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                i.InitiativeID,
                i.Title,
                c.Type as category,
                CASE WHEN i.isHighlighted = 1 THEN 'Featured' ELSE 'Regular' END as status,
                DATE_FORMAT(i.PublishDate, '%b %d, %Y') as publishDate,
                i.Description
            FROM initiatives i
            LEFT JOIN category c ON i.CategoryID = c.CategoryID
            WHERE DATE(i.PublishDate) BETWEEN ? AND ?
            ORDER BY i.PublishDate DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        $initiatives = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'data' => $initiatives,
            'count' => count($initiatives)
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
?>