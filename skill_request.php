<?php
header("Content-Type: application/json");

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require 'vendor/autoload.php';
require "database_connection.php";

define('JWT_SECRET', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c'); // Replace with your actual secret key

function getTokenFromHeader() {
    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

function verifyJWT($token) {
    try {
        return JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
    } catch (Exception $e) {
        return null;
    }
}

$token = getTokenFromHeader();
if ($token) {
    $decoded = verifyJWT($token);

    if ($decoded) {
        $user_id2 = $decoded->user_id;

        // Handle JSON Data from frontend
        $data = json_decode(file_get_contents('php://input'), true);
        $user_id = $data['user_id'] ?? null; // user_id from frontend (optional)

        if ($user_id) {
            $query = "INSERT INTO learner_teacher (user_id, user_id2) VALUES (?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $user_id, $user_id2);
        } else {
            $query = "INSERT INTO learner_teacher (user_id2) VALUES (?)";  // Insert only user_id2
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $user_id2);
        }

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "User added to learner_teacher table"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to insert user"]);
        }

        $stmt->close();
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid token"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Token missing"]);
}
?>