<?php
// api/manage-system-maintenance.php
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? null;

        if (!$action) {
            throw new Exception('Action is required');
        }

        // ===== BACKUP MANAGEMENT =====
        if ($action === 'backup') {
            $backupDir = __DIR__ . '/../backups/';
            
            // Create backups directory if it doesn't exist
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Generate backup filename with timestamp
            $timestamp = date('Y-m-d_H-i-s');
            $backupFileName = "rebootph_backup_{$timestamp}.sql";
            $backupFilePath = $backupDir . $backupFileName;

            // Database credentials
            $host = 'localhost';
            $dbname = 'rebootph';
            $username = 'root';
            $password = '';

            // Create backup using mysqldump
            $command = "mysqldump --user={$username} --password={$password} {$dbname} > {$backupFilePath} 2>&1";
            
            // Execute backup command
            exec($command, $output, $returnVar);

            if ($returnVar === 0 && file_exists($backupFilePath)) {
                $fileSize = filesize($backupFilePath);
                $fileSizeFormatted = formatBytes($fileSize);

                echo json_encode([
                    'success' => true,
                    'message' => 'Backup created successfully',
                    'fileName' => $backupFileName,
                    'fileSize' => $fileSizeFormatted,
                    'timestamp' => $timestamp
                ]);
            } else {
                throw new Exception('Failed to create backup. Error: ' . implode("\n", $output));
            }
        }

        // ===== LIST BACKUPS =====
        elseif ($action === 'listBackups') {
            $backupDir = __DIR__ . '/../backups/';
            
            if (!is_dir($backupDir)) {
                echo json_encode([
                    'success' => true,
                    'backups' => [],
                    'count' => 0
                ]);
                exit;
            }

            $files = array_diff(scandir($backupDir, SCANDIR_SORT_DESCENDING), ['.', '..']);
            $backups = [];

            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                    $filePath = $backupDir . $file;
                    $fileSize = filesize($filePath);
                    $fileTime = filemtime($filePath);

                    $backups[] = [
                        'name' => $file,
                        'size' => formatBytes($fileSize),
                        'sizeRaw' => $fileSize,
                        'created' => date('Y-m-d H:i:s', $fileTime),
                        'timestamp' => $fileTime
                    ];
                }
            }

            echo json_encode([
                'success' => true,
                'backups' => array_slice($backups, 0, 10), // Return latest 10 backups
                'count' => count($backups)
            ]);
        }

        // ===== DELETE BACKUP =====
        elseif ($action === 'deleteBackup') {
            $fileName = $data['fileName'] ?? null;

            if (!$fileName) {
                throw new Exception('File name is required');
            }

            // Validate filename to prevent directory traversal
            if (strpos($fileName, '..') !== false || strpos($fileName, '/') !== false) {
                throw new Exception('Invalid file name');
            }

            $backupDir = __DIR__ . '/../backups/';
            $filePath = $backupDir . $fileName;

            if (file_exists($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'sql') {
                if (unlink($filePath)) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Backup deleted successfully'
                    ]);
                } else {
                    throw new Exception('Failed to delete backup file');
                }
            } else {
                throw new Exception('Backup file not found');
            }
        }

        // ===== CLEAR CACHE =====
        elseif ($action === 'clearCache') {
            // Clear any application cache files
            $cacheDir = __DIR__ . '/../cache/';
            
            if (is_dir($cacheDir)) {
                $files = array_diff(scandir($cacheDir), ['.', '..']);
                $deletedCount = 0;

                foreach ($files as $file) {
                    $filePath = $cacheDir . $file;
                    if (is_file($filePath)) {
                        unlink($filePath);
                        $deletedCount++;
                    }
                }

                echo json_encode([
                    'success' => true,
                    'message' => "System cache cleared successfully ($deletedCount files deleted)",
                    'filesDeleted' => $deletedCount
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'message' => 'No cache files found to clear'
                ]);
            }
        }

        // ===== GET SYSTEM STATS =====
        elseif ($action === 'getSystemStats') {
            $backupDir = __DIR__ . '/../backups/';
            $backupCount = 0;
            $totalBackupSize = 0;

            if (is_dir($backupDir)) {
                $files = array_diff(scandir($backupDir), ['.', '..']);
                foreach ($files as $file) {
                    $filePath = $backupDir . $file;
                    if (pathinfo($filePath, PATHINFO_EXTENSION) === 'sql') {
                        $backupCount++;
                        $totalBackupSize += filesize($filePath);
                    }
                }
            }

            // Get database size
            $result = $conn->query("SELECT SUM(data_length + index_length) as size FROM information_schema.TABLES WHERE table_schema = 'rebootph'");
            $dbSize = $result->fetch(PDO::FETCH_ASSOC)['size'] ?? 0;

            // Get table count
            $result = $conn->query("SELECT COUNT(*) as count FROM information_schema.TABLES WHERE table_schema = 'rebootph'");
            $tableCount = $result->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

            echo json_encode([
                'success' => true,
                'stats' => [
                    'databaseSize' => formatBytes($dbSize),
                    'databaseSizeRaw' => $dbSize,
                    'tableCount' => $tableCount,
                    'backupCount' => $backupCount,
                    'totalBackupSize' => formatBytes($totalBackupSize),
                    'totalBackupSizeRaw' => $totalBackupSize,
                    'serverTime' => date('Y-m-d H:i:s'),
                    'phpVersion' => phpversion(),
                    'mysqlVersion' => $conn->getAttribute(PDO::ATTR_SERVER_VERSION)
                ]
            ]);
        }

        else {
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

// Helper function to format bytes
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));

    return round($bytes, $precision) . ' ' . $units[$pow];
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>