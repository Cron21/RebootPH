<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $query = "
        SELECT a.FName, a.LName 
        FROM member m
        JOIN application a ON m.ApplicationID = a.ApplicationID
        WHERE m.Role = 'Admin'
        ORDER BY a.FName ASC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'admins' => $admins
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => "Database Error: " . $e->getMessage()
    ]);
}
?>