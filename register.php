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
    $nickname  = isset($data['nickname']) ? trim($data['nickname']) : null;

    $show_first    = array_key_exists('show_first_name', $data) ? (bool)$data['show_first_name'] : true;
    $show_last     = array_key_exists('show_last_name', $data) ? (bool)$data['show_last_name'] : true;
    $show_company  = array_key_exists('show_company', $data) ? (bool)$data['show_company'] : true;
    $show_nickname = array_key_exists('show_nickname', $data) ? (bool)$data['show_nickname'] : true;

    // validacija
    if (!$email || !$password || !$nickname) {
        http_response_code(400);
        echo json_encode(["error" => "Email, lozinka i nadimak su obavezni."]);
        return;
    }

    try {
        // hash lozinke
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $sql = "
            INSERT INTO users (
                email, password, first_name, last_name, company, nickname,
                show_first_name, show_last_name, show_company, show_nickname,
                avatar_url, created_at
            )
            VALUES (:email, :password, :first, :last, :company, :nickname,
                    :show_first, :show_last, :show_company, :show_nickname,
                    :avatar_url, NOW())
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
            'show_nickname'=> $show_nickname,
            'avatar_url'  => 'assets/images/avatars/A5.png' // Default avatar za novog korisnika
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
        // Log error za debug
        error_log("Registration error: " . $e->getMessage() . " | Code: " . $e->getCode());
        
        // Provjeri unique constraint error
        // MySQL error code 1062 = Duplicate entry
        // PostgreSQL error code 23505 = unique_violation
        $errorCode = $e->getCode();
        $errorMessage = $e->getMessage();
        
        if ($errorCode == 1062 || $errorCode == "23505" || $errorCode == 23505) {
            // Provjeri da li se radi o email ili nadimak
            $errorLower = strtolower($errorMessage);
            
            // Provjeri da li se spominje i email i nickname (oba)
            $hasEmail = (strpos($errorLower, "email") !== false || 
                        strpos($errorLower, "users.email") !== false ||
                        strpos($errorLower, "'email'") !== false);
            
            $hasNickname = (strpos($errorLower, "nickname") !== false || 
                           strpos($errorLower, "users.nickname") !== false ||
                           strpos($errorLower, "'nickname'") !== false);
            
            // Ako se spominje nickname, prioritiziraj ga (jer je vjerojatnije da je to problem)
            if ($hasNickname) {
                http_response_code(400);
                echo json_encode(["error" => "Nadimak već postoji."]);
                return;
            }
            
            // Ako se spominje email
            if ($hasEmail) {
                http_response_code(400);
                echo json_encode(["error" => "Email već postoji."]);
                return;
            }
            
            // Provjeri MySQL format: "Duplicate entry 'value' for key 'key_name'"
            if (preg_match("/duplicate entry.*for key.*nickname/i", $errorMessage)) {
                http_response_code(400);
                echo json_encode(["error" => "Nadimak već postoji."]);
                return;
            }
            
            if (preg_match("/duplicate entry.*for key.*email/i", $errorMessage)) {
                http_response_code(400);
                echo json_encode(["error" => "Email već postoji."]);
                return;
            }
            
            // Ako je unique error ali ne znamo koji
            http_response_code(400);
            echo json_encode(["error" => "Podaci već postoje."]);
            return;
        }
        
        // Ako nije unique constraint, provjeri da li se možda email ili nickname spominje u grešci
        $errorLower = strtolower($errorMessage);
        if (strpos($errorLower, "nickname") !== false && 
            (strpos($errorLower, "unique") !== false || strpos($errorLower, "duplicate") !== false)) {
            http_response_code(400);
            echo json_encode(["error" => "Nadimak već postoji."]);
            return;
        }
        if (strpos($errorLower, "email") !== false && 
            (strpos($errorLower, "unique") !== false || strpos($errorLower, "duplicate") !== false)) {
            http_response_code(400);
            echo json_encode(["error" => "Email već postoji."]);
            return;
        }
        
        // Generička greška
        http_response_code(500);
        echo json_encode([
            "error" => "Database error",
            "details" => $e->getMessage()
        ]);
    }
}

