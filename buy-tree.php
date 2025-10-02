<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

function buyTree($pdo, $data) {
    $tree_id = $data['tree_id'] ?? null;
    $user_id = $data['user_id'] ?? null;

    if (!$tree_id || !$user_id) {
        http_response_code(400);
        echo "tree_id i user_id su obvezni";
        return;
    }

    try {
        // 1. Pokušaj ažurirati red (samo ako je user_id NULL)
        $sql = "
            UPDATE trees
            SET user_id = :user_id
            WHERE id = :tree_id AND user_id IS NULL
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $user_id,
            'tree_id' => $tree_id
        ]);

        if ($stmt->rowCount() === 0) {
            // ništa nije ažurirano → drvo već zauzeto ili ne postoji
            http_response_code(409);
            echo "Tree already owned";
            return;
        }

        // 2. Vrati podatke o stablu
        $sql = "SELECT id, user_id FROM trees WHERE id = :tree_id LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['tree_id' => $tree_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($row);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "error" => "Server error",
            "details" => $e->getMessage()
        ]);
    }
}
