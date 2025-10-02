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
}  elseif (preg_match("/\/my-trees(\?.*)?$/", $request)) {
    if ($method === 'GET') {
        require_once 'my-trees.php';
        $user_id = $_GET['user_id'];
        getMyTrees($pdo, $user_id);
    } 
    else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
} elseif (preg_match("/\/user-co2(\?.*)?$/", $request)) {
    if ($method === 'GET') {
        require_once 'user-co2.php';
        $user_id = $_GET['user_id'];
        getUserCO2($pdo, $user_id);
    } 
    else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
} elseif (preg_match("/\/save-co2$/", $request)) {
    if ($method === 'POST') {
        require_once 'save-co2.php';

        // čitamo JSON body iz requesta
        $input = json_decode(file_get_contents("php://input"), true);

        saveCO2($pdo, $input);
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
} elseif (preg_match("/\/buy-tree$/", $request)) {
    if ($method === 'POST') {
        require_once 'buy-tree.php';
        $input = json_decode(file_get_contents("php://input"), true);
        buyTree($pdo, $input);
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
} elseif (preg_match("/\/register$/", $request)) {
    if ($method === 'POST') {
        require_once 'register.php';
        $input = json_decode(file_get_contents("php://input"), true);
        registerUser($pdo, $input); // <<< SAMO poziv, bez echo
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
} elseif (preg_match("/\/login$/", $request)) {
    if ($method === 'POST') {
        require_once 'login.php';
        $input = json_decode(file_get_contents("php://input"), true);
        loginUser($pdo, $input); // <<< SAMO poziv funkcije
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
} else {
    http_response_code(404);
    echo json_encode(["request_uri" => $_SERVER['REQUEST_URI']]);
}

