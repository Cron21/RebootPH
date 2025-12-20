<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? null;

    if (!$action) {
        throw new Exception('Action is required');
    }

    $adminId = $_SESSION['memberID'] ?? null;
    if (!$adminId) throw new Exception('Not authenticated');

    if ($action === 'create') {
        $proposalId = (int)($data['proposalId'] ?? 0);
        $isPriority = (int)($data['IsPriority'] ?? 0);

        if ($proposalId <= 0) throw new Exception('Valid proposal is required');

        // Check if already exists
        $checkStmt = $conn->prepare("SELECT AnnouncementID FROM announcement WHERE ProposalID = ?");
        $checkStmt->execute([$proposalId]);
        if ($checkStmt->rowCount() > 0) {
            throw new Exception('This proposal is already an announcement');
        }

        $stmt = $conn->prepare("
            INSERT INTO announcement (ProposalID, IsPriority, CreatedByAdminID, LastModifiedBy, LastModifiedDate)
            VALUES (?, ?, NULL, ?, NOW())
        ");
        $stmt->execute([$proposalId, $isPriority, $adminId]);

        echo json_encode([
            'success' => true,
            'message' => 'Announcement created successfully',
            'announcementId' => $conn->lastInsertId()
        ]);

    } elseif ($action === 'update') {
        $announcementId = (int)($data['announcementId'] ?? 0);
        $isPriority = isset($data['IsPriority']) ? (int)$data['IsPriority'] : null;

        if ($announcementId <= 0) throw new Exception('Valid announcement is required');

        if ($isPriority !== null) {
            $stmt = $conn->prepare("
                UPDATE announcement 
                SET IsPriority = ?, LastModifiedBy = ?, LastModifiedDate = NOW()
                WHERE AnnouncementID = ?
            ");
            $stmt->execute([$isPriority, $adminId, $announcementId]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Announcement updated successfully'
        ]);

    } elseif ($action === 'delete') {
        $announcementId = (int)($data['announcementId'] ?? 0);

        if ($announcementId <= 0) throw new Exception('Valid announcement is required');

        $stmt = $conn->prepare("DELETE FROM announcement WHERE AnnouncementID = ?");
        $stmt->execute([$announcementId]);

        echo json_encode([
            'success' => true,
            'message' => 'Announcement deleted successfully'
        ]);

    } else {
        throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>