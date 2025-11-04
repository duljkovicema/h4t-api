<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

function getAnalytics($pdo) {
    try {
        // 1. Zaštićeno - COUNT stabala gde user_id IS NOT NULL
        // Kumulativno: za svaki datum, koliko je ukupno stabala sa user_id kreirano do tog datuma
        $protectedSql = "
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as count
            FROM trees
            WHERE user_id IS NOT NULL
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ";
        $stmt = $pdo->query($protectedSql);
        $protectedRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Kalkulacija kumulativnih vrednosti
        $protectedTimeSeries = [];
        $protectedCumulative = 0;
        foreach ($protectedRows as $row) {
            $protectedCumulative += (int)$row['count'];
            $protectedTimeSeries[] = [
                'date' => $row['date'],
                'value' => $protectedCumulative
            ];
        }
        
        // Trenutna ukupna vrednost
        $protectedTotalStmt = $pdo->query("SELECT COUNT(*) as total FROM trees WHERE user_id IS NOT NULL");
        $protectedTotal = $protectedTotalStmt->fetch(PDO::FETCH_ASSOC)['total'];

        // 2. Mapirano - COUNT svih stabala
        $mappedSql = "
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as count
            FROM trees
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ";
        $stmt = $pdo->query($mappedSql);
        $mappedRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $mappedTimeSeries = [];
        $mappedCumulative = 0;
        foreach ($mappedRows as $row) {
            $mappedCumulative += (int)$row['count'];
            $mappedTimeSeries[] = [
                'date' => $row['date'],
                'value' => $mappedCumulative
            ];
        }
        
        $mappedTotalStmt = $pdo->query("SELECT COUNT(*) as total FROM trees");
        $mappedTotal = $mappedTotalStmt->fetch(PDO::FETCH_ASSOC)['total'];

        // 3. Ukupno korisnika - COUNT iz users tabele
        $usersSql = "
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as count
            FROM users
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ";
        $stmt = $pdo->query($usersSql);
        $usersRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $usersTimeSeries = [];
        $usersCumulative = 0;
        foreach ($usersRows as $row) {
            $usersCumulative += (int)$row['count'];
            $usersTimeSeries[] = [
                'date' => $row['date'],
                'value' => $usersCumulative
            ];
        }
        
        $usersTotalStmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $usersTotal = $usersTotalStmt->fetch(PDO::FETCH_ASSOC)['total'];

        // 4. Ukupno CO2 - SUM iz user_co2 tabele
        $co2Sql = "
            SELECT 
                DATE(created_at) as date,
                SUM(co2) as sum_co2
            FROM user_co2
            WHERE co2 IS NOT NULL
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ";
        $stmt = $pdo->query($co2Sql);
        $co2Rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $co2TimeSeries = [];
        $co2Cumulative = 0;
        foreach ($co2Rows as $row) {
            $co2Cumulative += (float)$row['sum_co2'];
            $co2TimeSeries[] = [
                'date' => $row['date'],
                'value' => round($co2Cumulative, 2)
            ];
        }
        
        $co2TotalStmt = $pdo->query("SELECT COALESCE(SUM(co2), 0) as total FROM user_co2 WHERE co2 IS NOT NULL");
        $co2Total = round((float)$co2TotalStmt->fetch(PDO::FETCH_ASSOC)['total'], 2);

        echo json_encode([
            'protected' => [
                'total' => (int)$protectedTotal,
                'timeSeries' => $protectedTimeSeries
            ],
            'mapped' => [
                'total' => (int)$mappedTotal,
                'timeSeries' => $mappedTimeSeries
            ],
            'users' => [
                'total' => (int)$usersTotal,
                'timeSeries' => $usersTimeSeries
            ],
            'co2' => [
                'total' => $co2Total,
                'timeSeries' => $co2TimeSeries
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "error" => "Database error",
            "details" => $e->getMessage()
        ]);
    }
}

