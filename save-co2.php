<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

function saveCO2($pdo, $data) {
    $user_id = $data['user_id'] ?? null;
    $co2 = $data['co2'] ?? null;
    $years = $data['years'] ?? null;

    if (!$user_id || $co2 === null) {
        http_response_code(400);
        echo json_encode(["error" => "Missing data"]);
        return;
    }

    try {
        $sql = "
            INSERT INTO user_co2 (user_id, co2, years, created_at, updated_at)
            VALUES (:user_id, :co2, :years, NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
                co2 = VALUES(co2),
                years = VALUES(years),
                updated_at = NOW()
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $user_id,
            'co2'     => $co2,
            'years'   => $years
        ]);

        http_response_code(201);
        echo "CO₂ saved (upsert)";
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "error" => "Database error",
            "details" => $e->getMessage()
        ]);
    }
}