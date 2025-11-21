<?php

/**
 * Sponsorship utilities shared across endpoints.
 */

function sponsorshipPointWkt(float $lat, float $lon): string {
    return sprintf('POINT(%F %F)', $lon, $lat);
}

function sponsorshipWindowCondition(): string {
    return "NOW() BETWEEN zs.starts_at AND COALESCE(zs.ends_at, '2999-12-31')";
}

function sponsorshipSelectColumns(): string {
    return "
        zs.id AS zone_sponsorship_id,
        zs.zone_id,
        zs.mode,
        zs.visible_on_map,
        zs.quota_total,
        zs.quota_remaining,
        COALESCE(zs.tree_message_override, s.default_tree_message) AS tree_message,
        COALESCE(zs.zone_message_override, s.default_zone_message) AS zone_message,
        s.id AS sponsor_id,
        s.name AS sponsor_name,
        s.logo_url,
        s.website_url
    ";
}

function sponsorshipBuildPayload(array $row, string $scope = 'tree'): ?array {
    if (empty($row['sponsor_id'])) {
        return null;
    }

    $message = $scope === 'zone' ? ($row['zone_message'] ?? null) : ($row['tree_message'] ?? null);

    return [
        "zone_sponsorship_id" => (int)$row['zone_sponsorship_id'],
        "mode" => $row['mode'],
        "message" => $message,
        "visible_on_map" => isset($row['visible_on_map']) ? (bool)$row['visible_on_map'] : null,
        "sponsor" => [
            "id" => (int)$row['sponsor_id'],
            "name" => $row['sponsor_name'],
            "logo_url" => $row['logo_url'],
            "website_url" => $row['website_url'],
        ],
    ];
}

function sponsorshipZoneIdsForPoint(PDO $pdo, string $pointWkt): array {
    $stmt = $pdo->prepare("
        SELECT id
        FROM zones
        WHERE ST_Contains(geom, ST_GeomFromText(:point, 4326))
    ");
    $stmt->execute([":point" => $pointWkt]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function sponsorshipPickPerTree(PDO $pdo, array $zoneIds): ?array {
    if (empty($zoneIds)) {
        return null;
    }

    $placeholders = implode(',', array_fill(0, count($zoneIds), '?'));
    $sql = "
        SELECT " . sponsorshipSelectColumns() . "
        FROM zone_sponsorships zs
        JOIN sponsors s ON s.id = zs.sponsor_id
        WHERE zs.mode = 'per_tree'
          AND zs.status = 'active'
          AND " . sponsorshipWindowCondition() . "
          AND zs.quota_remaining > 0
          AND zs.zone_id IN ($placeholders)
        ORDER BY zs.starts_at ASC
        LIMIT 1
        FOR UPDATE
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($zoneIds);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    $upd = $pdo->prepare("UPDATE zone_sponsorships SET quota_remaining = quota_remaining - 1 WHERE id = ?");
    $upd->execute([(int)$row['zone_sponsorship_id']]);

    return $row;
}

function sponsorshipPickSubzone(PDO $pdo, string $pointWkt): ?array {
    $sql = "
        SELECT " . sponsorshipSelectColumns() . "
        FROM zone_sponsorships zs
        JOIN sponsors s ON s.id = zs.sponsor_id
        WHERE zs.mode = 'subzone'
          AND zs.status = 'active'
          AND " . sponsorshipWindowCondition() . "
          AND ST_Contains(zs.subzone_geom, ST_GeomFromText(:point, 4326))
        ORDER BY zs.starts_at ASC
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":point" => $pointWkt]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function sponsorshipPickFullZone(PDO $pdo, array $zoneIds): ?array {
    if (empty($zoneIds)) {
        return null;
    }
    $placeholders = implode(',', array_fill(0, count($zoneIds), '?'));
    $sql = "
        SELECT " . sponsorshipSelectColumns() . "
        FROM zone_sponsorships zs
        JOIN sponsors s ON s.id = zs.sponsor_id
        WHERE zs.mode = 'full_zone'
          AND zs.status = 'active'
          AND " . sponsorshipWindowCondition() . "
          AND zs.zone_id IN ($placeholders)
        ORDER BY zs.starts_at ASC
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($zoneIds);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function assignTreeSponsorship(PDO $pdo, int $treeId, float $lat, float $lon): ?array {
    $point = sponsorshipPointWkt($lat, $lon);
    $zoneIds = sponsorshipZoneIdsForPoint($pdo, $point);

    $provider = sponsorshipPickPerTree($pdo, $zoneIds)
        ?? sponsorshipPickSubzone($pdo, $point)
        ?? sponsorshipPickFullZone($pdo, $zoneIds);

    if (!$provider) {
        return null;
    }

    $insert = $pdo->prepare("
        INSERT INTO tree_sponsorships (
            tree_id,
            zone_sponsorship_id,
            sponsor_id,
            mode,
            tree_message,
            zone_message
        ) VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            zone_sponsorship_id = VALUES(zone_sponsorship_id),
            sponsor_id = VALUES(sponsor_id),
            mode = VALUES(mode),
            tree_message = VALUES(tree_message),
            zone_message = VALUES(zone_message),
            assigned_at = CURRENT_TIMESTAMP
    ");
    $insert->execute([
        $treeId,
        (int)$provider['zone_sponsorship_id'],
        (int)$provider['sponsor_id'],
        $provider['mode'],
        $provider['tree_message'],
        $provider['zone_message'],
    ]);

    return sponsorshipBuildPayload($provider, 'tree');
}

function hydrateTreeSponsorships(array &$rows): void {
    foreach ($rows as &$row) {
        if (!empty($row['tree_sponsor_id'])) {
            $row['sponsorship'] = [
                "zone_sponsorship_id" => (int)$row['tree_zone_sponsorship_id'],
                "mode" => $row['tree_sponsorship_mode'],
                "message" => $row['tree_sponsorship_message'],
                "sponsor" => [
                    "id" => (int)$row['tree_sponsor_id'],
                    "name" => $row['tree_sponsor_name'],
                    "logo_url" => $row['tree_sponsor_logo'],
                    "website_url" => $row['tree_sponsor_website'],
                ],
            ];
        } else {
            $row['sponsorship'] = null;
        }

        unset(
            $row['tree_zone_sponsorship_id'],
            $row['tree_sponsorship_mode'],
            $row['tree_sponsorship_message'],
            $row['tree_sponsor_id'],
            $row['tree_sponsor_name'],
            $row['tree_sponsor_logo'],
            $row['tree_sponsor_website']
        );
    }
    unset($row);
}

function hydrateZoneSponsorships(PDO $pdo, array &$features): void {
    if (empty($features)) {
        return;
    }
    $zoneIds = [];
    foreach ($features as $feature) {
        if (isset($feature['properties']['id'])) {
            $zoneIds[] = (int)$feature['properties']['id'];
        }
    }
    if (empty($zoneIds)) {
        return;
    }
    $placeholders = implode(',', array_fill(0, count($zoneIds), '?'));
    $sql = "
        SELECT " . sponsorshipSelectColumns() . "
        FROM zone_sponsorships zs
        JOIN sponsors s ON s.id = zs.sponsor_id
        WHERE zs.mode = 'full_zone'
          AND zs.status = 'active'
          AND " . sponsorshipWindowCondition() . "
          AND zs.zone_id IN ($placeholders)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($zoneIds);
    $map = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $map[(int)$row['zone_id']] = sponsorshipBuildPayload($row, 'zone');
    }

    foreach ($features as &$feature) {
        $zoneId = isset($feature['properties']['id']) ? (int)$feature['properties']['id'] : null;
        $feature['properties']['sponsorship'] = $zoneId && isset($map[$zoneId]) ? $map[$zoneId] : null;
    }
    unset($feature);
}

function fetchZoneSponsorship(PDO $pdo, int $zoneSponsorshipId): ?array {
    $stmt = $pdo->prepare("
        SELECT " . sponsorshipSelectColumns() . ",
               zs.subzone_geom
        FROM zone_sponsorships zs
        JOIN sponsors s ON s.id = zs.sponsor_id
        WHERE zs.id = :id
    ");
    $stmt->execute([":id" => $zoneSponsorshipId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function assignExistingTree(PDO $pdo, int $treeId, array $sponsorshipRow): void {
    $insert = $pdo->prepare("
        INSERT INTO tree_sponsorships (
            tree_id,
            zone_sponsorship_id,
            sponsor_id,
            mode,
            tree_message,
            zone_message
        ) VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            zone_sponsorship_id = VALUES(zone_sponsorship_id),
            sponsor_id = VALUES(sponsor_id),
            mode = VALUES(mode),
            tree_message = VALUES(tree_message),
            zone_message = VALUES(zone_message),
            assigned_at = CURRENT_TIMESTAMP
    ");
    $insert->execute([
        $treeId,
        (int)$sponsorshipRow['zone_sponsorship_id'],
        (int)$sponsorshipRow['sponsor_id'],
        $sponsorshipRow['mode'],
        $sponsorshipRow['tree_message'],
        $sponsorshipRow['zone_message'],
    ]);
}

function selectUnsponsoredTreesInZone(PDO $pdo, int $zoneId, ?int $limit = null): array {
    $sql = "
        SELECT t.id
        FROM trees t
        LEFT JOIN tree_sponsorships ts ON ts.tree_id = t.id
        WHERE ts.tree_id IS NULL
          AND ST_Contains(
                (SELECT geom FROM zones WHERE id = :zone_id),
                ST_GeomFromText(CONCAT('POINT(', t.longitude, ' ', t.latitude, ')'), 4326)
          )
        ORDER BY t.created_at ASC, t.id ASC
    ";
    if ($limit !== null) {
        $sql .= " LIMIT :limit";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':zone_id', $zoneId, PDO::PARAM_INT);
    if ($limit !== null) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function selectUnsponsoredTreesInSubzone(PDO $pdo, int $zoneSponsorshipId): array {
    $sql = "
        SELECT t.id
        FROM trees t
        LEFT JOIN tree_sponsorships ts ON ts.tree_id = t.id
        WHERE ts.tree_id IS NULL
          AND ST_Contains(
                (SELECT subzone_geom FROM zone_sponsorships WHERE id = :zs_id),
                ST_GeomFromText(CONCAT('POINT(', t.longitude, ' ', t.latitude, ')'), 4326)
          )
        ORDER BY t.created_at ASC, t.id ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':zs_id', $zoneSponsorshipId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function backfillZoneSponsorship(PDO $pdo, int $zoneSponsorshipId): array {
    $sponsorship = fetchZoneSponsorship($pdo, $zoneSponsorshipId);
    if (!$sponsorship) {
        throw new InvalidArgumentException("Zone sponsorship not found");
    }

    $mode = $sponsorship['mode'];
    $assignedCount = 0;

    if ($mode === 'per_tree') {
        $quota = (int)($sponsorship['quota_remaining'] ?? 0);
        if ($quota <= 0) {
            return ["assigned" => 0, "remaining_quota" => 0];
        }
        $treeIds = selectUnsponsoredTreesInZone($pdo, (int)$sponsorship['zone_id'], $quota);
        foreach ($treeIds as $treeId) {
            assignExistingTree($pdo, (int)$treeId, $sponsorship);
            $assignedCount++;
        }
        if ($assignedCount > 0) {
            $pdo->prepare("
                UPDATE zone_sponsorships
                SET quota_remaining = GREATEST(quota_remaining - :cnt, 0)
                WHERE id = :id
            ")->execute([
                ':cnt' => $assignedCount,
                ':id' => $zoneSponsorshipId
            ]);
        }
        $remaining = max($quota - $assignedCount, 0);
        return ["assigned" => $assignedCount, "remaining_quota" => $remaining];
    }

    if ($mode === 'subzone') {
        $treeIds = selectUnsponsoredTreesInSubzone($pdo, $zoneSponsorshipId);
        foreach ($treeIds as $treeId) {
            assignExistingTree($pdo, (int)$treeId, $sponsorship);
            $assignedCount++;
        }
        return ["assigned" => $assignedCount];
    }

    if ($mode === 'full_zone') {
        $treeIds = selectUnsponsoredTreesInZone($pdo, (int)$sponsorship['zone_id']);
        foreach ($treeIds as $treeId) {
            assignExistingTree($pdo, (int)$treeId, $sponsorship);
            $assignedCount++;
        }
        return ["assigned" => $assignedCount];
    }

    throw new InvalidArgumentException("Unsupported mode: {$mode}");
}


