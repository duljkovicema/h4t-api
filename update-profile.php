<?php

function updateUserProfile($pdo, $input) {
    // Validacija da user_id postoji
    if (empty($input['user_id'])) {
        http_response_code(400);
        echo json_encode(["error" => "user_id required"]);
        return;
    }

    try {
        $sql = "
            UPDATE users
            SET first_name     = :first_name,
                last_name      = :last_name,
                company        = :company,
                nickname       = :nickname,
                show_first_name = :show_first_name,
                show_last_name  = :show_last_name,
                show_company    = :show_company,
                show_nickname   = :show_nickname
            WHERE id = :user_id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":first_name"     => $input['first_name'] ?? null,
            ":last_name"      => $input['last_name'] ?? null,
            ":company"        => $input['company'] ?? null,
            ":nickname"       => $input['nickname'] ?? null,
            ":show_first_name"=> $input['show_first_name'] ?? 0,
            ":show_last_name" => $input['show_last_name'] ?? 0,
            ":show_company"   => $input['show_company'] ?? 0,
            ":show_nickname"  => $input['show_nickname'] ?? 0,
            ":user_id"        => $input['user_id']
        ]);

        echo json_encode(["success" => true]);
    } catch (PDOException $e) {
        error_log("DB error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error"]);
    }
}
