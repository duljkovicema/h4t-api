<?php

function checkPaymentStatus($pdo, $id) {
    if (empty($payment_id)) {
        http_response_code(400);
        echo json_encode(["error" => "id required"]);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT status FROM payments WHERE id = :id");
        $stmt->execute([":id" => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            echo json_encode(["status" => $row['status']]);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Not found"]);
        }
    } catch (PDOException $e) {
        error_log("DB error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error"]);
    }
}
