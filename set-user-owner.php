<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['user_id']) || !isset($input['is_owner'])) {
    http_response_code(400);
    echo json_encode(["error" => "user_id and is_owner required"]);
    exit;
}

try {
    // Provjeri da li korisnik postoji
    $checkUser = $pdo->prepare("SELECT id FROM users WHERE id = :user_id");
    $checkUser->execute([':user_id' => $input['user_id']]);
    
    if ($checkUser->rowCount() == 0) {
        http_response_code(404);
        echo json_encode(["error" => "User not found"]);
        exit;
    }
    
    // Postavi is_owner status
    $sql = "UPDATE users SET is_owner = :is_owner WHERE id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':is_owner' => $input['is_owner'] ? 1 : 0,
        ':user_id' => $input['user_id']
    ]);
    
    echo json_encode([
        "success" => true,
        "message" => "User owner status updated successfully",
        "user_id" => $input['user_id'],
        "is_owner" => $input['is_owner'] ? 1 : 0
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Database error: " . $e->getMessage()
    ]);
}
?>
