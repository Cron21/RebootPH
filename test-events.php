<?php
require_once 'api/config.php';

// Get total event count
$result = $conn->query('SELECT COUNT(*) as count FROM event');
$data = $result->fetch(PDO::FETCH_ASSOC);
echo "Total events in database: " . $data['count'] . "\n\n";

// Get event details with status
$result = $conn->query("
    SELECT e.EventID, e.status, p.Title, p.ProposedDate, p.StartTime, p.EndTime
    FROM event e
    LEFT JOIN proposal p ON e.ProposalID = p.ProposalID
    ORDER BY p.ProposedDate DESC
    LIMIT 5
");

echo "Recent Events:\n";
$events = $result->fetchAll(PDO::FETCH_ASSOC);
if (empty($events)) {
    echo "No events found\n";
} else {
    foreach ($events as $event) {
        echo "ID: " . $event['EventID'] . " | Status: " . $event['status'] . " | Title: " . $event['Title'] . " | Date: " . $event['ProposedDate'] . "\n";
    }
}

// Check events with Scheduled, Ongoing, or Near status
$result = $conn->query("
    SELECT COUNT(*) as count FROM event 
    WHERE status IN ('Scheduled', 'Ongoing', 'Near')
");
$data = $result->fetch(PDO::FETCH_ASSOC);
echo "\nUpcoming/Ongoing events (Scheduled, Ongoing, Near): " . $data['count'] . "\n";

// Check recent completed events
$result = $conn->query("
    SELECT COUNT(*) as count FROM event e
    LEFT JOIN proposal p ON e.ProposalID = p.ProposalID
    WHERE e.status = 'Completed' AND DATE(p.ProposedDate) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
");
$data = $result->fetch(PDO::FETCH_ASSOC);
echo "Recent completed events (last 7 days): " . $data['count'] . "\n";
?>
