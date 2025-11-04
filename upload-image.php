<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once 'config.php';

function uploadImage($pdo) {
    // Ensure uploads directory exists
    $uploadDir = __DIR__ . "/uploads";
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $imageType = $_POST['image_type'] ?? null;
    $user_id = $_POST['user_id'] ?? null;

    if (!$imageType) {
        http_response_code(400);
        echo json_encode(["error" => "image_type required"]);
        return;
    }

    if (!isset($_FILES['image'])) {
        http_response_code(400);
        echo json_encode(["error" => "No image file provided"]);
        return;
    }

    $file = $_FILES['image'];
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(["error" => "File upload error: " . $file['error']]);
        return;
    }

    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
    $filename = $imageType . '_' . time() . '_' . uniqid() . '.' . $ext;
    $dest = $uploadDir . "/" . $filename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        $imagePath = "uploads/" . $filename;
        
        // Store image info in database (optional - for tracking)
        try {
            $stmt = $pdo->prepare("
                INSERT INTO uploaded_images (user_id, image_type, file_path, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$user_id, $imageType, $imagePath]);
        } catch (PDOException $e) {
            // If table doesn't exist, just continue
            error_log("Could not save image info to database: " . $e->getMessage());
        }

        echo json_encode([
            "success" => true,
            "image_path" => $imagePath,
            "image_type" => $imageType,
            "filename" => $filename
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to save image file"]);
    }
}
?>
