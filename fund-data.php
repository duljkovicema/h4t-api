<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    
    $user_id = $_GET['user_id'] ?? null;
    
    if (!$user_id) {
        http_response_code(400);
        echo json_encode(['error' => 'user_id je obavezan']);
        exit;
    }
    
    // Dohvati sve kupljena stabla za korisnika
    $stmt = $pdo->prepare("
        SELECT 
            t.id,
            t.species,
            t.created_at,
            t.latitude,
            t.longitude,
            t.carbon_kg,
            t.diameter_cm,
            t.height_m,
            t.no2_g_per_year,
            t.o3_g_per_year,
            t.so2_g_per_year,
            t.created_by,
            u.first_name,
            u.last_name,
            u.company,
            u.nickname
        FROM trees t
        LEFT JOIN users u ON t.created_by = u.id
        WHERE t.created_by = ?
        ORDER BY t.created_at DESC
    ");
    
    $stmt->execute([$user_id]);
    $trees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Izračunaj fond (20% od kupljenih stabala)
    $totalFund = 0;
    $monthlyData = [];
    $investments = [];
    
    // Grupiraj po mjesecima
    $monthlyStats = [];
    
    foreach ($trees as $tree) {
        $treeValue = 50; // Osnovna vrijednost stabla
        $fundContribution = $treeValue * 0.20; // 20% u fond
        $totalFund += $fundContribution;
        
        $date = new DateTime($tree['created_at']);
        $monthKey = $date->format('Y-m');
        
        if (!isset($monthlyStats[$monthKey])) {
            $monthlyStats[$monthKey] = [
                'income' => 0,
                'expense' => 0,
                'month' => $date->format('M Y')
            ];
        }
        
        $monthlyStats[$monthKey]['income'] += $fundContribution;
        $monthlyStats[$monthKey]['expense'] += $treeValue * 0.10; // 10% troškovi
        
        // Dodaj u investicije
        $investments[] = [
            'date' => $date->format('d.m.Y'),
            'amount' => $fundContribution,
            'project' => "Stablo #{$tree['id']} - {$tree['species']}"
        ];
    }
    
    // Sortiraj mjesečne podatke
    ksort($monthlyStats);
    $monthlyData = array_values($monthlyStats);
    
    // Ako nema podataka, stvori prazne mjesečne podatke za zadnjih 6 mjeseci
    if (empty($monthlyData)) {
        for ($i = 5; $i >= 0; $i--) {
            $date = new DateTime();
            $date->modify("-{$i} months");
            $monthlyData[] = [
                'income' => 0,
                'expense' => 0,
                'month' => $date->format('M Y')
            ];
        }
    }
    
    // Kumulativni fond
    $cumulativeData = $totalFund;
    
    $response = [
        'totalFund' => $totalFund,
        'cumulativeData' => $cumulativeData,
        'monthlyData' => $monthlyData,
        'investments' => $investments,
        'treesCount' => count($trees)
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Greška pri dohvaćanju podataka: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Greška: ' . $e->getMessage()]);
}
?>
