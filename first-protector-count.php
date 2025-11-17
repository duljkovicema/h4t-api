<?php

function getFirstProtectorCount($pdo, $user_id)
{
    if (empty($user_id)) {
        http_response_code(400);
        echo json_encode(["error" => "user_id required"]);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM first_protector WHERE user_id = :user_id");
        $stmt->execute([":user_id" => $user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            "user_id" => (int)$user_id,
            "count" => isset($row["cnt"]) ? (int)$row["cnt"] : 0
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database error"]);
    }
}

