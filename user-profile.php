<?php

function getUserProfile($pdo, $user_id) {
    if (empty($user_id)) {
        http_response_code(400);
        echo json_encode(["error" => "user_id required"]);
        return;
    }

    try {
        $sql = "
            SELECT first_name, last_name, company, nickname,
                   show_first_name, show_last_name, show_company, show_nickname
            FROM users
            WHERE id = :user_id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([":user_id" => $user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Ako nema korisnika, vrati prazan objekat {}
        echo json_encode($row ? $row : new stdClass());
    } catch (PDOException $e) {
        error_log("DB error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error"]);
    }
}
