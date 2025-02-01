<?php
header("Content-Type: application/json");

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require 'vendor/autoload.php';
require 'database_connection.php';

define('JWT_SECRET', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c');

function generateJWT($user_id, $username, $email) {
    $payload = [
        'user_id' => $user_id,
        'username' => $username,
        'email' => $email,
        'iat' => time(),
        'exp' => time() + 3600
    ];
    return JWT::encode($payload, JWT_SECRET, 'HS256');
}

function verifyJWT($token) {
    try {
        return JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
    } catch (Exception $e) {
        return null;
    }
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (isset($input['login'])) {
        // Input validation
        if (empty($input['username']) || empty($input['password'])) {
            echo json_encode(["status" => "error", "message" => "Username/email and password are required"]);
            exit();
        }

        $username_or_email = $input['username'];
        $password = $input['password'];

        // Modified SQL query to check for either username or email
        $stmt = $conn->prepare("SELECT user_id, username, email, password FROM user WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username_or_email, $username_or_email);
        $stmt->execute();
        $stmt->bind_result($user_id, $username, $email, $hashed_password);
        $stmt->fetch();

        if ($hashed_password && password_verify($password, $hashed_password)) {
            // Successful login, generate JWT token
            $token = generateJWT($user_id, $username, $email);
            echo json_encode([
                "status" => "success",
                "message" => "Login successful",
                "token" => $token
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid credentials"
            ]);
        }
        $stmt->close();
    }

    if (isset($input['logout'])) {
        // No specific server-side action is required to logout; the client just needs to delete the token.
        echo json_encode([
            "status" => "success",
            "message" => "Logout successful. Please delete the token on the client-side."
        ]);
    }
}
?>
