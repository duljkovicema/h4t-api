<?php

function getZones($pdo) {
    try {
        $sql = "
            SELECT 
                id,
                name,
                type,
                owner,
                partner,
                jurisdiction,
                source,
                external_id,
                ST_AsGeoJSON(geom) as geometry,
                created_at,
                updated_at
            FROM zone
            ORDER BY name ASC
        ";
        
        $stmt = $pdo->query($sql);
        $zones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Konvertiraj u GeoJSON format
        $features = [];
        foreach ($zones as $zone) {
            $geometry = json_decode($zone['geometry'], true);
            if ($geometry) {
                $features[] = [
                    'type' => 'Feature',
                    'properties' => [
                        'id' => $zone['id'],
                        'name' => $zone['name'],
                        'type' => $zone['type'],
                        'owner' => $zone['owner'],
                        'partner' => $zone['partner'],
                        'jurisdiction' => $zone['jurisdiction'],
                        'source' => $zone['source'],
                        'external_id' => $zone['external_id'],
                        'created_at' => $zone['created_at'],
                        'updated_at' => $zone['updated_at']
                    ],
                    'geometry' => $geometry
                ];
            }
        }
        
        $geojson = [
            'type' => 'FeatureCollection',
            'features' => $features
        ];
        
        http_response_code(200);
        echo json_encode($geojson);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }
}