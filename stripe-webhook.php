<?php

function handleStripeWebhook($pdo) {
    // čitaj JSON telo
    $input = json_decode(file_get_contents("php://input"), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid JSON"]);
        return;
    }

    try {
        $eventType = $input['type'] ?? null;
        $sessionId = $input['data']['object']['id'] ?? null;

        if (!$eventType || !$sessionId) {
            http_response_code(400);
            echo json_encode(["error" => "Missing event type or session ID"]);
            return;
        }

        // Provjeri da li je to checkout.session.completed event
        if ($eventType === 'checkout.session.completed') {
            $stmt = $pdo->prepare("
                UPDATE payments 
                SET status = :status, updated_at = NOW()
                WHERE payment_id = :payment_id AND payment_type = 'card'
            ");
            $stmt->execute([
                ":status"     => "completed",
                ":payment_id" => $sessionId
            ]);

            // Automatski kupi stablo ako je plaćanje uspješno
            $stmt = $pdo->prepare("
                SELECT user_id, tree_id FROM payments 
                WHERE payment_id = :payment_id AND payment_type = 'card' AND status = 'completed'
            ");
            $stmt->execute([":payment_id" => $sessionId]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($payment) {
                // Kupi stablo
                $stmt = $pdo->prepare("
                    UPDATE trees
                    SET user_id = :user_id
                    WHERE id = :tree_id AND user_id IS NULL
                ");
                $stmt->execute([
                    'user_id' => $payment['user_id'],
                    'tree_id' => $payment['tree_id']
                ]);
            }
        }

        http_response_code(200);
        echo json_encode(["success" => true]);
    } catch (PDOException $e) {
        error_log("Stripe webhook DB error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error"]);
    }
}
