<?php

function updateFirstProtectorName($pdo, $input) {
    // Validacija da user_id postoji
    if (empty($input['user_id'])) {
        http_response_code(400);
        echo json_encode(["error" => "user_id required"]);
        return;
    }

    try {
        $sql = "
            UPDATE users
            SET first_protector_name = :first_protector_name,
                updated_at = NOW()
            WHERE id = :user_id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":first_protector_name" => !empty($input['first_protector_name']) ? trim($input['first_protector_name']) : null,
            ":user_id"              => $input['user_id']
        ]);

        echo json_encode(["success" => true]);
    } catch (PDOException $e) {
        error_log("DB error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error"]);
    }
}

