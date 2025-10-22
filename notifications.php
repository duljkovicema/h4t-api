<?php
// notifications.php - Backend logika za notifikacije
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Funkcija za sigurno dohvaćanje JSON podataka
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true);
}

// Funkcija za provjeru notifikacija za korisnika
function checkUserNotifications($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("
            SELECT n.id, n.name, n.kategorija, n.body, un.seen_at
            FROM notifications n
            LEFT JOIN user_notif un ON n.id = un.notification_id AND un.user_id = ?
            WHERE un.seen_at IS NULL OR un.seen_at IS NOT NULL
            ORDER BY n.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error checking notifications: " . $e->getMessage());
        return [];
    }
}

// Funkcija za označavanje notifikacije kao viđene
function markNotificationAsSeen($pdo, $userId, $notificationId) {
    try {
        // Provjeri postoji li već zapis
        $checkStmt = $pdo->prepare("
            SELECT id FROM user_notif 
            WHERE user_id = ? AND notification_id = ?
        ");
        $checkStmt->execute([$userId, $notificationId]);
        $existing = $checkStmt->fetch();
        
        if ($existing) {
            // Ažuriraj postojeći zapis
            $stmt = $pdo->prepare("
                UPDATE user_notif 
                SET seen_at = CURRENT_TIMESTAMP 
                WHERE user_id = ? AND notification_id = ?
            ");
            $stmt->execute([$userId, $notificationId]);
        } else {
            // Kreiraj novi zapis
            $stmt = $pdo->prepare("
                INSERT INTO user_notif (user_id, notification_id, seen_at) 
                VALUES (?, ?, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([$userId, $notificationId]);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Error marking notification as seen: " . $e->getMessage());
        return false;
    }
}

// Funkcija za dohvaćanje neviđenih notifikacija po kategoriji
function getUnseenNotificationsByCategory($pdo, $userId, $category) {
    try {
        $stmt = $pdo->prepare("
            SELECT n.id, n.name, n.kategorija, n.body
            FROM notifications n
            LEFT JOIN user_notif un ON n.id = un.notification_id AND un.user_id = ?
            WHERE n.kategorija = ? AND (un.seen_at IS NULL OR un.id IS NULL)
            ORDER BY n.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$userId, $category]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting unseen notifications: " . $e->getMessage());
        return null;
    }
}

// Funkcija za kreiranje nove notifikacije (admin funkcija)
function createNotification($pdo, $name, $category, $body) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (name, kategorija, body) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$name, $category, $body]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

// Glavna logika
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'check':
                    $userId = $_GET['user_id'] ?? null;
                    if (!$userId) {
                        http_response_code(400);
                        echo json_encode(['error' => 'user_id je obavezan']);
                        exit;
                    }
                    
                    $notifications = checkUserNotifications($pdo, $userId);
                    echo json_encode([
                        'success' => true,
                        'notifications' => $notifications
                    ]);
                    break;
                    
                case 'unseen':
                    $userId = $_GET['user_id'] ?? null;
                    $category = $_GET['category'] ?? null;
                    
                    if (!$userId || !$category) {
                        http_response_code(400);
                        echo json_encode(['error' => 'user_id i category su obavezni']);
                        exit;
                    }
                    
                    $notification = getUnseenNotificationsByCategory($pdo, $userId, $category);
                    echo json_encode([
                        'success' => true,
                        'notification' => $notification,
                        'hasUnseen' => $notification !== null
                    ]);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Nepoznata akcija']);
                    break;
            }
            break;
            
        case 'POST':
            switch ($action) {
                case 'mark_seen':
                    $data = getJsonInput();
                    $userId = $data['user_id'] ?? null;
                    $notificationId = $data['notification_id'] ?? null;
                    
                    if (!$userId || !$notificationId) {
                        http_response_code(400);
                        echo json_encode(['error' => 'user_id i notification_id su obavezni']);
                        exit;
                    }
                    
                    $success = markNotificationAsSeen($pdo, $userId, $notificationId);
                    echo json_encode([
                        'success' => $success,
                        'message' => $success ? 'Notifikacija označena kao viđena' : 'Greška pri označavanju'
                    ]);
                    break;
                    
                case 'create':
                    $data = getJsonInput();
                    $name = $data['name'] ?? null;
                    $category = $data['kategorija'] ?? null;
                    $body = $data['body'] ?? null;
                    
                    if (!$name || !$category || !$body) {
                        http_response_code(400);
                        echo json_encode(['error' => 'name, kategorija i body su obavezni']);
                        exit;
                    }
                    
                    $notificationId = createNotification($pdo, $name, $category, $body);
                    if ($notificationId) {
                        echo json_encode([
                            'success' => true,
                            'notification_id' => $notificationId,
                            'message' => 'Notifikacija kreirana'
                        ]);
                    } else {
                        http_response_code(500);
                        echo json_encode(['error' => 'Greška pri kreiranju notifikacije']);
                    }
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Nepoznata akcija']);
                    break;
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Metoda nije podržana']);
            break;
    }
} catch (Exception $e) {
    error_log("Notifications API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Interna greška servera']);
}
?>
