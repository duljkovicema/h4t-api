<?php
function getTreeLikes($pdo, $tree_id, $user_id) {
    try {
        // broj lajkova
        $stmt = $pdo->prepare("SELECT likes FROM trees WHERE id = :tree_id");
        $stmt->execute([":tree_id" => $tree_id]);
        $likes = $stmt->fetchColumn() ?? 0;

        // provjera jel user lajkao
        $stmt = $pdo->prepare("SELECT 1 FROM tree_likes WHERE tree_id = :tree_id AND user_id = :user_id");
        $stmt->execute([":tree_id" => $tree_id, ":user_id" => $user_id]);
        $liked = (bool) $stmt->fetch();

        echo json_encode([
            "likes" => (int)$likes,
            "liked" => $liked
        ]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error"]);
    }
}
