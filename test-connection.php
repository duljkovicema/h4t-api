<?php
// Test endpoint za provjeru konekcije
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Test baze podataka
    require_once 'config.php';
    $pdo = getPDO();
    
    // Test query
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "status" => "success",
        "message" => "Backend radi ispravno",
        "database" => "connected",
        "test_query" => $result,
        "timestamp" => date("Y-m-d H:i:s"),
        "environment" => [
            "php_version" => PHP_VERSION,
            "server" => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown'
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Backend greška",
        "error" => $e->getMessage(),
        "timestamp" => date("Y-m-d H:i:s")
    ]);
}
?>
