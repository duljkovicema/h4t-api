<?php
function getMyTokens($pdo, $user_id) {
    if (!$user_id) {
        http_response_code(400);
        echo json_encode(["error" => "user_id required"]);
        return;
    }

    try {
        // broj stabala koje je user unio
        $stmtCreated = $pdo->prepare("SELECT COUNT(*) AS count FROM trees WHERE created_by = :uid");
        $stmtCreated->execute(['uid' => $user_id]);
        $createdCount = (int)$stmtCreated->fetchColumn();

        // stabla koja je user kupio
        $stmtBought = $pdo->prepare("SELECT carbon_kg FROM trees WHERE user_id = :uid");
        $stmtBought->execute(['uid' => $user_id]);
        $boughtTrees = $stmtBought->fetchAll(PDO::FETCH_ASSOC);

        // izračuni
        $tokensFromCreated = $createdCount * 10;
        $tokensFromBought = 0;
        foreach ($boughtTrees as $t) {
            $tokensFromBought += 2 * ((float)$t['carbon_kg'] ?? 0);
        }

        $totalTokens = $tokensFromCreated + $tokensFromBought;
        $value = $totalTokens * 0.056; // € vrijednost

        echo json_encode([
            "createdCount"      => $createdCount,
            "boughtCount"       => count($boughtTrees),
            "tokensFromCreated" => $tokensFromCreated,
            "tokensFromBought"  => $tokensFromBought,
            "totalTokens"       => $totalTokens,
            "value"             => $value,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "error" => "Database error",
            "details" => $e->getMessage()
        ]);
    }
}
