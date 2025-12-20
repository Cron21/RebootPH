<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLocal = in_array($_SERVER['HTTP_HOST'] ?? 'cli', ['localhost', '127.0.0.1']) || php_sapi_name() === 'cli';

$dbConfig = $isLocal
    ? [
        'host' => '127.0.0.1',
        'name' => 'rebootph',       // local DB name
        'user' => 'root',           // local DB user
        'pass' => ''                // local DB password
    ]
    : [
        // Use the exact host from Hostinger's MySQL details (often "localhost" on shared plans).
        'host' => getenv('DB_HOST') ?: 'localhost',
        'name' => getenv('DB_NAME') ?: 'u569378998_rebootph',
        'user' => getenv('DB_USER') ?: 'u569378998_root',
        'pass' => getenv('DB_PASS') ?: 'b~kY3F4S'
    ];

// Backward compatibility for scripts still using the old variable names.
$host = $dbConfig['host'];
$dbname = $dbConfig['name'];
$username = $dbConfig['user'];
$password_db = $dbConfig['pass'];

try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $dbConfig['host'], $dbConfig['name']);
    $conn = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    // Do not expose full credentials; return concise error for debugging.
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
}

$fname     = $_SESSION['firstName'] ?? 'Guest';
$lname     = $_SESSION['lastName'] ?? '';
$user_role = $_SESSION['role'] ?? null;
?>