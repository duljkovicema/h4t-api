<?php
function getHighValueTrees($pdo) {
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
                t.high_value,
                t.high_value_cost,
                t.high_value_name,
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
            WHERE t.high_value = TRUE
            ORDER BY t.created_at DESC, t.id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $trees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Dodaj broj lajkova za svako stablo
        foreach ($trees as &$tree) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as likes FROM tree_likes WHERE tree_id = ?");
            $stmt->execute([$tree['id']]);
            $likes = $stmt->fetch(PDO::FETCH_ASSOC);
            $tree['likes'] = $likes['likes'] ?? 0;
        }

        echo json_encode($trees);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "error" => "Database error",
            "details" => $e->getMessage()
        ]);
    }
}
?>
