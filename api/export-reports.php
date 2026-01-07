<?php
require_once 'config.php';

session_start();

// Check if user is authenticated
if (!isset($_SESSION['memberID'])) {
    http_response_code(401);
    die('Not authenticated');
}

$format = $_GET['format'] ?? 'pdf';
$reportType = $_GET['type'] ?? 'summary';
$startDate = $_GET['startDate'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['endDate'] ?? date('Y-m-d');

try {
    // Generate HTML content
    $htmlContent = generateReportHTML($conn, $reportType, $startDate, $endDate);
    
    if ($format === 'pdf') {
        // Generate PDF filename
        $fileName = 'RebootPH_Report_' . ucfirst($reportType) . '_' . date('Y-m-d_His') . '.pdf';
        
        // Use browser print-to-PDF method
        generatePDFWithBrowserPrint($htmlContent, $fileName);
        exit;
    } else {
        // Return HTML for preview
        header('Content-Type: text/html; charset=UTF-8');
        echo $htmlContent;
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<html><body>';
    echo '<h2>Error Generating Report</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</body></html>';
    exit;
}

function generatePDFWithBrowserPrint($htmlContent, $fileName) {
    // Send HTML with JavaScript auto-print and download
    $htmlWithScript = str_replace(
        '<button onclick="window.print()" style="position: fixed; top: 10px; right: 10px; padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; z-index: 100;">Print / Save PDF</button>',
        '<script>
            document.addEventListener("DOMContentLoaded", function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            });
        </script>',
        $htmlContent
    );
    
    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: inline; filename="' . $fileName . '"');
    echo $htmlWithScript;
}

function generateReportHTML($conn, $reportType, $startDate, $endDate) {
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RebootPH Report - ' . ucfirst($reportType) . '</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; width: 100%; }
        body { 
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px; 
            color: #333;
            line-height: 1.6;
            background: #f5f5f5;
        }
        .container { 
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px;
            background: white;
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
            border-bottom: 3px solid #007bff; 
            padding-bottom: 20px; 
        }
        .header h1 { 
            color: #007bff; 
            font-size: 28px; 
            margin-bottom: 5px;
            font-weight: bold;
        }
        .header p { 
            color: #666; 
            font-size: 11px;
            margin: 3px 0;
        }
        .info { 
            margin-bottom: 25px; 
            padding: 15px;
            background: #f0f8ff;
            border-left: 4px solid #007bff;
            border-radius: 4px;
        }
        .info-row { 
            display: flex; 
            justify-content: space-between;
            font-size: 11px;
            color: #555;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        thead { 
            background-color: #007bff;
            color: white;
        }
        th { 
            padding: 12px; 
            text-align: left; 
            border: 1px solid #ddd; 
            font-weight: bold;
            font-size: 12px;
        }
        td { 
            padding: 10px; 
            border: 1px solid #ddd;
            font-size: 11px;
        }
        tbody tr:nth-child(even) { 
            background-color: #f9f9f9; 
        }
        tbody tr:hover {
            background-color: #f0f8ff;
        }
        .summary-box { 
            background: linear-gradient(135deg, #e7f3ff 0%, #ffffff 100%);
            padding: 20px; 
            border-left: 4px solid #007bff; 
            margin-bottom: 30px;
            border-radius: 4px;
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }
        .summary-item { 
            display: inline-block;
            flex: 1;
            min-width: 150px;
        }
        .summary-value { 
            font-size: 24px; 
            font-weight: bold; 
            color: #007bff;
        }
        .summary-label { 
            font-size: 10px; 
            color: #666;
            margin-top: 3px;
        }
        .footer { 
            margin-top: 40px; 
            padding-top: 15px; 
            border-top: 1px solid #ddd;
            text-align: center; 
            font-size: 9px; 
            color: #999;
        }
        .chart-container {
            position: relative;
            width: 100%;
            height: 400px;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .chart-title {
            font-size: 16px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 15px;
        }
        @media print {
            body {
                background: white;
            }
            .container {
                padding: 0;
                max-width: 100%;
            }
            table {
                page-break-inside: avoid;
            }
            thead {
                display: table-header-group;
            }
        }
        @page {
            size: A4;
            margin: 15mm;
        }
    </style>
</head>
<body>
    <button onclick="window.print()" style="position: fixed; top: 10px; right: 10px; padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; z-index: 100;">Print / Save PDF</button>
    
    <div class="container">
        <div class="header">
            <h1>RebootPH Reports</h1>
            <p>' . ucfirst($reportType) . ' Report</p>
        </div>
        
        <div class="info">
            <div class="info-row">
                <span><strong>Generated:</strong> ' . date('F d, Y \a\t h:i A') . '</span>
                <span><strong>Period:</strong> ' . date('M d, Y', strtotime($startDate)) . ' - ' . date('M d, Y', strtotime($endDate)) . '</span>
            </div>
        </div>';
    
    if ($reportType === 'summary') {
        $html .= generateSummaryHTML($conn, $startDate, $endDate);
    } elseif ($reportType === 'events') {
        $html .= generateEventsHTML($conn, $startDate, $endDate);
    } elseif ($reportType === 'members') {
        $html .= generateMembersHTML($conn, $startDate, $endDate);
    } elseif ($reportType === 'trends') {
        $html .= generateTrendsHTML($conn, $startDate, $endDate);
    } elseif ($reportType === 'initiatives') {
        $html .= generateInitiativesHTML($conn, $startDate, $endDate);
    }
    
    $html .= '
        <div class="footer">
            <p>This is a confidential report. Generated by RebootPH Admin Dashboard.</p>
        </div>
    </div>
</body>
</html>';
    
    return $html;
}

function generateSummaryHTML($conn, $startDate, $endDate) {
    $html = '<div class="summary-box">';
    
    // Total Events
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT e.EventID) as count 
        FROM event e
        JOIN proposal p ON e.ProposalID = p.ProposalID
        JOIN registration r ON e.EventID = r.EventID
        JOIN eventattendance ea ON r.RegistrationID = ea.RegistrationID
        WHERE DATE(p.ProposedDate) BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate]);
    $totalEvents = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    // Total Registrations
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT r.RegistrationID) as count 
        FROM registration r
        JOIN event e ON r.EventID = e.EventID
        JOIN proposal p ON e.ProposalID = p.ProposalID
        WHERE DATE(p.ProposedDate) BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate]);
    $totalRegistrations = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    // Total Attendees
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT ea.AttendanceID) as count 
        FROM eventattendance ea
        JOIN registration r ON ea.RegistrationID = r.RegistrationID
        JOIN event e ON r.EventID = e.EventID
        JOIN proposal p ON e.ProposalID = p.ProposalID
        WHERE DATE(p.ProposedDate) BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate]);
    $totalAttendees = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    // Attendance Rate
    $avgAttendanceRate = $totalRegistrations > 0 ? round(($totalAttendees / $totalRegistrations) * 100, 1) : 0;
    
    // Active Members
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM member WHERE isActive = 1");
    $stmt->execute();
    $totalActiveMembers = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    // Total Non-Members
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT nm.non_memberID) as count 
        FROM non_member nm
        JOIN registration r ON nm.non_memberID = r.non_MemberID
        WHERE DATE(r.RegistrationDate) BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate]);
    $totalNonMembers = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    // Average Feedback Rating
    $stmt = $conn->prepare("
        SELECT COALESCE(ROUND(AVG(f.Rating), 2), 0) as avgRating
        FROM feedback f
        JOIN eventattendance ea ON f.AttendanceID = ea.AttendanceID
        JOIN registration r ON ea.RegistrationID = r.RegistrationID
        JOIN event e ON r.EventID = e.EventID
        JOIN proposal p ON e.ProposalID = p.ProposalID
        WHERE DATE(p.ProposedDate) BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate]);
    $avgFeedbackRating = $stmt->fetch(PDO::FETCH_ASSOC)['avgRating'] ?? 0;
    
    $html .= '
        <div class="summary-item">
            <div class="summary-value">' . $totalEvents . '</div>
            <div class="summary-label">Total Events</div>
        </div>
        <div class="summary-item">
            <div class="summary-value">' . $totalRegistrations . '</div>
            <div class="summary-label">Total Registrations</div>
        </div>
        <div class="summary-item">
            <div class="summary-value">' . $totalAttendees . '</div>
            <div class="summary-label">Total Attendees</div>
        </div>
        <div class="summary-item">
            <div class="summary-value">' . $avgAttendanceRate . '%</div>
            <div class="summary-label">Avg Attendance Rate</div>
        </div>
        <div class="summary-item">
            <div class="summary-value">' . $totalActiveMembers . '</div>
            <div class="summary-label">Active Members</div>
        </div>
        <div class="summary-item">
            <div class="summary-value">' . $totalNonMembers . '</div>
            <div class="summary-label">Non-Members</div>
        </div>
        <div class="summary-item">
            <div class="summary-value">' . number_format($avgFeedbackRating, 2) . '</div>
            <div class="summary-label">Avg Feedback Rating</div>
        </div>
    </div>';
    
    return $html;
}

function generateEventsHTML($conn, $startDate, $endDate) {
    $stmt = $conn->prepare("
        SELECT 
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
            COALESCE(ROUND(AVG(f.Rating), 2), 0) as avgRating
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
    
    $html = '<table>';
    $html .= '<thead><tr><th>Event Name</th><th>Date</th><th>Venue</th><th>Registered</th><th>Attended</th><th>Attendance Rate</th><th>Avg Rating</th></tr></thead>';
    $html .= '<tbody>';
    
    foreach ($events as $event) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($event['eventName']) . '</td>';
        $html .= '<td>' . $event['eventDate'] . '</td>';
        $html .= '<td>' . htmlspecialchars($event['Venue'] ?? 'N/A') . '</td>';
        $html .= '<td>' . $event['registered'] . '</td>';
        $html .= '<td>' . $event['attended'] . '</td>';
        $html .= '<td>' . $event['attendanceRate'] . '%</td>';
        $html .= '<td>' . number_format($event['avgRating'], 2) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
    return $html;
}

function generateMembersHTML($conn, $startDate, $endDate) {
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as totalCount,
            SUM(CASE WHEN isActive = 1 THEN 1 ELSE 0 END) as active
        FROM member
    ");
    $stmt->execute();
    $memberData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $conn->prepare("
        SELECT 
            COUNT(DISTINCT nm.non_memberID) as totalCount
        FROM non_member nm
        JOIN registration r ON nm.non_memberID = r.non_MemberID
        WHERE DATE(r.RegistrationDate) BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate]);
    $nonMemberData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $html = '<table>';
    $html .= '<thead><tr><th>Member Type</th><th>Total Count</th><th>Active</th><th>Inactive</th></tr></thead>';
    $html .= '<tbody>';
    $html .= '<tr>';
    $html .= '<td>All Members</td>';
    $html .= '<td>' . $memberData['totalCount'] . '</td>';
    $html .= '<td>' . $memberData['active'] . '</td>';
    $html .= '<td>' . ($memberData['totalCount'] - $memberData['active']) . '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td>Non-Members (in period)</td>';
    $html .= '<td>' . $nonMemberData['totalCount'] . '</td>';
    $html .= '<td>' . $nonMemberData['totalCount'] . '</td>';
    $html .= '<td>0</td>';
    $html .= '</tr>';
    $html .= '</tbody></table>';
    
    return $html;
}

function generateTrendsHTML($conn, $startDate, $endDate) {
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(p.ProposedDate, '%b %d, %Y') as dateFormatted,
            DATE_FORMAT(p.ProposedDate, '%Y-%m-%d') as dateRaw,
            COUNT(DISTINCT e.EventID) as events,
            COUNT(DISTINCT r.RegistrationID) as registrations,
            COUNT(DISTINCT ea.AttendanceID) as attendees,
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
        GROUP BY DATE_FORMAT(p.ProposedDate, '%Y-%m-%d'), p.ProposedDate
        ORDER BY p.ProposedDate ASC
    ");
    $stmt->execute([$startDate, $endDate]);
    $trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare chart data
    $chartLabels = [];
    $chartEvents = [];
    $chartRegistrations = [];
    $chartAttendees = [];
    
    foreach ($trends as $trend) {
        $chartLabels[] = $trend['dateFormatted'];
        $chartEvents[] = $trend['events'];
        $chartRegistrations[] = $trend['registrations'];
        $chartAttendees[] = $trend['attendees'];
    }
    
    $html = '<div class="chart-container">';
    $html .= '<div class="chart-title">Event Trends Over Time</div>';
    $html .= '<canvas id="trendsChart"></canvas>';
    $html .= '</div>';
    
    // Add chart script
    $html .= '<script>';
    $html .= 'document.addEventListener("DOMContentLoaded", function() {';
    $html .= 'if (document.getElementById("trendsChart")) {';
    $html .= 'const ctx = document.getElementById("trendsChart").getContext("2d");';
    $html .= 'new Chart(ctx, {';
    $html .= 'type: "line",';
    $html .= 'data: {';
    $html .= 'labels: ' . json_encode($chartLabels) . ',';
    $html .= 'datasets: [';
    $html .= '{';
    $html .= 'label: "Events",';
    $html .= 'data: ' . json_encode($chartEvents) . ',';
    $html .= 'borderColor: "#007bff",';
    $html .= 'backgroundColor: "rgba(0, 123, 255, 0.1)",';
    $html .= 'tension: 0.4,';
    $html .= 'fill: true,';
    $html .= 'pointRadius: 5,';
    $html .= 'pointHoverRadius: 7';
    $html .= '},';
    $html .= '{';
    $html .= 'label: "Registrations",';
    $html .= 'data: ' . json_encode($chartRegistrations) . ',';
    $html .= 'borderColor: "#28a745",';
    $html .= 'backgroundColor: "rgba(40, 167, 69, 0.1)",';
    $html .= 'tension: 0.4,';
    $html .= 'fill: true,';
    $html .= 'pointRadius: 5,';
    $html .= 'pointHoverRadius: 7';
    $html .= '},';
    $html .= '{';
    $html .= 'label: "Attendees",';
    $html .= 'data: ' . json_encode($chartAttendees) . ',';
    $html .= 'borderColor: "#ffc107",';
    $html .= 'backgroundColor: "rgba(255, 193, 7, 0.1)",';
    $html .= 'tension: 0.4,';
    $html .= 'fill: true,';
    $html .= 'pointRadius: 5,';
    $html .= 'pointHoverRadius: 7';
    $html .= '}';
    $html .= ']';
    $html .= '},';
    $html .= 'options: {';
    $html .= 'responsive: true,';
    $html .= 'maintainAspectRatio: false,';
    $html .= 'plugins: {';
    $html .= 'legend: { display: true, position: "top" }';
    $html .= '},';
    $html .= 'scales: {';
    $html .= 'y: { beginAtZero: true, ticks: { stepSize: 1 } }';
    $html .= '}';
    $html .= '}';
    $html .= '});';
    $html .= '}';
    $html .= '});';
    $html .= '</script>';
    
    $html .= '<table>';
    $html .= '<thead><tr><th>Date</th><th>Events</th><th>Registrations</th><th>Attendees</th><th>Attendance Rate</th><th>Avg Rating</th></tr></thead>';
    $html .= '<tbody>';
    
    foreach ($trends as $trend) {
        $html .= '<tr>';
        $html .= '<td>' . $trend['dateFormatted'] . '</td>';
        $html .= '<td>' . $trend['events'] . '</td>';
        $html .= '<td>' . $trend['registrations'] . '</td>';
        $html .= '<td>' . $trend['attendees'] . '</td>';
        $html .= '<td>' . $trend['attendanceRate'] . '%</td>';
        $html .= '<td>' . number_format($trend['avgFeedbackRating'], 2) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
    return $html;
}

function generateInitiativesHTML($conn, $startDate, $endDate) {
    $stmt = $conn->prepare("
        SELECT 
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
    
    $html = '<table>';
    $html .= '<thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Date</th></tr></thead>';
    $html .= '<tbody>';
    
    foreach ($initiatives as $initiative) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($initiative['Title']) . '</td>';
        $html .= '<td>' . htmlspecialchars($initiative['category'] ?? 'N/A') . '</td>';
        $html .= '<td>' . $initiative['status'] . '</td>';
        $html .= '<td>' . $initiative['publishDate'] . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
    return $html;
}
?>
