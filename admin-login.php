<?php
function adminLogin($pdo, $data) {
    $email = $data['email'] ?? null;
    $password = $data['password'] ?? null;

    if (!$email || !$password) {
        http_response_code(400);
        echo json_encode(["error" => "Email i lozinka su obavezni"]);
        return;
    }

    try {
        // Pronađi korisnika s email-om
        $stmt = $pdo->prepare("
            SELECT id, email, password, is_admin, first_name, last_name, nickname 
            FROM users 
            WHERE email = :email AND is_admin = TRUE
        ");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(401);
            echo json_encode(["error" => "Admin korisnik nije pronađen"]);
            return;
        }

        // Provjeri lozinku
        if (!password_verify($password, $user['password'])) {
            http_response_code(401);
            echo json_encode(["error" => "Neispravna lozinka"]);
            return;
        }

        // Uspješna prijava
        echo json_encode([
            "success" => true,
            "user_id" => $user['id'],
            "email" => $user['email'],
            "first_name" => $user['first_name'],
            "last_name" => $user['last_name'],
            "nickname" => $user['nickname'],
            "is_admin" => true
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "error" => "Database error",
            "details" => $e->getMessage()
        ]);
    }
}
?>
