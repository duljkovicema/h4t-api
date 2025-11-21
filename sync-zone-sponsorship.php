<?php

require_once __DIR__ . '/sponsorships.php';

function syncZoneSponsorship($pdo, array $input): void {
    $zoneSponsorshipId = $input['zone_sponsorship_id'] ?? null;
    if (!$zoneSponsorshipId) {
        http_response_code(400);
        echo json_encode(["error" => "zone_sponsorship_id required"]);
        return;
    }

    try {
        $pdo->beginTransaction();
        $result = backfillZoneSponsorship($pdo, (int)$zoneSponsorshipId);
        $pdo->commit();

        echo json_encode([
            "success" => true,
            "zone_sponsorship_id" => (int)$zoneSponsorshipId,
            "result" => $result
        ]);
    } catch (InvalidArgumentException $e) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(["error" => $e->getMessage()]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "Failed to sync sponsorship", "details" => $e->getMessage()]);
    }
}

