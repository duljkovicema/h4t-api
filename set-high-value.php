<?php
header('Content-Type: application/json');

try {
    require_once 'config.php';
} catch (Exception $e) {
    error_log("Config error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Configuration error: " . $e->getMessage()
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['tree_id']) || !isset($input['high_value_cost']) || !isset($input['high_value_name'])) {
    http_response_code(400);
    echo json_encode(["error" => "tree_id, high_value_cost, and high_value_name are required"]);
    exit;
}

try {
    // Provjeri da li stablo postoji
    $checkTree = $pdo->prepare("SELECT id FROM trees WHERE id = :tree_id");
    $checkTree->execute([':tree_id' => $input['tree_id']]);
    
    if ($checkTree->rowCount() == 0) {
        http_response_code(404);
        echo json_encode(["error" => "Tree not found"]);
        exit;
    }
    
    // Odredi da li se stablo postavlja ili uklanja kao high value
    $isRemoving = ($input['high_value_cost'] == 0 && $input['high_value_name'] == "");
    
    if ($isRemoving) {
        // Ukloni high value status
        $sql = "UPDATE trees SET 
                high_value = 0, 
                high_value_cost = 0, 
                high_value_name = '' 
                WHERE id = :tree_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':tree_id' => $input['tree_id']]);
        
        echo json_encode([
            "success" => true,
            "message" => "Tree removed from high value successfully",
            "tree_id" => $input['tree_id']
        ]);
    } else {
        // Postavi stablo kao high value s vrijednošću i imenom
        $sql = "UPDATE trees SET 
                high_value = 1, 
                high_value_cost = :high_value_cost, 
                high_value_name = :high_value_name 
                WHERE id = :tree_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':high_value_cost' => $input['high_value_cost'],
            ':high_value_name' => $input['high_value_name'],
            ':tree_id' => $input['tree_id']
        ]);
        
        echo json_encode([
            "success" => true,
            "message" => "Tree set as high value successfully",
            "tree_id" => $input['tree_id'],
            "high_value_cost" => $input['high_value_cost'],
            "high_value_name" => $input['high_value_name']
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Database error: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "General error: " . $e->getMessage()
    ]);
}
?>