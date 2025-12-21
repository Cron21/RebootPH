<?php
// Simple test to check if events exist in database
require_once 'api/config.php';

// Check for approved proposals
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM proposal WHERE Status = 'Approved'");
$stmt->execute();
$proposalCount = $stmt->fetch(PDO::FETCH_ASSOC);

// Check for events
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM event");
$stmt->execute();
$eventCount = $stmt->fetch(PDO::FETCH_ASSOC);

// Check for upcoming approved events
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM proposal WHERE Status = 'Approved' AND ProposedDate >= CURDATE()");
$stmt->execute();
$upcomingCount = $stmt->fetch(PDO::FETCH_ASSOC);

// Get sample upcoming event
$stmt = $conn->prepare("
    SELECT e.*, p.Title, p.ProposedDate, p.StartTime, p.Status
    FROM event e
    JOIN proposal p ON e.ProposalID = p.ProposalID
    WHERE p.Status = 'Approved' AND p.ProposedDate >= CURDATE()
    LIMIT 5
");
$stmt->execute();
$sampleEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'approved_proposals' => $proposalCount,
    'total_events' => $eventCount,
    'upcoming_approved_events' => $upcomingCount,
    'sample_events' => $sampleEvents
], JSON_PRETTY_PRINT);
?>
