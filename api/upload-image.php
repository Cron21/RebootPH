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
    $adminId = $_SESSION['memberID'] ?? null;
    if (!$adminId) throw new Exception('Not authenticated');

    // Support both 'image' and 'file' parameter names
    $file = $_FILES['file'] ?? $_FILES['image'] ?? null;
    
    if (!$file) {
        throw new Exception('No image file provided');
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    // Get file extension
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload error code: ' . $file['error']);
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('File size exceeds 5MB limit');
    }

    if (!in_array($fileExtension, $allowedExtensions)) {
        throw new Exception('Invalid file format. Allowed: JPG, PNG, GIF, WebP');
    }

    // Determine upload type (profile or general image)
    $uploadType = $_POST['type'] ?? 'general';
    
    if ($uploadType === 'profile') {
        // Create profile images directory
        $uploadsDir = __DIR__ . '/../uploads/profile-images/';
    } else {
        // Default general images directory
        $uploadsDir = __DIR__ . '/../assets/image/';
    }

    if (!is_dir($uploadsDir)) {
        if (!mkdir($uploadsDir, 0755, true)) {
            throw new Exception('Failed to create uploads directory');
        }
    }

    // Generate unique filename
    $timestamp = time();
    $randomStr = bin2hex(random_bytes(5));
    $newFileName = "img_{$timestamp}_{$randomStr}.{$fileExtension}";
    $filePath = $uploadsDir . $newFileName;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Failed to save file');
    }

    // Store relative path for database
    if ($uploadType === 'profile') {
        $imagePath = "uploads/profile-images/" . $newFileName;
    } else {
        $imagePath = "assets/image/" . $newFileName;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Image uploaded successfully',
        'path' => $imagePath,
        'imagePath' => $imagePath,
        'fileName' => $newFileName
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>