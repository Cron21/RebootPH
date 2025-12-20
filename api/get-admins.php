<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $query = "
        SELECT a.FName, a.LName 
        FROM member m
        JOIN application a ON m.ApplicationID = a.ApplicationID
        WHERE m.Role = 'Executive Director' OR m.Role = 'Finance Officer' OR m.Role = 'Meal Officer' OR m.Role = 'Program Officer' OR m.Role = 'Regional Convenor' OR m.Role = 'Local Coordinator' OR m.Role = 'Member Staff'
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