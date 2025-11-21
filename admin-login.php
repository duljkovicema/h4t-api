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

        // Uspješna prijava - automatski sync zone sponsorships u pozadini
        try {
            require_once __DIR__ . '/sponsorships.php';
            
            // Pronađi sve aktivne zone_sponsorships koje treba sync-ati
            $stmt = $pdo->query("
                SELECT id 
                FROM zone_sponsorships 
                WHERE status = 'active' 
                  AND NOW() BETWEEN starts_at AND COALESCE(ends_at, '2999-12-31')
            ");
            $sponsorships = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Sync-aj svaki sponsorship u pozadini (ne blokira login ako nešto ne uspije)
            foreach ($sponsorships as $sponsorshipId) {
                try {
                    backfillZoneSponsorship($pdo, (int)$sponsorshipId);
                } catch (Exception $e) {
                    // Ignoriraj greške pojedinačnih sync-ova, samo logiraj
                    error_log("Auto-sync failed for sponsorship {$sponsorshipId}: " . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            // Ignoriraj greške sync-a, login mora proći
            error_log("Auto-sync error on admin login: " . $e->getMessage());
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
