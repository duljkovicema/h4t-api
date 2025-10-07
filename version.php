<?php

function getLatestVersion() {
    header("Content-Type: application/json");
    try {
        $path = __DIR__ . DIRECTORY_SEPARATOR . 'latest-version.json';
        if (!file_exists($path)) {
            echo json_encode(["version" => "1.0.0", "url" => null]);
            return;
        }
        $json = file_get_contents($path);
        $data = json_decode($json, true);
        if (!is_array($data)) {
            echo json_encode(["version" => "1.0.0", "url" => null]);
            return;
        }
        echo json_encode([
            "version" => $data['version'] ?? "1.0.0",
            "url" => $data['url'] ?? null,
        ]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to read version", "details" => $e->getMessage()]);
    }
}
