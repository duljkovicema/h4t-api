<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

function getUserLikesCount($pdo, $user_id) {
    if (!$user_id) {
        http_response_code(400);
        echo json_encode(["error" => "user_id required"]);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tree_likes WHERE user_id = :user_id");
        $stmt->execute([':user_id' => (int)$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "count" => (int)($result['count'] ?? 0)
        ]);
    } catch (PDOException $e) {
        error_log("DB error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error"]);
    }
}

