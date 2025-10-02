<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once 'config.php';

// Simple router
$request = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

if (preg_match("/\/upload-tree$/", $request)) {
    if ($method === 'POST') {
        require_once 'upload-tree.php';
        uploadTree($pdo);
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
} elseif (preg_match("/\/trees$/", $request)) {
    if ($method === 'GET') {
        require_once 'trees.php';
        getTrees($pdo);
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
} else {
    http_response_code(404);
    echo json_encode(["request_uri" => $_SERVER['REQUEST_URI']]);
}

