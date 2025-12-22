<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

session_start();

// Check if user is authenticated
if (!isset($_SESSION['memberID'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? null;

try {
    switch ($action) {
        case 'getEvents':
            getUpcomingAndOngoingEvents();
            break;
        case 'verifyRegistration':
            verifyRegistration();
            break;
        case 'recordAttendance':
            recordAttendance();
            break;
        case 'getEventDetails':
            getEventDetailsWithAttendance();
            break;
        case 'getAttendanceStats':
            getAttendanceStats();
            break;
        case 'markAttendance':
            markAttendanceQR();
            break;
        case 'scanQRCode':
            scanQRCode();
            break;
        case 'getEventAttendance':
            getEventAttendance();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Get upcoming and ongoing events with registration counts
 */
function getUpcomingAndOngoingEvents() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                e.EventID,
                e.SerialNumber,
                e.QRCode,
                e.status,
                p.Title as EventName,
                p.ProposedDate,
                p.StartTime,
                p.EndTime,
                p.Venue,
                p.TargetParticipants as Capacity,
                p.StaffRequired,
                COUNT(DISTINCT r.RegistrationID) as RegisteredCount,
                COUNT(DISTINCT ea.AttendanceID) as CheckedInCount
            FROM event e
            LEFT JOIN proposal p ON e.ProposalID = p.ProposalID
            LEFT JOIN registration r ON e.EventID = r.EventID
            LEFT JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            GROUP BY e.EventID, e.SerialNumber, e.QRCode, e.status, 
                     p.Title, p.ProposedDate, p.StartTime, p.EndTime, 
                     p.Venue, p.TargetParticipants, p.StaffRequired
            ORDER BY CASE 
                WHEN e.status IN ('Scheduled', 'Ongoing', 'Near') THEN 0
                WHEN e.status = 'Completed' AND DATE(p.ProposedDate) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1
                ELSE 2
            END ASC,
            p.ProposedDate DESC, p.StartTime DESC
        ");
        
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format event data
        foreach ($events as &$event) {
            $event['RegisteredCount'] = (int)$event['RegisteredCount'];
            $event['CheckedInCount'] = (int)$event['CheckedInCount'];
            $event['Capacity'] = (int)$event['Capacity'];
            $event['StaffRequired'] = (int)$event['StaffRequired'];
        }
        
        echo json_encode([
            'success' => true,
            'events' => $events,
            'count' => count($events)
        ]);
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Verify event serial number matches and get registered members for that event
 * If a member serial is provided, get that member's details
 */
function verifyRegistration() {
    global $conn;
    
    $eventSerialNumber = $_POST['eventSerialNumber'] ?? $_GET['eventSerialNumber'] ?? null;
    $memberSerialNumber = $_POST['memberSerialNumber'] ?? $_GET['memberSerialNumber'] ?? null;
    $eventId = $_POST['eventId'] ?? $_GET['eventId'] ?? null;
    
    if (!$eventSerialNumber || !$eventId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Event serial number and event ID are required']);
        return;
    }
    
    try {
        // First, verify the event serial number matches
        $eventStmt = $conn->prepare("
            SELECT EventID, SerialNumber 
            FROM event 
            WHERE EventID = ? AND SerialNumber = ?
            LIMIT 1
        ");
        
        $eventStmt->execute([$eventId, $eventSerialNumber]);
        $eventVerification = $eventStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$eventVerification) {
            echo json_encode([
                'success' => true,
                'valid' => false,
                'message' => 'Invalid event serial number'
            ]);
            return;
        }
        
        // If no member serial provided, just confirm event is valid
        if (!$memberSerialNumber) {
            echo json_encode([
                'success' => true,
                'valid' => true,
                'eventVerified' => true,
                'message' => 'Event verified. Now scan or enter member serial number.'
            ]);
            return;
        }
        
        // Extract member ID from member serial number format: RPH-[timestamp]-[random]
        // We need to match this against registered members for this event
        // The member serial is generated but we need to match it to the registration
        
        // Try to find member by searching registrations for this event
        // and matching with member data
        $memberStmt = $conn->prepare("
            SELECT 
                r.RegistrationID,
                r.MemberID,
                r.EventID,
                r.RegistrationDate,
                a.FName,
                a.LName,
                a.ApplicantEmail,
                p.Title as EventName,
                p.ProposedDate,
                p.StartTime,
                p.EndTime,
                CASE 
                    WHEN ea.AttendanceID IS NOT NULL THEN 1
                    ELSE 0 
                END as HasAttended,
                ea.AttendanceTime
            FROM registration r
            JOIN member m ON r.MemberID = m.MemberID
            JOIN application a ON m.ApplicationID = a.ApplicationID
            JOIN event e ON r.EventID = e.EventID
            LEFT JOIN proposal p ON e.ProposalID = p.ProposalID
            LEFT JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            WHERE r.EventID = ? 
                AND CONCAT(a.FName, '-', a.LName, '-', r.RegistrationID) LIKE CONCAT('%', ?, '%')
            LIMIT 1
        ");
        
        $memberStmt->execute([$eventId, $memberSerialNumber]);
        $member = $memberStmt->fetch(PDO::FETCH_ASSOC);
        
        // If not found by name pattern, try direct RegistrationID match
        if (!$member) {
            $altStmt = $conn->prepare("
                SELECT 
                    r.RegistrationID,
                    r.MemberID,
                    r.EventID,
                    r.RegistrationDate,
                    a.FName,
                    a.LName,
                    a.ApplicantEmail,
                    p.Title as EventName,
                    p.ProposedDate,
                    p.StartTime,
                    p.EndTime,
                    CASE 
                        WHEN ea.AttendanceID IS NOT NULL THEN 1
                        ELSE 0 
                    END as HasAttended,
                    ea.AttendanceTime
                FROM registration r
                JOIN member m ON r.MemberID = m.MemberID
                JOIN application a ON m.ApplicationID = a.ApplicationID
                JOIN event e ON r.EventID = e.EventID
                LEFT JOIN proposal p ON e.ProposalID = p.ProposalID
                LEFT JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
                WHERE r.EventID = ? 
                    AND r.RegistrationID = ?
                LIMIT 1
            ");
            
            $altStmt->execute([$eventId, $memberSerialNumber]);
            $member = $altStmt->fetch(PDO::FETCH_ASSOC);
        }
        
        if ($member) {
            echo json_encode([
                'success' => true,
                'valid' => true,
                'message' => 'Member found',
                'data' => [
                    'registrationId' => $member['RegistrationID'],
                    'memberId' => $member['MemberID'],
                    'name' => $member['FName'] . ' ' . $member['LName'],
                    'email' => $member['ApplicantEmail'],
                    'eventName' => $member['EventName'],
                    'eventId' => $member['EventID'],
                    'registrationTime' => $member['RegistrationDate'],
                    'hasAttended' => (int)$member['HasAttended'],
                    'attendanceTime' => $member['AttendanceTime']
                ]
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'valid' => false,
                'message' => 'Member not registered for this event'
            ]);
        }
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Record attendance for a member at an event
 */
function recordAttendance() {
    global $conn;
    
    $registrationId = $_POST['registrationId'] ?? null;
    $eventId = $_POST['eventId'] ?? null;
    $memberId = $_POST['memberId'] ?? null;
    $scanType = $_POST['scanType'] ?? 'QR'; // QR or Manual
    
    if (!$registrationId || !$eventId || !$memberId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Registration ID, Event ID, and Member ID are required']);
        return;
    }
    
    try {
        // Check if already attended
        $checkStmt = $conn->prepare("
            SELECT AttendanceID, AttendanceTime 
            FROM eventattendance 
            WHERE RegistrationID = ?
            LIMIT 1
        ");
        
        $checkStmt->execute([$registrationId]);
        $existingAttendance = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingAttendance) {
            echo json_encode([
                'success' => true,
                'alreadyAttended' => true,
                'message' => 'Member already checked in',
                'attendanceTime' => $existingAttendance['AttendanceTime']
            ]);
            return;
        }
        
        // Record new attendance
        $insertStmt = $conn->prepare("
            INSERT INTO eventattendance (RegistrationID, AttendanceTime, ScanType)
            VALUES (?, NOW(), ?)
        ");
        
        $insertStmt->execute([$registrationId, $scanType]);
        $attendanceId = $conn->lastInsertId();
        
        // Get updated member info for response
        $getStmt = $conn->prepare("
            SELECT 
                r.MemberID,
                a.FName,
                a.LName,
                ea.AttendanceTime,
                e.EventID
            FROM eventattendance ea
            JOIN registration r ON ea.RegistrationID = r.RegistrationID
            JOIN member m ON r.MemberID = m.MemberID
            JOIN application a ON m.ApplicationID = a.ApplicationID
            JOIN event e ON r.EventID = e.EventID
            WHERE ea.AttendanceID = ?
        ");
        
        $getStmt->execute([$attendanceId]);
        $result = $getStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Attendance recorded successfully',
            'attendanceId' => $attendanceId,
            'attendanceTime' => $result['AttendanceTime'],
            'memberName' => $result['FName'] . ' ' . $result['LName']
        ]);
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Get detailed event information with attendance status
 */
function getEventDetailsWithAttendance() {
    global $conn;
    
    $eventId = $_GET['eventId'] ?? null;
    
    if (!$eventId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Event ID is required']);
        return;
    }
    
    try {
        // Get event details
        $eventStmt = $conn->prepare("
            SELECT 
                e.EventID,
                e.SerialNumber,
                e.QRCode,
                e.status,
                p.Title as EventName,
                p.ProposedDate,
                p.StartTime,
                p.EndTime,
                p.Venue,
                p.TargetParticipants as Capacity,
                p.StaffRequired,
                p.Description
            FROM event e
            LEFT JOIN proposal p ON e.ProposalID = p.ProposalID
            WHERE e.EventID = ?
        ");
        
        $eventStmt->execute([$eventId]);
        $eventDetails = $eventStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$eventDetails) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Event not found']);
            return;
        }
        
        // Get attendance list
        $attendanceStmt = $conn->prepare("
            SELECT 
                r.RegistrationID,
                r.MemberID,
                r.RegistrationDate,
                a.FName,
                a.LName,
                a.ApplicantEmail,
                CASE 
                    WHEN ea.AttendanceID IS NOT NULL THEN 'Attended'
                    ELSE 'Registered' 
                END as Status,
                ea.AttendanceTime
            FROM registration r
            JOIN member m ON r.MemberID = m.MemberID
            JOIN application a ON m.ApplicationID = a.ApplicationID
            LEFT JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            WHERE r.EventID = ?
            ORDER BY CASE WHEN ea.AttendanceID IS NOT NULL THEN 0 ELSE 1 END,
                     ea.AttendanceTime DESC,
                     r.RegistrationDate DESC
        ");
        
        $attendanceStmt->execute([$eventId]);
        $attendanceList = $attendanceStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'event' => $eventDetails,
            'attendanceList' => $attendanceList,
            'stats' => [
                'registeredCount' => count($attendanceList),
                'attendedCount' => count(array_filter($attendanceList, fn($a) => $a['Status'] === 'Attended')),
                'attendanceRate' => count($attendanceList) > 0 
                    ? round((count(array_filter($attendanceList, fn($a) => $a['Status'] === 'Attended')) / count($attendanceList)) * 100, 2)
                    : 0
            ]
        ]);
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Get attendance statistics
 */
function getAttendanceStats() {
    global $conn;
    
    try {
        // Overall attendance stats
        $statsStmt = $conn->prepare("
            SELECT 
                COUNT(DISTINCT e.EventID) as TotalEvents,
                COUNT(DISTINCT r.RegistrationID) as TotalRegistrations,
                COUNT(DISTINCT ea.AttendanceID) as TotalAttendances,
                ROUND(
                    (COUNT(DISTINCT ea.AttendanceID) / 
                    CASE WHEN COUNT(DISTINCT r.RegistrationID) > 0 THEN COUNT(DISTINCT r.RegistrationID) ELSE 1 END * 100),
                    2
                ) as OverallAttendanceRate
            FROM event e
            LEFT JOIN registration r ON e.EventID = r.EventID
            LEFT JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            WHERE e.status IN ('Completed', 'Ongoing')
        ");
        
        $statsStmt->execute();
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        // Attendance by event (last 10)
        $eventStatsStmt = $conn->prepare("
            SELECT 
                e.EventID,
                p.Title as EventName,
                p.ProposedDate,
                COUNT(DISTINCT r.RegistrationID) as RegisteredCount,
                COUNT(DISTINCT ea.AttendanceID) as AttendedCount,
                ROUND(
                    (COUNT(DISTINCT ea.AttendanceID) / 
                    CASE WHEN COUNT(DISTINCT r.RegistrationID) > 0 THEN COUNT(DISTINCT r.RegistrationID) ELSE 1 END * 100),
                    2
                ) as AttendanceRate
            FROM event e
            LEFT JOIN proposal p ON e.ProposalID = p.ProposalID
            LEFT JOIN registration r ON e.EventID = r.EventID
            LEFT JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
            WHERE e.status IN ('Completed', 'Ongoing')
            GROUP BY e.EventID, p.Title, p.ProposedDate
            ORDER BY p.ProposedDate DESC
            LIMIT 10
        ");
        
        $eventStatsStmt->execute();
        $eventStats = $eventStatsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'overallStats' => $stats,
            'eventStats' => $eventStats
        ]);
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Mark attendance from member QR display (member confirms attendance)
 */
function markAttendanceQR() {
    global $conn;
    
    $eventId = $_POST['eventId'] ?? null;
    $memberId = $_POST['memberId'] ?? null;
    $checkInMethod = $_POST['checkInMethod'] ?? 'qr_scan'; // qr_scan or direct
    
    if (!$eventId || !$memberId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Event ID and Member ID are required']);
        return;
    }
    
    try {
        // Verify member is registered for this event
        $regStmt = $conn->prepare("
            SELECT r.RegistrationID
            FROM registration r
            WHERE r.EventID = ? AND r.MemberID = ?
            LIMIT 1
        ");
        
        $regStmt->execute([$eventId, $memberId]);
        $registration = $regStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$registration) {
            echo json_encode(['success' => false, 'message' => 'Member is not registered for this event']);
            return;
        }
        
        // Check if already marked attendance
        $checkStmt = $conn->prepare("
            SELECT AttendanceID 
            FROM eventattendance 
            WHERE RegistrationID = ?
            LIMIT 1
        ");
        
        $checkStmt->execute([$registration['RegistrationID']]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            echo json_encode([
                'success' => true,
                'alreadyMarked' => true,
                'message' => 'Attendance already marked for this event'
            ]);
            return;
        }
        
        // Record attendance from member QR scan
        $insertStmt = $conn->prepare("
            INSERT INTO eventattendance (RegistrationID, AttendanceTime, ScanType)
            VALUES (?, NOW(), ?)
        ");
        
        $insertStmt->execute([$registration['RegistrationID'], $checkInMethod]);
        $attendanceId = $conn->lastInsertId();
        
        // Get member details
        $memberStmt = $conn->prepare("
            SELECT 
                m.MemberID,
                a.FName,
                a.LName,
                p.Title as EventName
            FROM member m
            JOIN application a ON m.ApplicationID = a.ApplicationID
            JOIN event e ON e.EventID = ?
            LEFT JOIN proposal p ON e.ProposalID = p.ProposalID
            WHERE m.MemberID = ?
        ");
        
        $memberStmt->execute([$eventId, $memberId]);
        $member = $memberStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Attendance marked successfully',
            'attendanceId' => $attendanceId,
            'memberName' => $member['FName'] . ' ' . $member['LName'],
            'eventName' => $member['EventName']
        ]);
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Scan QR code from member and mark attendance (admin scanning member's phone)
 */
function scanQRCode() {
    global $conn;
    
    $qrData = $_POST['qrData'] ?? null;
    
    if (!$qrData) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'QR data is required']);
        return;
    }
    
    try {
        // Decode QR data (should be JSON)
        $decodedData = json_decode($qrData, true);
        
        if (!$decodedData || !isset($decodedData['eventId']) || !isset($decodedData['memberId'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid QR code data format']);
            return;
        }
        
        $eventId = $decodedData['eventId'];
        $memberId = $decodedData['memberId'];
        
        // Verify member is registered for this event
        $regStmt = $conn->prepare("
            SELECT r.RegistrationID
            FROM registration r
            WHERE r.EventID = ? AND r.MemberID = ?
            LIMIT 1
        ");
        
        $regStmt->execute([$eventId, $memberId]);
        $registration = $regStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$registration) {
            echo json_encode(['success' => false, 'message' => 'Member is not registered for this event']);
            return;
        }
        
        // Check if already marked attendance
        $checkStmt = $conn->prepare("
            SELECT AttendanceID 
            FROM eventattendance 
            WHERE RegistrationID = ?
            LIMIT 1
        ");
        
        $checkStmt->execute([$registration['RegistrationID']]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            echo json_encode([
                'success' => true,
                'alreadyMarked' => true,
                'message' => 'Attendance already marked'
            ]);
            return;
        }
        
        // Record attendance from QR scan
        $insertStmt = $conn->prepare("
            INSERT INTO eventattendance (RegistrationID, AttendanceTime, ScanType)
            VALUES (?, NOW(), 'QR_Scan')
        ");
        
        $insertStmt->execute([$registration['RegistrationID']]);
        $attendanceId = $conn->lastInsertId();
        
        // Get member details
        $memberStmt = $conn->prepare("
            SELECT 
                m.MemberID,
                a.FName,
                a.LName,
                p.Title as EventName
            FROM member m
            JOIN application a ON m.ApplicationID = a.ApplicationID
            JOIN event e ON e.EventID = ?
            LEFT JOIN proposal p ON e.ProposalID = p.ProposalID
            WHERE m.MemberID = ?
        ");
        
        $memberStmt->execute([$eventId, $memberId]);
        $member = $memberStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Attendance marked successfully',
            'attendanceId' => $attendanceId,
            'memberName' => $member['FName'] . ' ' . $member['LName'],
            'eventName' => $member['EventName']
        ]);
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Get event attendance details with member list
 */
function getEventAttendance() {
    global $conn;
    
    // Accept eventId from GET or POST
    $eventId = $_GET['eventId'] ?? $_POST['eventId'] ?? null;
    $memberId = $_GET['memberId'] ?? $_POST['memberId'] ?? null;
    
    if (!$eventId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Event ID is required']);
        return;
    }
    
    try {
        // If memberId is provided, get only that member's registration (for member self check-in)
        if ($memberId) {
            $stmt = $conn->prepare("
                SELECT 
                    r.RegistrationID,
                    r.MemberID,
                    r.RegistrationDate,
                    m.MemberID,
                    a.FName,
                    a.LName,
                    a.ApplicantEmail,
                    ea.AttendanceID,
                    ea.AttendanceTime,
                    ea.ScanType
                FROM registration r
                JOIN member m ON r.MemberID = m.MemberID
                JOIN application a ON m.ApplicationID = a.ApplicationID
                LEFT JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
                WHERE r.EventID = ? AND r.MemberID = ?
                LIMIT 1
            ");
            
            $stmt->execute([$eventId, $memberId]);
        } else {
            // Get all registered members for this event with attendance status (admin view)
            $stmt = $conn->prepare("
                SELECT 
                    r.RegistrationID,
                    r.MemberID,
                    r.RegistrationDate,
                    a.FName,
                    a.LName,
                    a.ApplicantEmail,
                    ea.AttendanceID,
                    ea.AttendanceTime,
                    ea.ScanType
                FROM registration r
                JOIN member m ON r.MemberID = m.MemberID
                JOIN application a ON m.ApplicationID = a.ApplicationID
                LEFT JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
                WHERE r.EventID = ?
                ORDER BY a.LName ASC, a.FName ASC
            ");
            
            $stmt->execute([$eventId]);
        }
        
        $attendees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If memberId provided and no results, return not found
        if ($memberId && empty($attendees)) {
            echo json_encode([
                'success' => false,
                'message' => 'Member is not registered for this event',
                'data' => []
            ]);
            return;
        }
        
        if ($memberId) {
            // Single member lookup - return as array with one item
            echo json_encode([
                'success' => true,
                'data' => $attendees
            ]);
        } else {
            // Admin view - return stats
            $totalAttended = count(array_filter($attendees, fn($a) => $a['AttendanceID']));
            
            echo json_encode([
                'success' => true,
                'attendees' => $attendees,
                'stats' => [
                    'totalRegistered' => count($attendees),
                    'totalAttended' => $totalAttended,
                    'attendanceRate' => count($attendees) > 0 
                        ? round(($totalAttended / count($attendees)) * 100, 2)
                        : 0
                ]
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Error fetching event attendance: ' . $e->getMessage()
        ]);
    }
}
?>