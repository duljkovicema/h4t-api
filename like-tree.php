<?php
function likeTree($pdo, $input) {
    $tree_id = $input['tree_id'] ?? null;
    $user_id = $input['user_id'] ?? null;

    if (!$tree_id || !$user_id) {
        http_response_code(400);
        echo json_encode(["error" => "tree_id and user_id required"]);
        return;
    }

    try {
        // provjeri je li već lajkao
        $stmt = $pdo->prepare("SELECT id FROM trees_likes WHERE tree_id = :tree_id AND user_id = :user_id");
        $stmt->execute([":tree_id" => $tree_id, ":user_id" => $user_id]);
        $exists = $stmt->fetch();

        if ($exists) {
            http_response_code(409); // Conflict
            echo json_encode(["error" => "Already liked"]);
            return;
        }

        // upiši novi lajk
        $stmt = $pdo->prepare("INSERT INTO trees_likes (tree_id, user_id) VALUES (:tree_id, :user_id)");
        $stmt->execute([":tree_id" => $tree_id, ":user_id" => $user_id]);

        // povećaj broj lajkova u trees tablici
        $stmt = $pdo->prepare("UPDATE trees SET likes = likes + 1 WHERE id = :tree_id");
        $stmt->execute([":tree_id" => $tree_id]);

        echo json_encode(["success" => true, "message" => "Tree liked"]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error"]);
    }
}
