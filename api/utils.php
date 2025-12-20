<?php
/**
 * Common utility functions for API endpoints
 */

/**
 * Set standard CORS and JSON headers
 */
function setApiHeaders() {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

/**
 * Check if user is authenticated
 * @return int|null Member ID if authenticated, null otherwise
 */
function checkAuthentication() {
    session_start();
    return $_SESSION['memberID'] ?? null;
}

/**
 * Require authentication - exits if not authenticated
 * @return int Member ID
 */
function requireAuthentication() {
    $memberId = checkAuthentication();
    if (!$memberId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
    return $memberId;
}

/**
 * Require admin role - exits if not admin
 * @return int Member ID
 */
function requireAdmin() {
    $memberId = requireAuthentication();
    session_start();
    $role = $_SESSION['role'] ?? null;
    
    if ($role !== 'Admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }
    return $memberId;
}

/**
 * Send JSON error response
 * @param string $message Error message
 * @param int $code HTTP status code
 */
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

/**
 * Send JSON success response
 * @param mixed $data Response data
 * @param string $message Optional success message
 */
function sendSuccess($data = null, $message = null) {
    $response = ['success' => true];
    if ($message) {
        $response['message'] = $message;
    }
    if ($data !== null) {
        if (is_array($data)) {
            $response = array_merge($response, $data);
        } else {
            $response['data'] = $data;
        }
    }
    echo json_encode($response);
    exit;
}

/**
 * Validate required fields in input data
 * @param array $data Input data
 * @param array $requiredFields List of required field names
 * @throws Exception if any required field is missing
 */
function validateRequired($data, $requiredFields) {
    $missing = [];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
            $missing[] = $field;
        }
    }
    if (!empty($missing)) {
        throw new Exception('Missing required fields: ' . implode(', ', $missing));
    }
}

/**
 * Sanitize string input
 * @param string $input Input string
 * @return string Sanitized string
 */
function sanitizeInput($input) {
    return trim(htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8'));
}

/**
 * Validate email format
 * @param string $email Email address
 * @return bool True if valid
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
?>

