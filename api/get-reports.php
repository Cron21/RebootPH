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
        // Total Events held in period
        $totalEventsStmt = $conn->prepare("
            SELECT COUNT(DISTINCT e.EventID) as count 
            FROM event e
            JOIN proposal p ON e.ProposalID = p.ProposalID
            WHERE DATE(p.ProposedDate) BETWEEN ? AND ? 
                AND e.status = 'Completed'
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
                AND e.status = 'Completed'
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
                AND e.status = 'Completed'
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
                AND e.status = 'Completed'
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
            WHERE DATE(p.ProposedDate) BETWEEN ? AND ? 
                AND e.status = 'Completed'
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
                AND e.status = 'Completed'
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
                AND e.status = 'Completed'
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
        // Regular Members
        $memberStmt = $conn->prepare("
            SELECT 
                'Regular Members' as type,
                COUNT(*) as totalCount,
                SUM(CASE WHEN isActive = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN isActive = 0 THEN 1 ELSE 0 END) as inactive
            FROM member 
            WHERE Role = 'Member'
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
                AND e.status = 'Completed'
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
                AND e.status = 'Completed'
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
                'memberType' => $nonMemberData['type'],
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
                AND e.status = 'Completed'
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

function getSummaryReport($conn, $startDate, $endDate) {
    try {
        // Total Events (count of COMPLETED events with attendance and registration data)
        $eventsStmt = $conn->prepare("
            SELECT COUNT(DISTINCT e.EventID) as count 
            FROM event e
            LEFT JOIN proposal p ON e.ProposalID = p.ProposalID
            LEFT JOIN registration r ON e.EventID = r.EventID
            LEFT JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            WHERE DATE(p.ProposedDate) BETWEEN ? AND ? 
                AND e.status = 'Completed'
                AND r.RegistrationID IS NOT NULL
                AND ea.AttendanceID IS NOT NULL
        ");
        $eventsStmt->execute([$startDate, $endDate]);
        $totalEvents = $eventsStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

        // Total Participants (total registrations count for completed events with attendance - includes both members and non-members)
        $participantsStmt = $conn->prepare("
            SELECT COUNT(DISTINCT r.RegistrationID) as count 
            FROM registration r
            JOIN event e ON r.EventID = e.EventID
            JOIN proposal p ON e.ProposalID = p.ProposalID
            JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            WHERE DATE(p.ProposedDate) BETWEEN ? AND ?
                AND e.status = 'Completed'
                AND (r.MemberID IS NOT NULL OR r.non_MemberID IS NOT NULL)
        ");
        $participantsStmt->execute([$startDate, $endDate]);
        $totalParticipants = $participantsStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

        // Average Attendance Rate (count of attended registrations / total registrations * 100 - includes both members and non-members)
        $attendanceStmt = $conn->prepare("
            SELECT 
                COUNT(DISTINCT ea.AttendanceID) as attended,
                COUNT(DISTINCT r.RegistrationID) as total
            FROM registration r
            LEFT JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            JOIN event e ON r.EventID = e.EventID
            JOIN proposal p ON e.ProposalID = p.ProposalID
            WHERE DATE(p.ProposedDate) BETWEEN ? AND ?
                AND e.status = 'Completed'
                AND (r.MemberID IS NOT NULL OR r.non_MemberID IS NOT NULL)
        ");
        $attendanceStmt->execute([$startDate, $endDate]);
        $attendance = $attendanceStmt->fetch(PDO::FETCH_ASSOC);
        $avgAttendance = ($attendance['total'] > 0) ? round(($attendance['attended'] / $attendance['total']) * 100, 2) : 0;

        // New Members (members added in the selected period)
        $newMembersStmt = $conn->prepare("
            SELECT COUNT(*) as count FROM member 
            WHERE isActive = 1 AND DATE(JoinDate) BETWEEN ? AND ?
        ");
        $newMembersStmt->execute([$startDate, $endDate]);
        $newMembers = $newMembersStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

        // Previous period comparison (same duration)
        $daysDiff = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24);
        $prevStart = date('Y-m-d', strtotime($startDate) - ($daysDiff * 86400));
        $prevEnd = date('Y-m-d', strtotime($startDate) - 86400);

        // Previous events (Completed with attendance and registration)
        $prevEventsStmt = $conn->prepare("
            SELECT COUNT(DISTINCT e.EventID) as count 
            FROM event e
            LEFT JOIN proposal p ON e.ProposalID = p.ProposalID
            LEFT JOIN registration r ON e.EventID = r.EventID
            LEFT JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            WHERE DATE(p.ProposedDate) BETWEEN ? AND ?
                AND e.status = 'Completed'
                AND r.RegistrationID IS NOT NULL
                AND ea.AttendanceID IS NOT NULL
        ");
        $prevEventsStmt->execute([$prevStart, $prevEnd]);
        $prevEvents = $prevEventsStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

        // Previous participants
        $prevParticipantsStmt = $conn->prepare("
            SELECT COUNT(DISTINCT r.RegistrationID) as count 
            FROM registration r
            JOIN event e ON r.EventID = e.EventID
            JOIN proposal p ON e.ProposalID = p.ProposalID
            JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            WHERE DATE(p.ProposedDate) BETWEEN ? AND ?
                AND e.status = 'Completed'
        ");
        $prevParticipantsStmt->execute([$prevStart, $prevEnd]);
        $prevParticipants = $prevParticipantsStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

        // Total Initiatives
        $initiativesStmt = $conn->prepare("
            SELECT COUNT(*) as count FROM initiatives 
            WHERE DATE(PublishDate) BETWEEN ? AND ?
        ");
        $initiativesStmt->execute([$startDate, $endDate]);
        $totalInitiatives = $initiativesStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

        // Calculate percentage changes
        $eventsChange = $prevEvents > 0 ? round((($totalEvents - $prevEvents) / $prevEvents) * 100, 1) : ($totalEvents > 0 ? 100 : 0);
        $participantsChange = $prevParticipants > 0 ? round((($totalParticipants - $prevParticipants) / $prevParticipants) * 100, 1) : ($totalParticipants > 0 ? 100 : 0);

        return [
            'success' => true,
            'summary' => [
                'totalEvents' => (int)$totalEvents,
                'eventsChange' => (float)$eventsChange,
                'totalParticipants' => (int)$totalParticipants,
                'participantsChange' => (float)$participantsChange,
                'avgAttendance' => (float)$avgAttendance,
                'newMembers' => (int)$newMembers,
                'totalInitiatives' => (int)$totalInitiatives
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
                p.StaffRequired,
                COUNT(DISTINCT r.RegistrationID) as registered,
                COUNT(DISTINCT ea.AttendanceID) as attended,
                CASE 
                    WHEN COUNT(DISTINCT r.RegistrationID) > 0 
                    THEN ROUND(COUNT(DISTINCT ea.AttendanceID) / COUNT(DISTINCT r.RegistrationID) * 100, 1)
                    ELSE 0
                END as attendanceRate,
                COALESCE(ROUND(AVG(f.Rating), 1), 0) as rating
            FROM event e
            LEFT JOIN proposal p ON e.ProposalID = p.ProposalID
            LEFT JOIN registration r ON e.EventID = r.EventID
            LEFT JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            LEFT JOIN feedback f ON ea.AttendanceID = f.AttendanceID
            WHERE DATE(p.ProposedDate) BETWEEN ? AND ?
                AND e.status = 'Completed'
                AND (r.MemberID IS NOT NULL OR r.non_MemberID IS NOT NULL)
            GROUP BY e.EventID, p.Title, p.ProposedDate, p.StaffRequired
            HAVING COUNT(DISTINCT r.RegistrationID) > 0 AND COUNT(DISTINCT ea.AttendanceID) > 0
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
        // Regular Members - get total count
        $regularCountStmt = $conn->prepare("
            SELECT COUNT(*) as count FROM member WHERE Role = 'Member'
        ");
        $regularCountStmt->execute();
        $regularTotal = $regularCountStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

        // Regular members active/inactive
        $regularStatsStmt = $conn->prepare("
            SELECT 
                COUNT(CASE WHEN isActive = 1 THEN 1 END) as active,
                COUNT(CASE WHEN isActive = 0 THEN 1 END) as inactive
            FROM member
            WHERE Role = 'Member'
        ");
        $regularStatsStmt->execute();
        $regularStats = $regularStatsStmt->fetch(PDO::FETCH_ASSOC);

        // Regular Members participation - count members who attended in the period (completed events only)
        $regularParticipationStmt = $conn->prepare("
            SELECT COUNT(DISTINCT m.MemberID) as participatingMembers
            FROM member m
            JOIN registration r ON m.MemberID = r.MemberID
            JOIN event e ON r.EventID = e.EventID
            JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            WHERE m.Role = 'Member'
                AND e.status = 'Completed'
                AND DATE(ea.AttendanceTime) BETWEEN ? AND ?
        ");
        $regularParticipationStmt->execute([$startDate, $endDate]);
        $regularParticipants = $regularParticipationStmt->fetch(PDO::FETCH_ASSOC)['participatingMembers'] ?? 0;

        $regularAvgParticipation = $regularTotal > 0 ? round(($regularParticipants / $regularTotal) * 100, 1) : 0;

        // Staff Members (Admin role) - get total count
        $staffCountStmt = $conn->prepare("
            SELECT COUNT(*) as count FROM member WHERE Role = 'Admin'
        ");
        $staffCountStmt->execute();
        $staffTotal = $staffCountStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

        // Staff members (Admin role) active/inactive
        $staffStatsStmt = $conn->prepare("
            SELECT 
                COUNT(CASE WHEN isActive = 1 THEN 1 END) as active,
                COUNT(CASE WHEN isActive = 0 THEN 1 END) as inactive
            FROM member
            WHERE Role = 'Admin'
        ");
        $staffStatsStmt->execute();
        $staffStats = $staffStatsStmt->fetch(PDO::FETCH_ASSOC);

        // Staff Members (Admin role) participation - count members who attended in the period (completed events only)
        $staffParticipationStmt = $conn->prepare("
            SELECT COUNT(DISTINCT m.MemberID) as participatingMembers
            FROM member m
            JOIN registration r ON m.MemberID = r.MemberID
            JOIN event e ON r.EventID = e.EventID
            JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            WHERE m.Role = 'Admin'
                AND e.status = 'Completed'
                AND DATE(ea.AttendanceTime) BETWEEN ? AND ?
        ");
        $staffParticipationStmt->execute([$startDate, $endDate]);
        $staffParticipants = $staffParticipationStmt->fetch(PDO::FETCH_ASSOC)['participatingMembers'] ?? 0;

        $staffAvgParticipation = $staffTotal > 0 ? round(($staffParticipants / $staffTotal) * 100, 1) : 0;

        $members = [
            [
                'memberType' => 'Regular Members',
                'totalCount' => (int)$regularTotal,
                'active' => (int)$regularStats['active'],
                'inactive' => (int)$regularStats['inactive'],
                'avgParticipation' => (float)$regularAvgParticipation
            ],
            [
                'memberType' => 'Staff Members',
                'totalCount' => (int)$staffTotal,
                'active' => (int)$staffStats['active'],
                'inactive' => (int)$staffStats['inactive'],
                'avgParticipation' => (float)$staffAvgParticipation
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
        // Monthly event participation trend (completed events only - includes both members and non-members)
        $trendStmt = $conn->prepare("
            SELECT 
                DATE_FORMAT(p.ProposedDate, '%Y-%m') as month,
                COUNT(DISTINCT e.EventID) as events,
                COUNT(DISTINCT r.RegistrationID) as participants,
                CASE 
                    WHEN COUNT(DISTINCT r.RegistrationID) > 0 
                    THEN ROUND(COUNT(DISTINCT ea.AttendanceID) / COUNT(DISTINCT r.RegistrationID) * 100, 1)
                    ELSE 0
                END as attendance
            FROM event e
            LEFT JOIN proposal p ON e.ProposalID = p.ProposalID
            LEFT JOIN registration r ON e.EventID = r.EventID
            LEFT JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            WHERE DATE(p.ProposedDate) BETWEEN ? AND ?
                AND e.status = 'Completed'
                AND (r.MemberID IS NOT NULL OR r.non_MemberID IS NOT NULL)
            GROUP BY DATE_FORMAT(p.ProposedDate, '%Y-%m')
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
                COALESCE(c.Type, 'Uncategorized') as category,
                CASE WHEN i.isHighlighted = 1 THEN 'Featured' ELSE 'Regular' END as status,
                DATE_FORMAT(i.PublishDate, '%b %d, %Y') as publishDate,
                i.Description
            FROM initiatives i
            LEFT JOIN category c ON i.CategoryID = c.CategoryID
            ORDER BY i.PublishDate DESC
        ");
        $stmt->execute();
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