<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

function toggleFavorite($pdo, $input) {
    $user_id = $input['user_id'] ?? null;
    $tree_id = $input['tree_id'] ?? null;
    $action = $input['action'] ?? 'toggle'; // 'add', 'remove', 'toggle'
    
    error_log("toggleFavorite called with user_id: $user_id, tree_id: $tree_id, action: $action");
    
    if (empty($user_id) || empty($tree_id)) {
        http_response_code(400);
        echo json_encode(["error" => "user_id and tree_id required"]);
        return;
    }
    
    try {
        // Try to create table if it doesn't exist (ignore error if it already exists)
        $createTable = "
            CREATE TABLE IF NOT EXISTS user_favorites (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                tree_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_tree (user_id, tree_id),
                INDEX idx_user_id (user_id),
                INDEX idx_tree_id (tree_id),
                INDEX idx_created_at (created_at)
            )
        ";
        $pdo->exec($createTable);
        // Check if favorite already exists
        $checkSql = "SELECT id FROM user_favorites WHERE user_id = :user_id AND tree_id = :tree_id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([
            ':user_id' => (int)$user_id,
            ':tree_id' => (int)$tree_id
        ]);
        $existing = $checkStmt->fetch();
        
        $isFavorite = $existing !== false;
        
        if ($action === 'add' || ($action === 'toggle' && !$isFavorite)) {
            // Add to favorites
            if (!$isFavorite) {
                $insertSql = "INSERT INTO user_favorites (user_id, tree_id, created_at) VALUES (:user_id, :tree_id, NOW())";
                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->execute([
                    ':user_id' => (int)$user_id,
                    ':tree_id' => (int)$tree_id
                ]);
            }
            $isFavorite = true;
        } elseif ($action === 'remove' || ($action === 'toggle' && $isFavorite)) {
            // Remove from favorites
            if ($isFavorite) {
                $deleteSql = "DELETE FROM user_favorites WHERE user_id = :user_id AND tree_id = :tree_id";
                $deleteStmt = $pdo->prepare($deleteSql);
                $deleteStmt->execute([
                    ':user_id' => (int)$user_id,
                    ':tree_id' => (int)$tree_id
                ]);
            }
            $isFavorite = false;
        }
        
        error_log("toggleFavorite success: is_favorite = " . ($isFavorite ? 'true' : 'false'));
        echo json_encode([
            "success" => true,
            "is_favorite" => $isFavorite,
            "action" => $isFavorite ? "added" : "removed"
        ]);
        
    } catch (PDOException $e) {
        error_log("toggleFavorite DB error: " . $e->getMessage());
        error_log("toggleFavorite DB error trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }
}

function getUserFavorites($pdo, $input) {
    $user_id = $input['user_id'] ?? null;
    
    if (empty($user_id)) {
        http_response_code(400);
        echo json_encode(["error" => "user_id required"]);
        return;
    }
    
    try {
        // Try to create table if it doesn't exist (ignore error if it already exists)
        $createTable = "
            CREATE TABLE IF NOT EXISTS user_favorites (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                tree_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_tree (user_id, tree_id),
                INDEX idx_user_id (user_id),
                INDEX idx_tree_id (tree_id),
                INDEX idx_created_at (created_at)
            )
        ";
        $pdo->exec($createTable);
        $sql = "
            SELECT t.*, uf.created_at as favorited_at
            FROM trees t
            INNER JOIN user_favorites uf ON t.id = uf.tree_id
            WHERE uf.user_id = :user_id
            ORDER BY uf.created_at DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => (int)$user_id]);
        $trees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "success" => true,
            "trees" => $trees
        ]);
        
    } catch (PDOException $e) {
        error_log("DB error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error"]);
    }
}

// Main logic
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    
    if ($method === 'POST') {
        toggleFavorite($pdo, $input);
    } elseif ($method === 'GET') {
        getUserFavorites($pdo, $input);
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
    
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
}
?>
