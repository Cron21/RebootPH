<?php
// Test script to verify API endpoint works
session_start();
$_SESSION['memberID'] = 1; // Simulate logged-in user
require_once 'api/config.php';

// Manually call the function to test
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
        ], JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

getUpcomingAndOngoingEvents();
?>
