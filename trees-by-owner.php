<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

function getTreesByOwner($pdo, $owner_id) {
    if (empty($owner_id)) {
        http_response_code(400);
        echo json_encode(["error" => "owner_id required"]);
        return;
    }

    try {
        $sql = "
            SELECT id, latitude, longitude, created_at_local, created_at,
                image_path, height_m, diameter_cm, species, carbon_kg,
                no2_g_per_year, so2_g_per_year, o3_g_per_year
            FROM trees
            WHERE created_by = :owner_id
            ORDER BY id DESC
        ";


        $stmt = $pdo->prepare($sql);
        $stmt->execute([":owner_id" => $owner_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode($rows);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error"]);
    }
}
