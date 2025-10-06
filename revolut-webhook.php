<?php

function handleRevolutWebhook($pdo) {
    // čitaj JSON telo
    $input = json_decode(file_get_contents("php://input"), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid JSON"]);
        return;
    }

    try {
        $paymentId = $input['order_id'] ?? null;
        $status    = $input['event'] ?? null; // npr. "ORDER_COMPLETED"

        if (empty($paymentId) || empty($status)) {
            http_response_code(400);
            echo json_encode(["error" => "Missing order_id or event"]);
            return;
        }

        $stmt = $pdo->prepare("
            UPDATE payments 
            SET status = :status, updated_at = NOW()
            WHERE payment_id = :payment_id
        ");
        $stmt->execute([
            ":status"     => $status,
            ":payment_id" => $paymentId
        ]);

        http_response_code(200);
        echo json_encode(["success" => true]);
    } catch (PDOException $e) {
        error_log("Webhook DB error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error"]);
    }
}
