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
        $sql = "SELECT id, password, is_admin FROM users WHERE email = :email LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(401);
            echo json_encode(["error" => "Korisnik ne postoji"]);
            return;
        }

        $dbPassword = $user['password'];
        $isAdmin = !empty($user['is_admin']);

        if (password_verify($password, $dbPassword)) {
            $response = ["user_id" => $user['id'], "is_admin" => $isAdmin];
            
            // Ako je admin, izvrši sync zone sponsorships
            if ($isAdmin) {
                $syncResults = performAdminSync($pdo);
                $response['sync_results'] = $syncResults;
            }
            
            echo json_encode($response);
            return;
        }

        if ($dbPassword === $password) {
            // automatska migracija na bcrypt
            $newHash = password_hash($password, PASSWORD_BCRYPT);
            $upd = $pdo->prepare("UPDATE users SET password = :pass WHERE id = :id");
            $upd->execute(['pass' => $newHash, 'id' => $user['id']]);

            $response = ["user_id" => $user['id'], "migrated" => true, "is_admin" => $isAdmin];
            
            // Ako je admin, izvrši sync zone sponsorships
            if ($isAdmin) {
                $syncResults = performAdminSync($pdo);
                $response['sync_results'] = $syncResults;
            }
            
            echo json_encode($response);
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

function performAdminSync(PDO $pdo): array {
    require_once __DIR__ . '/sponsorships.php';
    
    $results = [
        "status" => "completed",
        "sponsorships_processed" => 0,
        "trees_assigned" => 0,
        "details" => []
    ];
    
    try {
        // Pronađi sve aktivne zone_sponsorships
        $stmt = $pdo->query("
            SELECT id, zone_id, mode, quota_total, quota_remaining
            FROM zone_sponsorships 
            WHERE status = 'active' 
              AND NOW() BETWEEN starts_at AND COALESCE(ends_at, '2999-12-31')
        ");
        $sponsorships = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($sponsorships)) {
            $results["status"] = "no_sponsorships";
            return $results;
        }
        
        $results["sponsorships_processed"] = count($sponsorships);
        
        // Sync-aj svaki sponsorship
        foreach ($sponsorships as $sponsorship) {
            try {
                $syncResult = backfillZoneSponsorship($pdo, (int)$sponsorship['id']);
                $assigned = $syncResult['assigned'] ?? 0;
                $results["trees_assigned"] += $assigned;
                
                $results["details"][] = [
                    "zone_sponsorship_id" => (int)$sponsorship['id'],
                    "zone_id" => (int)$sponsorship['zone_id'],
                    "mode" => $sponsorship['mode'],
                    "assigned" => $assigned,
                    "remaining_quota" => $syncResult['remaining_quota'] ?? null,
                    "status" => "success"
                ];
            } catch (Exception $e) {
                $results["details"][] = [
                    "zone_sponsorship_id" => (int)$sponsorship['id'],
                    "zone_id" => (int)$sponsorship['zone_id'],
                    "mode" => $sponsorship['mode'],
                    "status" => "error",
                    "error" => $e->getMessage()
                ];
            }
        }
        
    } catch (Exception $e) {
        $results["status"] = "error";
        $results["error"] = $e->getMessage();
    }
    
    return $results;
}
