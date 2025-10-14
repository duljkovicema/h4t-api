<?php
function createCardPayment($pdo, $data) {
    $user_id = $data['user_id'] ?? null;
    $amount  = $data['amount'] ?? null;
    $currency = $data['currency'] ?? 'EUR';
    $tree_id = $data['tree_id'] ?? null;

    if (!$user_id || !$amount || !$tree_id) {
        http_response_code(400);
        echo json_encode(["error" => "user_id, amount i tree_id su obavezni"]);
        return;
    }

    try {
        // Stripe API konfiguracija
        $stripe_secret_key = getenv("STRIPE_SECRET_KEY") ?: "sk_test_51H8..."; // Zamijenite s vašim key-om
        
        if (!$stripe_secret_key || $stripe_secret_key === "sk_test_51H8...") {
            http_response_code(500);
            echo json_encode([
                "error" => "Stripe API key nije konfiguriran",
                "details" => "Zamijenite 'sk_test_51H8...' s vašim pravim Stripe secret key-om u create-card-payment.php"
            ]);
            return;
        }
        
        $payload = [
            "amount" => (int)$amount, // minor units (5000 = 50.00 EUR)
            "currency" => strtolower($currency),
            "payment_method_types" => ["card"],
            "metadata" => [
                "user_id" => $user_id,
                "tree_id" => $tree_id
            ],
            "success_url" => "myapp://payment-success?session_id={CHECKOUT_SESSION_ID}",
            "cancel_url" => "myapp://payment-cancel"
        ];

        $ch = curl_init("https://api.stripe.com/v1/checkout/sessions");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $stripe_secret_key,
            "Content-Type: application/x-www-form-urlencoded"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));

        $response = curl_exec($ch);
        if ($response === false) {
            throw new Exception("cURL error: " . curl_error($ch));
        }
        curl_close($ch);

        $dataResp = json_decode($response, true);
        if (!isset($dataResp['id'])) {
            throw new Exception("Neuspješan Stripe odgovor: " . $response);
        }

        // spremi u bazu
        $stmt = $pdo->prepare(
            "INSERT INTO payments (user_id, payment_id, status, tree_id, payment_type, created_at) 
             VALUES (:uid, :pid, :status, :tree_id, 'card', NOW())"
        );
        $stmt->execute([
            "uid" => $user_id,
            "pid" => $dataResp['id'],
            "status" => "pending",
            "tree_id" => $tree_id
        ]);

        echo json_encode([
            "checkout_url" => $dataResp['url'],
            "payment_id"   => $dataResp['id']
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "error" => "Stripe error",
            "details" => $e->getMessage()
        ]);
    }
}
