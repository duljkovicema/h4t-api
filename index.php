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
} elseif (preg_match("/\/trees(\?.*)?$/", $request)) {
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
} elseif (preg_match("/\/my-tokens(\?.*)?$/", $request)) {
    if ($method === 'GET') {
        require_once 'my-tokens.php';
        $user_id = $_GET['user_id'];
        getMyTokens($pdo, $user_id);
    } 
    else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
} elseif (preg_match("/\/create-payment$/", $request)) {
    if ($method === 'POST') {
        require_once 'create-payment.php';
        $input = json_decode(file_get_contents("php://input"), true);
        createPayment($pdo, $input);
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
} elseif (preg_match("/\/create-card-payment$/", $request)) {
    if ($method === 'POST') {
        require_once 'create-card-payment.php';
        $input = json_decode(file_get_contents("php://input"), true);
        createCardPayment($pdo, $input);
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
} elseif (preg_match("/\/trees-by-owner(\?.*)?$/", $request)) {
    if ($method === 'GET') {
        require_once 'trees-by-owner.php';
        $owner_id = $_GET['owner_id'] ?? null;
        getTreesByOwner($pdo, $owner_id);
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
} elseif (preg_match("/\/revolut-webhook$/", $request)) {
    if ($method === 'POST') {
        require_once 'revolut-webhook.php';
        handleRevolutWebhook($pdo);
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
} elseif (preg_match("/\/stripe-webhook$/", $request)) {
    if ($method === 'POST') {
        require_once 'stripe-webhook.php';
        handleStripeWebhook($pdo);
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
} elseif (preg_match("/\/check-payment-status(\?.*)?$/", $request)) {
    if ($method === 'GET') {
        require_once 'check-payment-status.php';
        $id = $_GET['id'] ?? null;
        checkPaymentStatus($pdo, $id);
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
} elseif (preg_match("/\/user-profile(\?.*)?$/", $request)) {
    if ($method === 'GET') {
        require_once 'user-profile.php';
        $user_id = $_GET['user_id'] ?? null;
        getUserProfile($pdo, $user_id);
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
} elseif (preg_match("/\/update-profile$/", $request)) {
    if ($method === 'POST') {
        require_once 'update-profile.php';
        $input = json_decode(file_get_contents("php://input"), true);
        updateUserProfile($pdo, $input);
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
} elseif (preg_match("/\/like-tree$/", $request)) {
    if ($method === 'POST') {
        require_once 'like-tree.php';
        $input = json_decode(file_get_contents("php://input"), true);
        likeTree($pdo, $input);
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
} elseif (preg_match("/\/unlike-tree$/", $request)) {
    if ($method === 'POST') {
        require_once 'unlike-tree.php';
        $input = json_decode(file_get_contents("php://input"), true);
        unlikeTree($pdo, $input);
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
} elseif (preg_match("/\/tree-likes(\?.*)?$/", $request)) {
    if ($method === 'GET') {
        require_once 'tree-likes.php';
        $tree_id = $_GET['tree_id'] ?? null;
        $user_id = $_GET['user_id'] ?? null;
        getTreeLikes($pdo, $tree_id, $user_id);
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
} elseif (preg_match("/\/version(\?.*)?$/", $request)) {
    if ($method === 'GET') {
        require_once 'version.php';
        getLatestVersion();
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
} elseif (preg_match("/\/test-connection$/", $request)) {
    if ($method === 'GET') {
        require_once 'test-connection.php';
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
} else {
    http_response_code(404);
    echo json_encode(["request_uri" => $_SERVER['REQUEST_URI']]);
}

