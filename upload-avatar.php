<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once 'config.php';

function uploadAvatar($pdo) {
    $user_id = $_POST['user_id'] ?? null;
    $avatar_type = $_POST['avatar_type'] ?? null; // 'default' ili 'custom'

    if (!$user_id) {
        http_response_code(400);
        echo json_encode(["error" => "user_id required"]);
        return;
    }

    // Ako je default avatar (A1-A10)
    if ($avatar_type === 'default') {
        $avatar_name = $_POST['avatar_name'] ?? null; // A1, A2, ..., A10
        
        if (!$avatar_name || !preg_match('/^A[1-9]|A10$/', $avatar_name)) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid avatar_name. Must be A1-A10"]);
            return;
        }
        
        $avatar_url = "assets/images/avatars/{$avatar_name}.png";
        
        // Ažuriraj bazu
        try {
            $stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
            $stmt->execute([$avatar_url, $user_id]);
            
            echo json_encode([
                "success" => true,
                "avatar_url" => $avatar_url,
                "avatar_type" => "default"
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => "Database error: " . $e->getMessage()]);
        }
        return;
    }
    
    // Ako je custom avatar (upload slike)
    if ($avatar_type === 'custom') {
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
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($file['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid file type. Only JPEG, PNG and GIF allowed"]);
            return;
        }
        
        // Validate file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(["error" => "File too large. Maximum size is 5MB"]);
            return;
        }
        
        // Ensure avatars directory exists
        $avatarDir = __DIR__ . "/assets/images/avatars";
        if (!file_exists($avatarDir)) {
            mkdir($avatarDir, 0777, true);
        }
        
        // Generate filename: user_{user_id}.jpg
        $ext = 'jpg'; // Uvijek koristi .jpg za konzistentnost
        $filename = "user_{$user_id}.{$ext}";
        $dest = $avatarDir . "/" . $filename;
        
        // Obriši staru sliku ako postoji
        if (file_exists($dest)) {
            unlink($dest);
        }
        
        // Konvertuj sliku u JPEG ako nije već
        $image = null;
        switch ($fileType) {
            case 'image/jpeg':
            case 'image/jpg':
                $image = imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $image = imagecreatefrompng($file['tmp_name']);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($file['tmp_name']);
                break;
        }
        
        if (!$image) {
            http_response_code(500);
            echo json_encode(["error" => "Failed to process image"]);
            return;
        }
        
        // Spremi kao JPEG (kvaliteta 85%)
        if (imagejpeg($image, $dest, 85)) {
            imagedestroy($image);
            
            $avatar_url = "assets/images/avatars/{$filename}";
            
            // Ažuriraj bazu
            try {
                $stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
                $stmt->execute([$avatar_url, $user_id]);
                
                echo json_encode([
                    "success" => true,
                    "avatar_url" => $avatar_url,
                    "avatar_type" => "custom"
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["error" => "Database error: " . $e->getMessage()]);
            }
        } else {
            imagedestroy($image);
            http_response_code(500);
            echo json_encode(["error" => "Failed to save image"]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Invalid avatar_type. Must be 'default' or 'custom'"]);
    }
}
?>

