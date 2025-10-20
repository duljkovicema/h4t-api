<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

function getUserCO2($pdo, $user_id) {
    if (!$user_id) {
        http_response_code(400);
        echo json_encode(["error" => "user_id required"]);
        return;
    }

    try {
        // Uzmemo najnoviji zapis s definiranim CO2 (ako postoji više zapisa po korisniku)
        $sql = "SELECT co2, years FROM user_co2 WHERE user_id = :user_id AND co2 IS NOT NULL ORDER BY id DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            echo json_encode([
                "co2" => is_null($row['co2']) ? null : (float)$row['co2'],
                "years" => isset($row['years']) ? (int)$row['years'] : null
            ]);
        } else {
            echo json_encode(new stdClass()); // prazan {}
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "error" => "Database error",
            "details" => $e->getMessage()
        ]);
    }
}