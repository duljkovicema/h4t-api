<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

function saveCO2($pdo, $data) {
    $user_id = $data['user_id'] ?? null;
    $co2 = isset($data['co2']) ? (float)$data['co2'] : null;
    $years = isset($data['years']) ? (int)$data['years'] : null;
    // Napomena: tablica sadrži stupce: id, user_id, co2, years, created_at, updated_at

    if (!$user_id || $co2 === null) {
        http_response_code(400);
        echo json_encode(["error" => "Missing data"]);
        return;
    }

    try {
        // Upsert bez oslanjanja na UNIQUE(user_id): prvo pokušaj UPDATE, ako nije pogođen red, napravi INSERT
        $pdo->beginTransaction();

        $updateSql = "
            UPDATE user_co2
            SET co2 = :co2,
                years = :years,
                updated_at = NOW()
            WHERE user_id = :user_id
        ";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            'user_id' => $user_id,
            'co2' => $co2,
            'years' => $years,
        ]);

        if ($updateStmt->rowCount() === 0) {
            $insertSql = "
                INSERT INTO user_co2 (user_id, co2, years, created_at, updated_at)
                VALUES (:user_id, :co2, :years, NOW(), NOW())
            ";
            $insertStmt = $pdo->prepare($insertSql);
            $insertStmt->execute([
                'user_id' => $user_id,
                'co2' => $co2,
                'years' => $years,
            ]);
        }

        $pdo->commit();
        http_response_code(201);
        echo json_encode(["status" => "ok"]);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        http_response_code(500);
        echo json_encode([
            "error" => "Database error",
            "details" => $e->getMessage()
        ]);
    }
}