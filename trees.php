<?php

require_once __DIR__ . '/sponsorships.php';

function getTrees($pdo) {
    try {
        $sort = isset($_GET['sort']) ? strtolower(trim($_GET['sort'])) : '';
        // Bez prioriteta kupljeno/otkupljeno – globalno sortiranje
        $orderBy = "t.created_at_local DESC, t.id DESC";
        if ($sort === 'likes') {
            $orderBy = "t.likes DESC, t.created_at_local DESC, t.id DESC";
        }

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
                ts.zone_sponsorship_id AS tree_zone_sponsorship_id,
                ts.mode AS tree_sponsorship_mode,
                ts.tree_message AS tree_sponsorship_message,
                sp.id AS tree_sponsor_id,
                sp.name AS tree_sponsor_name,
                sp.logo_url AS tree_sponsor_logo,
                sp.website_url AS tree_sponsor_website,
                COALESCE(
                    NULLIF(TRIM(CONCAT(
                        IF(u.show_first_name AND u.first_name IS NOT NULL, CONCAT(u.first_name,' '), ''),
                        IF(u.show_last_name AND u.last_name IS NOT NULL, CONCAT(u.last_name,' '), ''),
                        IF(u.show_company AND u.company IS NOT NULL, CONCAT('(',u.company,')'), ''),
                        IF(u.show_nickname AND u.nickname IS NOT NULL, CONCAT('\"',u.nickname,'\"'), '')
                    )),''),
                    CONCAT('Korisnik #', t.created_by)
                ) AS display_name,
                COALESCE(
                    NULLIF(TRIM(CONCAT(
                        IF(u_owner.show_first_name AND u_owner.first_name IS NOT NULL, CONCAT(u_owner.first_name,' '), ''),
                        IF(u_owner.show_last_name AND u_owner.last_name IS NOT NULL, CONCAT(u_owner.last_name,' '), ''),
                        IF(u_owner.show_company AND u_owner.company IS NOT NULL, CONCAT('(',u_owner.company,')'), ''),
                        IF(u_owner.show_nickname AND u_owner.nickname IS NOT NULL, CONCAT('\"',u_owner.nickname,'\"'), '')
                    )),''), 
                    CASE WHEN t.user_id IS NOT NULL THEN CONCAT('Korisnik #', t.user_id) ELSE NULL END
                ) AS owner_display_name,
                fp.user_id AS first_protector_user_id,
                COALESCE(
                    NULLIF(TRIM(u_fp.first_protector_name), ''),
                    NULLIF(TRIM(u_fp.nickname), ''),
                    NULLIF(TRIM(CONCAT(
                        IF(u_fp.show_first_name AND u_fp.first_name IS NOT NULL, CONCAT(u_fp.first_name,' '), ''),
                        IF(u_fp.show_last_name AND u_fp.last_name IS NOT NULL, CONCAT(u_fp.last_name,' '), ''),
                        IF(u_fp.show_company AND u_fp.company IS NOT NULL, CONCAT('(',u_fp.company,')'), ''),
                        IF(u_fp.show_nickname AND u_fp.nickname IS NOT NULL, CONCAT('\"',u_fp.nickname,'\"'), '')
                    )),''), 
                    CASE WHEN fp.user_id IS NOT NULL THEN CONCAT('Korisnik #', fp.user_id) ELSE NULL END
                ) AS first_protector_display_name
            FROM trees t
            LEFT JOIN users u ON t.created_by = u.id
            LEFT JOIN users u_owner ON t.user_id = u_owner.id
            LEFT JOIN first_protector fp ON fp.tree_id = t.id
            LEFT JOIN users u_fp ON fp.user_id = u_fp.id
            LEFT JOIN tree_sponsorships ts ON ts.tree_id = t.id
            LEFT JOIN sponsors sp ON sp.id = ts.sponsor_id
            ORDER BY $orderBy
        ";

        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        hydrateTreeSponsorships($rows);

        echo json_encode($rows);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "error" => "Database error",
            "details" => $e->getMessage()
        ]);
    }
}
