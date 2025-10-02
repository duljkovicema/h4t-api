<?php

function getTrees($pdo) {
    try {
        $sql = "
            SELECT
                t.id,
                t.tree_number,
                t.latitude,
                t.longitude,
                t.created_at_local,
                t.created_at,
                t.user_id,
                t.image_path,
                t.height_m,
                t.diameter_cm,
                t.species,
                t.carbon_kg,
                t.no2_g_per_year,
                t.so2_g_per_year,
                t.o3_g_per_year,
                t.created_by,
                COALESCE(
                    NULLIF(TRIM(CONCAT(
                        IF(u.show_first_name AND u.first_name IS NOT NULL, CONCAT(u.first_name,' '), ''),
                        IF(u.show_last_name AND u.last_name IS NOT NULL, CONCAT(u.last_name,' '), ''),
                        IF(u.show_company AND u.company IS NOT NULL, CONCAT('(',u.company,')'), ''),
                        IF(u.show_nickname AND u.nickname IS NOT NULL, CONCAT('\"',u.nickname,'\"'), '')
                    )),''),
                    CONCAT('Korisnik #', t.created_by)
                ) AS display_name
            FROM trees t
            LEFT JOIN users u ON t.created_by = u.id
            ORDER BY (t.user_id IS NULL) DESC, t.id DESC
        ";

        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($rows);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "error" => "Database error",
            "details" => $e->getMessage()
        ]);
    }
}
