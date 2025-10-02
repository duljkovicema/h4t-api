<?php
header("Content-Type: application/json; charset=UTF-8");

// Učitaj config i konekciju
$config = require __DIR__ . "/config.php";

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['db']};charset={$config['charset']}",
        $config['user'],
        $config['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

// Provera parametra
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "user_id required"]);
    exit;
}

$user_id = $_GET['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT id, latitude, longitude, created_at_local, created_at, image_path, 
               height_m, diameter_cm, species, carbon_kg, 
               no2_g_per_year, so2_g_per_year, o3_g_per_year
        FROM trees
        WHERE user_id = :user_id
        ORDER BY id DESC
    ");
    $stmt->execute([":user_id" => $user_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rows);

} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Database error"]);
}
