<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

function getZones($pdo, $input = [])
{
    try {
        // Optional bbox filter
        $useBbox = isset($input['minLon'], $input['minLat'], $input['maxLon'], $input['maxLat']);

        if ($useBbox) {
            $minLon = (float)$input['minLon'];
            $minLat = (float)$input['minLat'];
            $maxLon = (float)$input['maxLon'];
            $maxLat = (float)$input['maxLat'];

            $sql = "
                SELECT
                    id, name, type, owner, partner, jurisdiction, source, external_id,
                    ST_AsGeoJSON(geom) AS geometry
                FROM zones
                WHERE MBRIntersects(
                    geom,
                    ST_GeomFromText(CONCAT('POLYGON((', :minLon, ' ', :minLat, ',', :maxLon, ' ', :minLat, ',', :maxLon, ' ', :maxLat, ',', :minLon, ' ', :maxLat, ',', :minLon, ' ', :minLat, '))'), 4326)
                )
            ";
            $params = [
                ':minLon' => $minLon,
                ':minLat' => $minLat,
                ':maxLon' => $maxLon,
                ':maxLat' => $maxLat,
            ];
        } else {
            $sql = "
                SELECT
                    id, name, type, owner, partner, jurisdiction, source, external_id,
                    ST_AsGeoJSON(geom) AS geometry
                FROM zones
                ORDER BY name ASC
            ";
            $params = [];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Build FeatureCollection
        $features = array_map(function ($r) {
            return [
                'type' => 'Feature',
                'properties' => [
                    'id' => (int)$r['id'],
                    'name' => $r['name'],
                    'type' => $r['type'],
                    'owner' => $r['owner'],
                    'partner' => (int)$r['partner'],
                    'jurisdiction' => $r['jurisdiction'],
                    'source' => $r['source'],
                    'external_id' => $r['external_id'],
                ],
                'geometry' => json_decode($r['geometry'], true),
            ];
        }, $rows);

        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode([
            'type' => 'FeatureCollection',
            'features' => $features,
        ], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        error_log("DB error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error"]);
    }
}

function addZone($pdo, $input)
{
    $requiredFields = ['name', 'type', 'owner', 'partner', 'geometry_wkt'];

    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(["error" => "Field '{$field}' required"]);
            return;
        }
    }

    $allowedTypes = ['national_park', 'nature_park', 'private', 'municipal', 'state'];
    if (!in_array($input['type'], $allowedTypes, true)) {
        http_response_code(400);
        echo json_encode(["error" => "Nevažeći tip zone"]);
        return;
    }

    try {
        $sql = "
            INSERT INTO zones (name, type, owner, partner, jurisdiction, source, external_id, geom)
            VALUES (:name, :type, :owner, :partner, :jurisdiction, :source, :external_id, ST_GeomFromText(:geometry_wkt, 4326))
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name' => $input['name'],
            ':type' => $input['type'],
            ':owner' => $input['owner'],
            ':partner' => (int)$input['partner'],
            ':jurisdiction' => $input['jurisdiction'] ?? null,
            ':source' => $input['source'] ?? null,
            ':external_id' => $input['external_id'] ?? null,
            ':geometry_wkt' => $input['geometry_wkt'],
        ]);

        $zoneId = $pdo->lastInsertId();
        echo json_encode(["success" => true, "id" => $zoneId]);
    } catch (PDOException $e) {
        error_log("DB error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error"]);
    }
}

function updateZonePartner($pdo, $input)
{
    $zone_id = $input['zone_id'] ?? null;
    $partner = $input['partner'] ?? null;

    if (empty($zone_id) || !isset($partner)) {
        http_response_code(400);
        echo json_encode(["error" => "zone_id and partner required"]);
        return;
    }

    if (!in_array($partner, [0, 1], true)) {
        http_response_code(400);
        echo json_encode(["error" => "Partner mora biti 0 ili 1"]);
        return;
    }

    try {
        $sql = "UPDATE zones SET partner = :partner WHERE id = :zone_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":partner" => (int)$partner,
            ":zone_id" => (int)$zone_id
        ]);

        echo json_encode(["success" => true]);
    } catch (PDOException $e) {
        error_log("DB error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error"]);
    }
}
