<?php

function uploadTree($pdo) {
    // Ensure uploads directory exists
    $uploadDir = __DIR__ . "/uploads";
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $lat = $_POST['lat'] ?? null;
    $lon = $_POST['lon'] ?? null;
    $altitude = $_POST['altitude'] ?? null;
    $user_id = $_POST['user_id'] ?? null;
    $height_m = $_POST['height_m'] ?? null;
    $diameter_cm = $_POST['diameter_cm'] ?? null;
    $species = $_POST['species'] ?? null;
    $co2_kg_estimate = $_POST['co2_kg_estimate'] ?? null;
    $no2_g_per_year = $_POST['no2_g_per_year'] ?? null;
    $so2_g_per_year = $_POST['so2_g_per_year'] ?? null;
    $o3_g_per_year = $_POST['o3_g_per_year'] ?? null;
    $sensor_data = $_POST['sensor_data'] ?? null;
    $analysis_confidence = $_POST['analysis_confidence'] ?? null;

    if ($lat === null || $lon === null) {
        http_response_code(400);
        echo json_encode(["error" => "lat/lon required"]);
        return;
    }

    $photoPaths = [];
    if (!empty($_FILES['photo'])) {
        foreach ($_FILES['photo']['tmp_name'] as $index => $tmpName) {
            $ext = pathinfo($_FILES['photo']['name'][$index], PATHINFO_EXTENSION);
            $filename = time() . "-" . uniqid() . "." . ($ext ?: "jpg");
            $dest = $uploadDir . "/" . $filename;
            if (move_uploaded_file($tmpName, $dest)) {
                $photoPaths[] = "uploads/" . $filename;
            }
        }
    }

    $ts = date("Y-m-d H:i:s");
    $localTs = date("Y-m-d H:i:s"); // local time

    try {
        // Pokušaj s novim kolonama
        $stmt = $pdo->prepare("
            INSERT INTO trees (
                latitude, longitude, altitude, created_at, created_at_local, image_path,
                user_id, height_m, diameter_cm, species, carbon_kg,
                no2_g_per_year, so2_g_per_year, o3_g_per_year, created_by,
                sensor_data, analysis_confidence
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            floatval($lat),
            floatval($lon),
            $altitude ? floatval($altitude) : null,
            $ts,
            $localTs,
            json_encode($photoPaths),
            null,
            $height_m ? floatval($height_m) : null,
            $diameter_cm ? floatval($diameter_cm) : null,
            $species ?: null,
            $co2_kg_estimate ? floatval($co2_kg_estimate) : null,
            $no2_g_per_year ? floatval($no2_g_per_year) : null,
            $so2_g_per_year ? floatval($so2_g_per_year) : null,
            $o3_g_per_year ? floatval($o3_g_per_year) : null,
            $user_id ? intval($user_id) : null,
            $sensor_data ?: null,
            $analysis_confidence ? floatval($analysis_confidence) : null
        ]);

        echo json_encode(["id" => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        // Ako ne uspije s novim kolonama, pokušaj s starim
        try {
            $stmt = $pdo->prepare("
                INSERT INTO trees (
                    latitude, longitude, created_at, created_at_local, image_path,
                    user_id, height_m, diameter_cm, species, carbon_kg,
                    no2_g_per_year, so2_g_per_year, o3_g_per_year, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                floatval($lat),
                floatval($lon),
                $ts,
                $localTs,
                json_encode($photoPaths),
                null,
                $height_m ? floatval($height_m) : null,
                $diameter_cm ? floatval($diameter_cm) : null,
                $species ?: null,
                $co2_kg_estimate ? floatval($co2_kg_estimate) : null,
                $no2_g_per_year ? floatval($no2_g_per_year) : null,
                $so2_g_per_year ? floatval($so2_g_per_year) : null,
                $o3_g_per_year ? floatval($o3_g_per_year) : null,
                $user_id ? intval($user_id) : null
            ]);

            echo json_encode(["id" => $pdo->lastInsertId()]);
        } catch (PDOException $e2) {
            http_response_code(500);
            echo json_encode(["error" => "Database error: " . $e2->getMessage()]);
        }
    }
}
