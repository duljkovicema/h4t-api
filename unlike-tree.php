<?php
function unlikeTree($pdo, $input) {
    $tree_id = $input['tree_id'] ?? null;
    $user_id = $input['user_id'] ?? null;

    if (!$tree_id || !$user_id) {
        http_response_code(400);
        echo json_encode(["error" => "tree_id and user_id required"]);
        return;
    }

    try {
        // provjeri postoji li lajk
        $stmt = $pdo->prepare("SELECT id FROM tree_likes WHERE tree_id = :tree_id AND user_id = :user_id");
        $stmt->execute([":tree_id" => $tree_id, ":user_id" => $user_id]);
        $exists = $stmt->fetch();

        if (!$exists) {
            http_response_code(404);
            echo json_encode(["error" => "Like not found"]);
            return;
        }

        $pdo->beginTransaction();

        // makni iz trees_likes
        $stmt = $pdo->prepare("DELETE FROM tree_likes WHERE tree_id = :tree_id AND user_id = :user_id");
        $stmt->execute([":tree_id" => $tree_id, ":user_id" => $user_id]);

        // smanji broj lajkova
        $stmt = $pdo->prepare("UPDATE trees SET likes = GREATEST(likes - 1, 0) WHERE id = :tree_id");
        $stmt->execute([":tree_id" => $tree_id]);

        $pdo->commit();

        echo json_encode(["success" => true, "message" => "Like removed"]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log($e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error"]);
    }
}
