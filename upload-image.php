<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Pokušaj učitati config.php i konekciju, ali ne zahtijevaj ga
$pdo = null;
try {
    $host = "localhost";
    $db_name = "agilosor_h4t";
    $username = "agilosor_izuna";
    $password = "h}K(ZC5FaJBX";
    $port = 3306;
    
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db_name;charset=utf8",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    // Ako konekcija ne uspije, nastavi bez baze - upload će raditi bez spremanja u bazu
    error_log("Database connection failed (continuing without database): " . $e->getMessage());
    $pdo = null;
}

function deleteImage($pdo, $imagePath) {
    if (!$imagePath) {
        http_response_code(400);
        echo json_encode(["error" => "image_path required"]);
        return;
    }
    
    // Osiguraj da se briše samo iz uploads direktorija (security)
    $baseDir = __DIR__ . "/uploads";
    $fullPath = realpath($baseDir . "/" . basename($imagePath));
    
    // Provjeri da li je putanja unutar uploads direktorija
    if (!$fullPath || strpos($fullPath, $baseDir) !== 0) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid image path"]);
        return;
    }
    
    // Obriši fajl ako postoji
    if (file_exists($fullPath)) {
        if (unlink($fullPath)) {
            // Obriši i iz baze ako postoji tabela
            try {
                $stmt = $pdo->prepare("DELETE FROM uploaded_images WHERE file_path = ?");
                $stmt->execute([$imagePath]);
            } catch (PDOException $e) {
                // Ako tabela ne postoji, samo nastavi
                error_log("Could not delete image info from database: " . $e->getMessage());
            }
            
            echo json_encode([
                "success" => true,
                "message" => "Image deleted successfully"
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to delete image file"]);
        }
    } else {
        // Fajl već ne postoji, ali vrati success
        echo json_encode([
            "success" => true,
            "message" => "Image already deleted or not found"
        ]);
    }
}

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
        if ($pdo) {
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

// Glavna logika
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            uploadImage($pdo);
            break;
            
        case 'DELETE':
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            $imagePath = $data['image_path'] ?? null;
            deleteImage($pdo, $imagePath);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(["error" => "Method not allowed"]);
            break;
    }
} catch (Exception $e) {
    error_log("Upload image API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Internal server error: " . $e->getMessage()]);
}
?>
