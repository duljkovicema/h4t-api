<?php
function loginUser($pdo, $data) {
    $email    = $data['email'] ?? null;
    $password = $data['password'] ?? null;

    if (!$email || !$password) {
        http_response_code(400);
        echo json_encode(["error" => "Email i lozinka su obavezni"]);
        return;
    }

    try {
        $sql = "SELECT id, password FROM users WHERE email = :email LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(401);
            echo json_encode(["error" => "Korisnik ne postoji"]);
            return;
        }

        $dbPassword = $user['password'];

        if (password_verify($password, $dbPassword)) {
            echo json_encode(["user_id" => $user['id']]);
            return;
        }

        if ($dbPassword === $password) {
            // automatska migracija na bcrypt
            $newHash = password_hash($password, PASSWORD_BCRYPT);
            $upd = $pdo->prepare("UPDATE users SET password = :pass WHERE id = :id");
            $upd->execute(['pass' => $newHash, 'id' => $user['id']]);

            echo json_encode(["user_id" => $user['id'], "migrated" => true]);
            return;
        }

        // ❌ Ako ništa ne valja
        http_response_code(401);
        echo json_encode(["error" => "Pogrešna lozinka"]);
        return;

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "error" => "Database error",
            "details" => $e->getMessage()
        ]);
    }
}
