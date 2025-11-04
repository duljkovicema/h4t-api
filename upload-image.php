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

// Pokušaj kreirati konekciju direktno, ali ne zaustavljaj ako ne uspije
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

function deleteUserImagesWithoutTree($pdo, $userId) {
    if (!$userId) {
        http_response_code(400);
        echo json_encode(["error" => "user_id required"]);
        return;
    }
    
    try {
        // Provjeri da li tabela postoji
        $checkTable = $pdo->query("SHOW TABLES LIKE 'uploaded_images'");
        if ($checkTable->rowCount() == 0) {
            // Tabela ne postoji, samo vrati success
            echo json_encode([
                "success" => true,
                "message" => "Table uploaded_images does not exist",
                "deleted_count" => 0
            ]);
            return;
        }
        
        // Provjeri da li tabela ima tree_id kolonu
        $checkColumn = $pdo->query("SHOW COLUMNS FROM uploaded_images LIKE 'tree_id'");
        $hasTreeIdColumn = $checkColumn->rowCount() > 0;
        
        // Dohvati sve slike za user_id koje nemaju tree_id (ili sve ako nema tree_id kolone)
        if ($hasTreeIdColumn) {
            $stmt = $pdo->prepare("
                SELECT file_path FROM uploaded_images 
                WHERE user_id = ? AND (tree_id IS NULL OR tree_id = 0)
            ");
        } else {
            // Ako nema tree_id kolone, obriši sve slike za user_id
            $stmt = $pdo->prepare("
                SELECT file_path FROM uploaded_images 
                WHERE user_id = ?
            ");
        }
        $stmt->execute([$userId]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $baseDir = __DIR__ . "/uploads";
        $deletedCount = 0;
        
        // Obriši fajlove
        foreach ($images as $image) {
            $filePath = $image['file_path'];
            $fullPath = realpath($baseDir . "/" . basename($filePath));
            
            // Provjeri da li je putanja unutar uploads direktorija (security)
            if ($fullPath && strpos($fullPath, $baseDir) === 0 && file_exists($fullPath)) {
                if (unlink($fullPath)) {
                    $deletedCount++;
                }
            }
        }
        
        // Obriši iz baze
        if ($hasTreeIdColumn) {
            $stmt = $pdo->prepare("
                DELETE FROM uploaded_images 
                WHERE user_id = ? AND (tree_id IS NULL OR tree_id = 0)
            ");
        } else {
            $stmt = $pdo->prepare("
                DELETE FROM uploaded_images 
                WHERE user_id = ?
            ");
        }
        $stmt->execute([$userId]);
        
        echo json_encode([
            "success" => true,
            "message" => "Deleted images for user without tree_id",
            "deleted_count" => $deletedCount
        ]);
    } catch (PDOException $e) {
        error_log("Error deleting user images: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Failed to delete images: " . $e->getMessage()]);
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

// Glavna logika
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            if (function_exists('uploadImage')) {
                uploadImage($pdo);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "uploadImage function not found"]);
            }
            break;
            
        case 'DELETE':
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            $userId = $data['user_id'] ?? null;
            
            if ($userId) {
                // Brisanje svih slika za user_id bez tree_id
                deleteUserImagesWithoutTree($pdo, $userId);
            } else {
                http_response_code(400);
                echo json_encode(["error" => "user_id required for DELETE"]);
            }
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
