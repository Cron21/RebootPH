<?php
// api/manage-system-settings.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';
session_start();

// Only allow authenticated admins
if (!isset($_SESSION['memberID'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get all system settings
    try {
        // Create settings table if it doesn't exist
        $conn->exec("
            CREATE TABLE IF NOT EXISTS system_settings (
                SettingID INT PRIMARY KEY AUTO_INCREMENT,
                SettingKey VARCHAR(100) UNIQUE NOT NULL,
                SettingValue VARCHAR(255),
                DataType VARCHAR(50),
                LastUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");

        $stmt = $conn->prepare("SELECT SettingKey, SettingValue, DataType FROM system_settings");
        $stmt->execute();
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convert settings to associative array
        $settingsArray = [];
        foreach ($settings as $setting) {
            $value = $setting['SettingValue'];
            
            // Convert value based on DataType
            if ($setting['DataType'] === 'boolean') {
                $value = $value === '1' || $value === 'true';
            } elseif ($setting['DataType'] === 'integer') {
                $value = (int)$value;
            }
            
            $settingsArray[$setting['SettingKey']] = $value;
        }

        echo json_encode([
            'success' => true,
            'settings' => $settingsArray
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? null;

        if (!$action) {
            throw new Exception('Action is required');
        }

        if ($action === 'update') {
            $settingKey = $data['settingKey'] ?? null;
            $settingValue = $data['settingValue'] ?? null;
            $dataType = $data['dataType'] ?? 'string';

            if (!$settingKey) {
                throw new Exception('Setting key is required');
            }

            // Ensure table exists
            $conn->exec("
                CREATE TABLE IF NOT EXISTS system_settings (
                    SettingID INT PRIMARY KEY AUTO_INCREMENT,
                    SettingKey VARCHAR(100) UNIQUE NOT NULL,
                    SettingValue VARCHAR(255),
                    DataType VARCHAR(50),
                    LastUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");

            // Check if setting exists
            $checkStmt = $conn->prepare("SELECT SettingID FROM system_settings WHERE SettingKey = ?");
            $checkStmt->execute([$settingKey]);

            if ($checkStmt->rowCount() > 0) {
                // Update existing setting
                $updateStmt = $conn->prepare("
                    UPDATE system_settings 
                    SET SettingValue = ?, DataType = ?
                    WHERE SettingKey = ?
                ");
                $updateStmt->execute([$settingValue, $dataType, $settingKey]);
            } else {
                // Insert new setting
                $insertStmt = $conn->prepare("
                    INSERT INTO system_settings (SettingKey, SettingValue, DataType)
                    VALUES (?, ?, ?)
                ");
                $insertStmt->execute([$settingKey, $settingValue, $dataType]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Setting updated successfully'
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
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>