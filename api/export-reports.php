<?php
require_once 'config.php';

session_start();

// Check if user is authenticated
if (!isset($_SESSION['memberID'])) {
    http_response_code(401);
    die('Not authenticated');
}

$format = $_GET['format'] ?? 'csv';
$reportType = $_GET['type'] ?? 'summary';
$startDate = $_GET['startDate'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['endDate'] ?? date('Y-m-d');

try {
    $filename = "report_" . $reportType . "_" . date('Y-m-d-His') . "." . $format;
    
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        exportToCSV($conn, $reportType, $startDate, $endDate);
    }
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => $e->getMessage()]));
}

function exportToCSV($conn, $reportType, $startDate, $endDate) {
    $output = fopen('php://output', 'w');
    
    if ($reportType === 'events') {
        fputcsv($output, ['Event Name', 'Date', 'Staff Required', 'Registered', 'Attended', 'Attendance Rate %', 'Rating']);
        
        $stmt = $conn->prepare("
            SELECT 
                p.Title,
                DATE_FORMAT(p.ProposedDate, '%b %d, %Y') as date,
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
            GROUP BY e.EventID, p.Title, p.ProposedDate, p.StaffRequired
            HAVING COUNT(DISTINCT r.RegistrationID) > 0
            ORDER BY p.ProposedDate DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['Title'],
                $row['date'],
                $row['StaffRequired'],
                $row['registered'],
                $row['attended'],
                $row['attendanceRate'],
                $row['rating']
            ]);
        }
    } else if ($reportType === 'members') {
        fputcsv($output, ['Member Type', 'Total Count', 'Active', 'Inactive', 'Avg Participation %']);
        
        // Regular Members
        $stmt = $conn->prepare("
            SELECT 
                'Regular Members' as type,
                COUNT(DISTINCT m.MemberID) as total,
                COUNT(DISTINCT CASE WHEN m.isActive = 1 THEN m.MemberID END) as active,
                COUNT(DISTINCT CASE WHEN m.isActive = 0 THEN m.MemberID END) as inactive,
                CASE 
                    WHEN COUNT(DISTINCT m.MemberID) > 0 
                    THEN ROUND(COUNT(DISTINCT ea.AttendanceID) / COUNT(DISTINCT m.MemberID) * 100, 1)
                    ELSE 0
                END as participation
            FROM member m
            LEFT JOIN registration r ON m.MemberID = r.MemberID
            LEFT JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            LEFT JOIN event e ON r.EventID = e.EventID
            WHERE m.Role = 'member'
                AND (ea.AttendanceID IS NULL OR DATE(ea.AttendanceTime) BETWEEN ? AND ?)
        ");
        $stmt->execute([$startDate, $endDate]);
        $regularRow = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Staff Members (Admin role)
        $stmt = $conn->prepare("
            SELECT 
                'Staff Members' as type,
                COUNT(DISTINCT m.MemberID) as total,
                COUNT(DISTINCT CASE WHEN m.isActive = 1 THEN m.MemberID END) as active,
                COUNT(DISTINCT CASE WHEN m.isActive = 0 THEN m.MemberID END) as inactive,
                CASE 
                    WHEN COUNT(DISTINCT m.MemberID) > 0 
                    THEN ROUND(COUNT(DISTINCT ea.AttendanceID) / COUNT(DISTINCT m.MemberID) * 100, 1)
                    ELSE 0
                END as participation
            FROM member m
            LEFT JOIN registration r ON m.MemberID = r.MemberID
            LEFT JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            LEFT JOIN event e ON r.EventID = e.EventID
            WHERE m.Role = 'Admin'
                AND (ea.AttendanceID IS NULL OR DATE(ea.AttendanceTime) BETWEEN ? AND ?)
        ");
        $stmt->execute([$startDate, $endDate]);
        $staffRow = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($regularRow) {
            fputcsv($output, [
                $regularRow['type'],
                $regularRow['total'],
                $regularRow['active'],
                $regularRow['inactive'],
                $regularRow['participation']
            ]);
        }
        
        if ($staffRow) {
            fputcsv($output, [
                $staffRow['type'],
                $staffRow['total'],
                $staffRow['active'],
                $staffRow['inactive'],
                $staffRow['participation']
            ]);
        }
    } else if ($reportType === 'summary') {
        fputcsv($output, ['Metric', 'Value', 'Change %']);
        
        // Get summary data using the same logic as get-reports.php
        $summary = getSummaryReport($conn, $startDate, $endDate);
        
        if ($summary['success']) {
            $data = $summary['summary'];
            fputcsv($output, ['Total Events', $data['totalEvents'], $data['eventsChange']]);
            fputcsv($output, ['Total Participants', $data['totalParticipants'], $data['participantsChange']]);
            fputcsv($output, ['Average Attendance Rate', $data['avgAttendance'], '']);
            fputcsv($output, ['New Members', $data['newMembers'], '']);
        }
    } else if ($reportType === 'trends') {
        fputcsv($output, ['Month', 'Events', 'Participants', 'Attendance Rate %']);
        
        $stmt = $conn->prepare("
            SELECT 
                DATE_FORMAT(p.ProposedDate, '%Y-%m') as month,
                COUNT(DISTINCT e.EventID) as events,
                COUNT(DISTINCT r.MemberID) as participants,
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
            GROUP BY DATE_FORMAT(p.ProposedDate, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->execute([$startDate, $endDate]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['month'],
                $row['events'],
                $row['participants'],
                $row['attendance']
            ]);
        }
    }
    
    fclose($output);
}

// Helper function from get-reports.php
function getSummaryReport($conn, $startDate, $endDate) {
    try {
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

        $participantsStmt = $conn->prepare("
            SELECT COUNT(DISTINCT r.RegistrationID) as count 
            FROM registration r
            JOIN event e ON r.EventID = e.EventID
            JOIN proposal p ON e.ProposalID = p.ProposalID
            JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            WHERE DATE(p.ProposedDate) BETWEEN ? AND ?
                AND e.status = 'Completed'
        ");
        $participantsStmt->execute([$startDate, $endDate]);
        $totalParticipants = $participantsStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

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
        ");
        $attendanceStmt->execute([$startDate, $endDate]);
        $attendance = $attendanceStmt->fetch(PDO::FETCH_ASSOC);
        $avgAttendance = ($attendance['total'] > 0) ? round(($attendance['attended'] / $attendance['total']) * 100, 2) : 0;

        $newMembersStmt = $conn->prepare("
            SELECT COUNT(*) as count FROM member 
            WHERE isActive = 1 AND DATE(JoinDate) BETWEEN ? AND ?
        ");
        $newMembersStmt->execute([$startDate, $endDate]);
        $newMembers = $newMembersStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

        return [
            'success' => true,
            'summary' => [
                'totalEvents' => (int)$totalEvents,
                'eventsChange' => 0,
                'totalParticipants' => (int)$totalParticipants,
                'participantsChange' => 0,
                'avgAttendance' => (float)$avgAttendance,
                'newMembers' => (int)$newMembers
            ]
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
?>