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
    
    // Zagreb podaci - fiksni podatci
    $zagrebData = [
        'yearly_average' => 8500, // kg CO2 godišnje po osobi u Zagrebu
        'monthly_average' => 708, // kg CO2 mjesečno
        'daily_average' => 23.3, // kg CO2 dnevno
        'population' => 800000, // broj stanovnika
        'total_emissions' => 6800000000, // ukupne emisije u kg CO2 godišnje
        'transport_percentage' => 35, // % emisija iz transporta
        'energy_percentage' => 40, // % emisija iz energije
        'waste_percentage' => 15, // % emisija iz otpada
        'other_percentage' => 10, // % ostalih emisija
        'trend_data' => [
            '2020' => 8200,
            '2021' => 8300,
            '2022' => 8400,
            '2023' => 8500,
            '2024' => 8600,
            '2025' => 8700,
            '2026' => 8800,
            '2027' => 8900,
            '2028' => 9000,
            '2029' => 9100,
            '2030' => 9200
        ],
        'comparison_data' => [
            'croatia_average' => 7800,
            'eu_average' => 8200,
            'world_average' => 4200
        ],
        'green_initiatives' => [
            'trees_planted_2024' => 15000,
            'renewable_energy_percentage' => 25,
            'public_transport_usage' => 45,
            'cycling_infrastructure_km' => 120
        ]
    ];
    
    echo json_encode($zagrebData);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Greška pri dohvaćanju podataka: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Greška: ' . $e->getMessage()]);
}
?>
