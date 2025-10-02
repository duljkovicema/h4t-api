<?php
function createPayment($pdo, $data) {
    $user_id = $data['user_id'] ?? null;
    $amount  = $data['amount'] ?? null;
    $currency = $data['currency'] ?? 'EUR';

    if (!$user_id || !$amount) {
        http_response_code(400);
        echo json_encode(["error" => "user_id i amount su obavezni"]);
        return;
    }

    try {
        $payload = [
            "amount" => (int)$amount, // minor units (5000 = 50.00 EUR)
            "currency" => $currency,
            "capture_mode" => "AUTOMATIC",
            "merchant_order_ext_ref" => $user_id . "-" . time(),
            "return_url" => "myapp://payment-success",
            "cancel_url" => "myapp://payment-cancel"
        ];

        $ch = curl_init("https://b2b.revolut.com/api/1.0/order");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . getenv("REVOLUT_API_KEY"),
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        if ($response === false) {
            throw new Exception("cURL error: " . curl_error($ch));
        }
        curl_close($ch);

        $dataResp = json_decode($response, true);
        if (!isset($dataResp['id'])) {
            throw new Exception("Neuspješan Revolut odgovor: " . $response);
        }

        // spremi u bazu
        $stmt = $pdo->prepare(
            "INSERT INTO payments (user_id, payment_id, status, created_at) 
             VALUES (:uid, :pid, :status, NOW())"
        );
        $stmt->execute([
            "uid" => $user_id,
            "pid" => $dataResp['id'],
            "status" => "pending"
        ]);

        echo json_encode([
            "checkout_url" => $dataResp['checkout_url'] ?? null,
            "payment_id"   => $dataResp['id']
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "error" => "Revolut error",
            "details" => $e->getMessage()
        ]);
    }
}
