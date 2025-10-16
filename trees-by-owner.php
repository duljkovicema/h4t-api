<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

function getTreesByOwner($pdo, $owner_id)
{
    if (empty($owner_id)) {
        http_response_code(400);
        echo json_encode(["error" => "owner_id required"]);
        return;
    }

    try {
        $sql = "
            SELECT 
                t.id, t.latitude, t.longitude, t.created_at_local, t.created_at,
                t.image_path, t.height_m, t.diameter_cm, t.species, t.carbon_kg,
                t.no2_g_per_year, t.so2_g_per_year, t.o3_g_per_year,
                z.name as zone_name, z.partner as zone_partner
            FROM trees t
            LEFT JOIN zones z ON ST_Contains(z.geom, ST_GeomFromText(CONCAT('POINT(', t.longitude, ' ', t.latitude, ')'), 4326))
            WHERE t.created_by = :owner_id
            ORDER BY t.id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([":owner_id" => $owner_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode($rows);
    } catch (PDOException $e) {
        error_log($e->getMessage()); // log error
        http_response_code(500);
        echo json_encode(["error" => "Database error"]);
    }
}