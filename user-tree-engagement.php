<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

function getUserTreeEngagement($pdo, $user_id) {
    if (!$user_id) {
        http_response_code(400);
        echo json_encode(["error" => "user_id required"]);
        return;
    }

    try {
        // Dohvati sva stabla korisnika (kako oni koja je kreirao, tako i ona koja je kupio)
        $treesSql = "
            SELECT id FROM trees 
            WHERE created_by = :user_id OR user_id = :user_id
        ";
        $treesStmt = $pdo->prepare($treesSql);
        $treesStmt->execute([':user_id' => (int)$user_id]);
        $userTreeIds = $treesStmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($userTreeIds)) {
            echo json_encode([
                "likes_received" => 0,
                "favorites_received" => 0
            ]);
            return;
        }
        
        // Kreiraj placeholdere za IN query
        $placeholders = implode(',', array_fill(0, count($userTreeIds), '?'));
        
        // Broj lajkova koje su drugi dali korisnikovim stablima
        $likesSql = "SELECT COUNT(*) as count FROM tree_likes WHERE tree_id IN ($placeholders)";
        $likesStmt = $pdo->prepare($likesSql);
        $likesStmt->execute($userTreeIds);
        $likesResult = $likesStmt->fetch(PDO::FETCH_ASSOC);
        $likesCount = (int)($likesResult['count'] ?? 0);
        
        // Broj favorita koje su drugi dali korisnikovim stablima
        // Provjeri postoji li tablica user_favorites
        $favoritesCount = 0;
        try {
            $favoritesSql = "SELECT COUNT(*) as count FROM user_favorites WHERE tree_id IN ($placeholders)";
            $favoritesStmt = $pdo->prepare($favoritesSql);
            $favoritesStmt->execute($userTreeIds);
            $favoritesResult = $favoritesStmt->fetch(PDO::FETCH_ASSOC);
            $favoritesCount = (int)($favoritesResult['count'] ?? 0);
        } catch (PDOException $e) {
            // Tablica možda ne postoji, vrati 0
            error_log("user_favorites table might not exist: " . $e->getMessage());
        }
        
        echo json_encode([
            "likes_received" => $likesCount,
            "favorites_received" => $favoritesCount
        ]);
    } catch (PDOException $e) {
        error_log("DB error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error"]);
    }
}

