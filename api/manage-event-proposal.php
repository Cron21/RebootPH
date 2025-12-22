<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

    $memberId = $_SESSION['memberID'] ?? null;
    $userRole = $_SESSION['role'] ?? null;

    if (!$memberId) {
        throw new Exception('Not authenticated - no member ID in session');
    }

    // For delete/approve/reject, ensure user has proper role
    if (in_array($action, ['delete', 'approve', 'reject'])) {
        $rolesCanManage = ['Admin', 'Executive Director', 'Program Officer', 'Regional Convenor', 'Local Coordinator'];
        if (!in_array($userRole, $rolesCanManage)) {
            throw new Exception('Unauthorized: Insufficient permissions for this action');
        }
    }

    if ($action === 'create') {
        // --- CREATE PROPOSAL LOGIC ---
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $proposedDate = $data['proposedDate'] ?? '';
        $startTime = $data['startTime'] ?? '';
        $endTime = $data['endTime'] ?? '';
        $venue = trim($data['venue'] ?? '');
        $targetParticipants = (int)($data['targetParticipants'] ?? 0);
        $eventType = trim($data['eventType'] ?? '');
        $budgetEstimate = (float)($data['budgetEstimate'] ?? 0);
        $staffRequired = (int)($data['staffRequired'] ?? 0);
        $equipmentNeeded = trim($data['equipmentNeeded'] ?? '');
        $objectives = trim($data['objectives'] ?? '');
        $partnersSponsor = trim($data['partnersSponsor'] ?? '');

        if (empty($title)) throw new Exception('Title is required');
        if (empty($description)) throw new Exception('Description is required');
        if (empty($proposedDate)) throw new Exception('Proposed date is required');

        $stmt = $conn->prepare("
            INSERT INTO proposal 
            (Title, Description, ProposedDate, StartTime, EndTime, Venue, TargetParticipants, EventType, 
             BudgetEstimate, StaffRequired, EquipmentNeeded, Objectives, PartnersSponsor, 
             SubmittedByMemberID, Status, SubmissionDate)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())
        ");

        $stmt->execute([
            $title, $description, $proposedDate, $startTime, $endTime, $venue,
            $targetParticipants, $eventType, $budgetEstimate, $staffRequired,
            $equipmentNeeded, $objectives, $partnersSponsor, $memberId
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Event proposal submitted successfully',
            'proposalID' => $conn->lastInsertId()
        ]);

    } elseif ($action === 'approve' || $action === 'reject') {
        // --- APPROVE / REJECT LOGIC WITH SELF-APPROVAL PROTECTION ---
        
        $proposalId = (int)($data['proposalId'] ?? 0);
        if ($proposalId <= 0) throw new Exception('Invalid proposal ID');

        // Only these roles can approve/reject event proposals
        $rolesCanApproveProposals = ['Executive Director', 'Program Officer', 'Regional Convenor', 'Local Coordinator'];
        
        if (!in_array($userRole, $rolesCanApproveProposals)) {
            throw new Exception('Unauthorized: Only Executive Director, Program Officer, Regional Convenor, and Local Coordinator can review proposals');
        }

        $checkPropStmt = $conn->prepare("SELECT SubmittedByMemberID, Status FROM proposal WHERE ProposalID = ?");
        $checkPropStmt->execute([$proposalId]);
        $proposal = $checkPropStmt->fetch();

        if (!$proposal) {
            throw new Exception('Proposal not found');
        }

        if ($proposal['Status'] !== 'Pending') {
            throw new Exception('This proposal has already been reviewed');
        }

        if ($action === 'approve') {
            $newStatus = 'Approved';
            $message = 'Proposal approved successfully and announcement created';
        } else {
            $newStatus = 'Rejected';
            $message = 'Proposal rejected successfully';
        }

        // 3. Update Status
        $stmt = $conn->prepare("
            UPDATE proposal 
            SET Status = ?, ReviewByAdminID = ?, ReviewDate = NOW()
            WHERE ProposalID = ?
        ");
        $stmt->execute([$newStatus, $memberId, $proposalId]);

        // 4. Create announcement kung approved
        if ($action === 'approve') {
            $annCheck = $conn->prepare("SELECT AnnouncementID FROM announcement WHERE ProposalID = ?");
            $annCheck->execute([$proposalId]);
            
            if ($annCheck->rowCount() === 0) {
                $annStmt = $conn->prepare("
                    INSERT INTO announcement (ProposalID, IsPriority, CreatedByAdminID, LastModifiedBy, LastModifiedDate)
                    VALUES (?, 0, ?, ?, NOW())
                ");
                $annStmt->execute([$proposalId, $memberId, $memberId]);
            }

            // 5. Automatically create event from approved proposal
            $eventCheck = $conn->prepare("SELECT EventID FROM event WHERE ProposalID = ?");
            $eventCheck->execute([$proposalId]);
            
            if ($eventCheck->rowCount() === 0) {
                // Get proposal details
                $propStmt = $conn->prepare("
                    SELECT Title, ProposedDate, StartTime, EndTime, Venue, 
                           StaffRequired, TargetParticipants
                    FROM proposal
                    WHERE ProposalID = ?
                ");
                $propStmt->execute([$proposalId]);
                $proposal = $propStmt->fetch(PDO::FETCH_ASSOC);

                if ($proposal) {
                    // Calculate registration deadline (12 hours before event start)
                    try {
                        $eventDateTime = $proposal['ProposedDate'] . ' ' . $proposal['StartTime'];
                        $deadline = new DateTime($eventDateTime);
                        $deadline->modify('-12 hours');
                        $registrationDeadline = $deadline->format('Y-m-d');
                    } catch (Exception $e) {
                        $registrationDeadline = date('Y-m-d', strtotime('-12 hours'));
                    }

                    // Generate serial number - simple unique number
                    $serialNumber = mt_rand(100000, 999999);
                    $qrData = "EVENT-{$proposalId}-{$serialNumber}";

                    // Create event
                    $eventStmt = $conn->prepare("
                        INSERT INTO event 
                        (ProposalID, CreatedByAdminID, RegistrationDeadline, SerialNumber, QRCode, LastModifiedBy, LastModifiedDate)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");

                    $eventStmt->execute([
                        $proposalId,
                        $memberId,
                        $registrationDeadline,
                        $serialNumber,
                        $qrData,
                        $memberId
                    ]);
                }
            }
        }

        echo json_encode(['success' => true, 'message' => $message]);

    } elseif ($action === 'update') {
        // --- UPDATE LOGIC ---
        $proposalId = (int)($data['proposalId'] ?? 0);
        if ($proposalId <= 0) throw new Exception('Invalid proposal ID');

        $isAdmin = ($userRole === 'Admin');

        //  Update (Admin can edit any pending, Member only their own)
        $query = "UPDATE proposal SET Title = ?, Description = ?, ProposedDate = ?, StartTime = ?, EndTime = ?, Venue = ?, TargetParticipants = ?, EventType = ?, BudgetEstimate = ?, StaffRequired = ?, EquipmentNeeded = ?, Objectives = ?, PartnersSponsor = ? WHERE ProposalID = ? AND Status = 'Pending'";
        
        $params = [
            $data['title'], $data['description'], $data['proposedDate'], $data['startTime'], 
            $data['endTime'], $data['venue'], $data['targetParticipants'], $data['eventType'], 
            $data['budgetEstimate'], $data['staffRequired'], $data['equipmentNeeded'], 
            $data['objectives'], $data['partnersSponsor'], $proposalId
        ];

        if (!$isAdmin) {
            $query .= " AND SubmittedByMemberID = ?";
            $params[] = $memberId;
        }

        $stmt = $conn->prepare($query);
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Proposal not found or no changes made');
        }

        echo json_encode(['success' => true, 'message' => 'Proposal updated successfully']);

    } elseif ($action === 'updateDate') {
        // --- UPDATE PROPOSAL DATE (only for pending proposals with passed dates) ---
        $proposalId = (int)($data['proposalId'] ?? 0);
        $newDate = $data['newDate'] ?? '';

        if ($proposalId <= 0) throw new Exception('Invalid proposal ID');
        if (empty($newDate)) throw new Exception('New date is required');

        // Only admins can update dates
        $rolesCanUpdateDates = ['Admin', 'Executive Director', 'Program Officer', 'Regional Convenor', 'Local Coordinator'];
        if (!in_array($userRole, $rolesCanUpdateDates)) {
            throw new Exception('Unauthorized: Cannot update proposal date');
        }

        // Get current proposal
        $propStmt = $conn->prepare("SELECT Status, ProposedDate FROM proposal WHERE ProposalID = ?");
        $propStmt->execute([$proposalId]);
        $proposal = $propStmt->fetch();

        if (!$proposal) {
            throw new Exception('Proposal not found');
        }

        if ($proposal['Status'] !== 'Pending Review') {
            throw new Exception('Can only update date for pending proposals');
        }

        // Validate new date is not in the past
        $selectedDate = new DateTime($newDate);
        $today = new DateTime();
        $today->setTime(0, 0, 0);

        if ($selectedDate < $today) {
            throw new Exception('New date cannot be in the past');
        }

        // Update the proposal date
        $updateStmt = $conn->prepare("
            UPDATE proposal 
            SET ProposedDate = ?
            WHERE ProposalID = ?
        ");
        $updateStmt->execute([$newDate, $proposalId]);

        echo json_encode(['success' => true, 'message' => 'Proposal date updated successfully']);
        exit;

    } elseif ($action === 'delete') {
        // --- DELETE PROPOSAL AND RELATED DATA ---
        $proposalId = (int)($data['proposalId'] ?? 0);

        if ($proposalId <= 0) throw new Exception('Invalid proposal ID');

        // Get proposal details and status
        $propStmt = $conn->prepare("SELECT Status FROM proposal WHERE ProposalID = ?");
        $propStmt->execute([$proposalId]);
        $proposal = $propStmt->fetch();

        if (!$proposal) {
            throw new Exception('Proposal not found');
        }

        // Check if proposal status is Rejected or Postponed
        if ($proposal['Status'] !== 'Rejected' && $proposal['Status'] !== 'Postponed') {
            throw new Exception('Proposals can only be deleted if they are Rejected or Postponed');
        }

        // Find if there's an event associated with this proposal
        $eventStmt = $conn->prepare("SELECT EventID FROM event WHERE ProposalID = ?");
        $eventStmt->execute([$proposalId]);
        $eventResult = $eventStmt->fetch();
        
        // If event exists, delete cascade (registrations, attendance, feedback, event)
        if ($eventResult && isset($eventResult['EventID'])) {
            $eventId = $eventResult['EventID'];
            
            // Delete registrations and attendance
            $delRegStmt = $conn->prepare("
                DELETE FROM eventattendance 
                WHERE RegistrationID IN (
                    SELECT RegistrationID FROM registration WHERE EventID = ?
                )
            ");
            $delRegStmt->execute([$eventId]);

            $delAttStmt = $conn->prepare("DELETE FROM registration WHERE EventID = ?");
            $delAttStmt->execute([$eventId]);

            // Delete feedback (linked through eventattendance and registration)
            $delFeedStmt = $conn->prepare("
                DELETE FROM feedback 
                WHERE AttendanceID IN (
                    SELECT ea.AttendanceID FROM eventattendance ea
                    JOIN registration r ON ea.RegistrationID = r.RegistrationID
                    WHERE r.EventID = ?
                )
            ");
            $delFeedStmt->execute([$eventId]);

            // Delete event
            $delEventStmt = $conn->prepare("DELETE FROM event WHERE EventID = ?");
            $delEventStmt->execute([$eventId]);
        }

        // Delete related announcements
        $delAnnStmt = $conn->prepare("DELETE FROM announcement WHERE ProposalID = ?");
        $delAnnStmt->execute([$proposalId]);

        // Delete proposal
        $delPropStmt = $conn->prepare("DELETE FROM proposal WHERE ProposalID = ?");
        $delPropStmt->execute([$proposalId]);

        echo json_encode(['success' => true, 'message' => 'Proposal deleted successfully']);
        exit;

    } else {
        throw new Exception('Unknown action: ' . $action);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
?>