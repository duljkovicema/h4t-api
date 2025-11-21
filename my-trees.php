<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/sponsorships.php';

function getMyTrees($pdo, $user_id) {
    if (!$user_id) {
        http_response_code(400);
        echo json_encode(["error" => "user_id required"]);
        return;
    }

    try {
        $sql = "
            SELECT 
                id, 
                latitude, 
                longitude, 
                created_at_local, 
                created_at, 
                image_path, 
                height_m, 
                diameter_cm, 
                species, 
                carbon_kg, 
                no2_g_per_year, 
                so2_g_per_year, 
                o3_g_per_year,
                ts.zone_sponsorship_id AS tree_zone_sponsorship_id,
                ts.mode AS tree_sponsorship_mode,
                ts.tree_message AS tree_sponsorship_message,
                sp.id AS tree_sponsor_id,
                sp.name AS tree_sponsor_name,
                sp.logo_url AS tree_sponsor_logo,
                sp.website_url AS tree_sponsor_website
            FROM trees
            LEFT JOIN tree_sponsorships ts ON ts.tree_id = trees.id
            LEFT JOIN sponsors sp ON sp.id = ts.sponsor_id
            WHERE user_id = :user_id 
            ORDER BY id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
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
