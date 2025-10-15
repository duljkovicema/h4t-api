<?php
function setHighValue($pdo, $data) {
    $tree_id = $data['tree_id'] ?? null;
    $high_value = $data['high_value'] ?? null;
    $admin_user_id = $data['admin_user_id'] ?? null;

    // Debug log
    error_log("setHighValue called with: tree_id=$tree_id, high_value=$high_value, admin_user_id=$admin_user_id");

    if (!$tree_id || $high_value === null || !$admin_user_id) {
        http_response_code(400);
        echo json_encode(["error" => "tree_id, high_value i admin_user_id su obavezni"]);
        return;
    }

    try {
        // Provjeri da li je korisnik admin
        $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = :user_id");
        $stmt->execute(['user_id' => $admin_user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !$user['is_admin']) {
            http_response_code(403);
            echo json_encode(["error" => "Samo admin korisnici mogu označiti high value stabla"]);
            return;
        }

        // Provjeri da li stablo postoji
        $stmt = $pdo->prepare("SELECT id FROM trees WHERE id = :tree_id");
        $stmt->execute(['tree_id' => $tree_id]);
        $tree = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tree) {
            http_response_code(404);
            echo json_encode(["error" => "Stablo nije pronađeno"]);
            return;
        }

        // Ažuriraj high_value flag
        $stmt = $pdo->prepare("
            UPDATE trees 
            SET high_value = :high_value 
            WHERE id = :tree_id
        ");
        $stmt->execute([
            'high_value' => $high_value ? 1 : 0,
            'tree_id' => $tree_id
        ]);

        echo json_encode([
            "success" => true,
            "tree_id" => $tree_id,
            "high_value" => $high_value
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "error" => "Database error",
            "details" => $e->getMessage()
        ]);
    }
}
?>
