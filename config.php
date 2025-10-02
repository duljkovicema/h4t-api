<?php
// config.php
$host = "localhost";
$db_name = "agilosor_h4t";
$username = "agilosor_izuna";
$password = "h}K(ZC5FaJBX";
$port = 3306;

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db_name;charset=utf8",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo $e;
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit();
}
?>
