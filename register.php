<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

function registerUser($pdo, $data) {
    $email     = $data['email']     ?? null;
    $password  = $data['password']  ?? null;
    $first     = $data['first_name'] ?? null;
    $last      = $data['last_name']  ?? null;
    $company   = $data['company']    ?? null;
    $nickname  = $data['nickname']   ?? null;

    $show_first    = array_key_exists('show_first_name', $data) ? (bool)$data['show_first_name'] : true;
    $show_last     = array_key_exists('show_last_name', $data) ? (bool)$data['show_last_name'] : true;
    $show_company  = array_key_exists('show_company', $data) ? (bool)$data['show_company'] : true;
    $show_nickname = array_key_exists('show_nickname', $data) ? (bool)$data['show_nickname'] : true;

    // validacija
    if (!$email || !$password) {
        http_response_code(400);
        echo json_encode(["error" => "Email i lozinka su obavezni."]);
        return;
    }

    try {
        // hash lozinke
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $sql = "
            INSERT INTO users (
                email, password, first_name, last_name, company, nickname,
                show_first_name, show_last_name, show_company, show_nickname,
                created_at
            )
            VALUES (:email, :password, :first, :last, :company, :nickname,
                    :show_first, :show_last, :show_company, :show_nickname,
                    NOW()
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'email'       => $email,
            'password'    => $hashedPassword,
            'first'       => $first,
            'last'        => $last,
            'company'     => $company,
            'nickname'    => $nickname,
            'show_first'  => $show_first,
            'show_last'   => $show_last,
            'show_company'=> $show_company,
            'show_nickname'=> $show_nickname
        ]);

        $id = $pdo->lastInsertId();

        // vrati korisnika bez lozinke
        $user = [
            "id"             => $id,
            "email"          => $email,
            "first_name"     => $first,
            "last_name"      => $last,
            "company"        => $company,
            "nickname"       => $nickname,
            "show_first_name"=> $show_first,
            "show_last_name" => $show_last,
            "show_company"   => $show_company,
            "show_nickname"  => $show_nickname
        ];

        http_response_code(201);
        echo json_encode(["user" => $user]);

    } catch (PDOException $e) {
        // Unique constraint violation (MySQL error 1062, PostgreSQL 23505)
        if ($e->getCode() == 1062 || $e->getCode() == "23505") {
            http_response_code(400);
            echo json_encode(["error" => "Email već postoji."]);
        } else {
            http_response_code(500);
            echo json_encode([
                "error" => "Database error",
                "details" => $e->getMessage()
            ]);
        }
    }
}

// === poziv u index.php (router) ===
if ($method === 'POST' && preg_match("/\/register$/", $request)) {
    require_once 'register.php';
    $input = json_decode(file_get_contents("php://input"), true);
    registerUser($pdo, $input);
}
