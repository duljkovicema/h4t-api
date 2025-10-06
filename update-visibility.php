<?php

function updateVisibility($pdo, $input) {
    $allowedFields = ["show_first_name", "show_last_name", "show_company", "show_nickname"];

    $user_id = $input['user_id'] ?? null;
    $field   = $input['field'] ?? null;
    $value   = $input['value'] ?? null;

    if (empty($user_id) || empty($field)) {
        http_response_code(400);
        echo json_encode(["error" => "user_id and field required"]);
        return;
    }

    if (!in_array($field, $allowedFields, true)) {
        http_response_code(400);
        echo json_encode(["error" => "Nevažeće polje"]);
        return;
    }

    try {
        // ⚠️ dinamičko polje – koristi se samo nakon validacije!
        $sql = "UPDATE users SET {$field} = :value, updated_at = NOW() WHERE id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":value"   => $value,
            ":user_id" => $user_id
        ]);

        echo json_encode(["success" => true]);
    } catch (PDOException $e) {
        error_log("DB error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error"]);
    }
}
