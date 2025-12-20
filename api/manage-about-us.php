<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';
session_start();

try {
    // Handle GET requests (retrieve data)
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $type = $_GET['type'] ?? null;
        
        if ($type === 'mission' || $type === 'vision') {
            $stmt = $conn->prepare("SELECT id, type, title, subtitle, updated_at FROM about_us_content WHERE type = ?");
            $stmt->execute([$type]);
            $content = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($content) {
                $pointsStmt = $conn->prepare("SELECT point_text FROM about_us_points WHERE content_type = ? ORDER BY display_order ASC");
                $pointsStmt->execute([$type]);
                $content['bullets'] = $pointsStmt->fetchAll(PDO::FETCH_COLUMN);
                echo json_encode(['success' => true, 'content' => $content]);
            } else {
                echo json_encode(['success' => false, 'message' => ucfirst($type) . ' content not found']);
            }
            
        } elseif ($type === 'values') {
            $stmt = $conn->query("SELECT * FROM core_values ORDER BY display_order ASC");
            echo json_encode(['success' => true, 'values' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            
        } elseif ($type === 'org-team') {
            $stmt = $conn->query("SELECT * FROM org_team ORDER BY display_order ASC");
            echo json_encode(['success' => true, 'members' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            
        } else {
            throw new Exception('Invalid type parameter');
        }

    // Handle POST requests
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // Determine if we are receiving JSON or Form Data
        $json = json_decode(file_get_contents('php://input'), true);
        $action = $_POST['action'] ?? $json['action'] ?? null;
        $contentType = $_POST['type'] ?? $json['type'] ?? null;
        
        if (!$action) throw new Exception('Action is required');

        // --- ORG-TEAM LOGIC (Consolidated for Photo Uploads & Constraint Fix) ---
        if ($contentType === 'org-team') {
            if ($action === 'create' || $action === 'update') {
                $name = trim($_POST['name'] ?? '');
                $position = trim($_POST['position'] ?? '');
                $displayOrder = (int)($_POST['display_order'] ?? 0);
                $isActive = (int)($_POST['is_active'] ?? 1);
                
                if (!$name || !$position) throw new Exception('Name and Position are required');

                $photoPath = $_POST['existing_photo'] ?? 'assets/image/default-avatar.png';

                // Handle File Upload
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
                    $uploadDir = '../assets/image/team/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    $fileExt = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                    $fileName = strtolower(str_replace(' ', '_', $name)) . '_' . time() . '.' . $fileExt;
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $fileName)) {
                        $photoPath = 'assets/image/team/' . $fileName;
                    }
                }

                $conn->beginTransaction();
                try {
                    if ($action === 'create') {
                        // 1. MUST insert into parent 'member' table first to satisfy foreign key
                        $stmtMem = $conn->prepare("INSERT INTO member (MemberID) VALUES (NULL)");
                        $stmtMem->execute();
                        $newMemberId = $conn->lastInsertId();

                        // 2. Insert into org_team using the new ID
                        $stmt = $conn->prepare("INSERT INTO org_team (member_id, name, position, photo_path, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$newMemberId, $name, $position, $photoPath, $displayOrder, $isActive]);
                        $msg = "Team member added successfully!";
                    } else {
                        $memberId = (int)$_POST['member_id'];
                        $stmt = $conn->prepare("UPDATE org_team SET name=?, position=?, photo_path=?, display_order=?, is_active=? WHERE member_id=?");
                        $stmt->execute([$name, $position, $photoPath, $displayOrder, $isActive, $memberId]);
                        $msg = "Team member updated successfully!";
                    }
                    $conn->commit();
                    echo json_encode(['success' => true, 'message' => $msg]);
                } catch (Exception $e) {
                    $conn->rollBack();
                    throw $e;
                }
            } elseif ($action === 'delete') {
                $memberId = (int)($json['member_id'] ?? $_POST['member_id'] ?? 0);
                // Deleting from 'member' will cascade delete 'org_team' based on your DB constraint
                $stmt = $conn->prepare("DELETE FROM member WHERE MemberID = ?");
                $stmt->execute([$memberId]);
                echo json_encode(['success' => true, 'message' => 'Member deleted successfully']);
            }

        // --- MISSION/VISION LOGIC ---
        } elseif ($contentType === 'mission' || $contentType === 'vision') {
            $title = trim($json['title'] ?? '');
            $subtitle = trim($json['subtitle'] ?? '');
            $bullets = $json['bullets'] ?? [];

            $conn->beginTransaction();
            try {
                // Upsert logic for about_us_content
                $stmt = $conn->prepare("INSERT INTO about_us_content (type, title, subtitle) VALUES (?, ?, ?) 
                                        ON DUPLICATE KEY UPDATE title=?, subtitle=?, updated_at=NOW()");
                $stmt->execute([$contentType, $title, $subtitle, $title, $subtitle]);
                
                // Refresh points
                $conn->prepare("DELETE FROM about_us_points WHERE content_type = ?")->execute([$contentType]);
                $pointStmt = $conn->prepare("INSERT INTO about_us_points (content_type, point_text, display_order) VALUES (?, ?, ?)");
                foreach ($bullets as $idx => $text) {
                    if (trim($text)) $pointStmt->execute([$contentType, trim($text), $idx + 1]);
                }
                
                $conn->commit();
                echo json_encode(['success' => true, 'message' => ucfirst($contentType) . ' updated']);
            } catch (Exception $e) { $conn->rollBack(); throw $e; }

        // --- CORE VALUES LOGIC ---
        } elseif ($contentType === 'values') {
            if ($action === 'delete') {
                $stmt = $conn->prepare("DELETE FROM core_values WHERE value_id = ?");
                $stmt->execute([$json['value_id']]);
                echo json_encode(['success' => true, 'message' => 'Value deleted']);
            } else {
                $title = trim($json['title'] ?? '');
                if ($action === 'create') {
                    $stmt = $conn->prepare("INSERT INTO core_values (title, description, icon_class, display_order) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$title, $json['description'], $json['icon_class'], $json['display_order']]);
                } else {
                    $stmt = $conn->prepare("UPDATE core_values SET title=?, description=?, icon_class=?, display_order=? WHERE value_id=?");
                    $stmt->execute([$title, $json['description'], $json['icon_class'], $json['display_order'], $json['value_id']]);
                }
                echo json_encode(['success' => true, 'message' => 'Value saved']);
            }
        }
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>