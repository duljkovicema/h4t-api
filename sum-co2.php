<?php

function getSumCO2($pdo, $user_id)
{
    if (empty($user_id)) {
        http_response_code(400);
        echo json_encode(["error" => "user_id required"]);
        return;
    }

    try {
        $sql = "SELECT COALESCE(SUM(carbon_kg), 0) * 100 AS sum_co2 FROM trees WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":user_id" => $user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode(["sum_co2" => (float)($row["sum_co2"] ?? 0)]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error"]);
    }
}


