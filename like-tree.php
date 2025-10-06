<?php
function likeTree($pdo, $input) {
    $user_id = $input['user_id'] ?? null;
    $tree_id = $input['tree_id'] ?? null;

    if (!$user_id || !$tree_id) {
        http_response_code(400);
        echo json_encode(["error" => "Nedostaje user_id ili tree_id"]);
        return;
    }

    try {
        // provjeri postoji li već lajk
        $stmt = $pdo->prepare("SELECT 1 FROM tree_likes WHERE tree_id = ? AND user_id = ?");
        $stmt->execute([$tree_id, $user_id]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(["error" => "Već si lajkao ovo stablo"]);
            return;
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO tree_likes (tree_id, user_id) VALUES (?, ?)");
        $stmt->execute([$tree_id, $user_id]);

        $stmt = $pdo->prepare("UPDATE trees SET likes = likes + 1 WHERE id = ?");
        $stmt->execute([$tree_id]);

        $stmt = $pdo->prepare("SELECT likes FROM trees WHERE id = ?");
        $stmt->execute([$tree_id]);
        $likes = $stmt->fetchColumn();

        $pdo->commit();

        echo json_encode(["success" => true, "likes" => (int)$likes]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "Database error", "details" => $e->getMessage()]);
    }
}
