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
        
        // Detektiraj platformu iz User-Agent header-a
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $platform = 'unknown';
        
        if (strpos($userAgent, 'iPhone') !== false || strpos($userAgent, 'iPad') !== false) {
            $platform = 'ios';
        } elseif (strpos($userAgent, 'Android') !== false) {
            $platform = 'android';
        }
        
        // Odaberi odgovarajući URL na temelju platforme
        $url = null;
        if ($platform === 'ios' && isset($data['ios_url'])) {
            $url = $data['ios_url'];
        } elseif ($platform === 'android' && isset($data['android_url'])) {
            $url = $data['android_url'];
        } elseif (isset($data['url'])) {
            $url = $data['url']; // stari format za kompatibilnost
        }
        
        echo json_encode([
            "version" => $data['version'] ?? "1.0.0",
            "url" => $url,
            "platform" => $platform,
        ]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to read version", "details" => $e->getMessage()]);
    }
}
