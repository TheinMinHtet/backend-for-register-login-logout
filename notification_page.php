<?php
header("Content-Type: application/json");

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require 'vendor/autoload.php';
require "database_connection.php";

define('JWT_SECRET', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c');

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
        $user_id = $decoded->user_id;

        $data = json_decode(file_get_contents('php://input'), true);
        $description = $data['description'] ?? null;
        if ($description) {
            $descriptionQuery= "INSERT INTO notification (user_id, description) VALUES (?, ?)";
            $stmt = $conn->prepare($descriptionQuery);
            $stmt->bind_param("is", $user_id, $description);

            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Description added successfully"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to insert data"]);
            }

            $stmt->close();

        } else {
            echo json_encode(["status" => "error", "message" => "Missing description"]);
        }

        $notiQuery = "SELECT * FROM notification WHERE user_id = ?";
        $stmt = $conn->prepare($notiQuery);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $notiResult = $stmt->get_result();

        $noti = [];
        while ($row = $notiResult->fetch_assoc()) {
            $noti[] = $row;
        }

        $stmt->close();
        $conn->close();

        echo json_encode(["noti" => $noti], JSON_PRETTY_PRINT);
    } else {
        echo json_encode(["error" => "Invalid token"], JSON_PRETTY_PRINT);
    }
} else {
    echo json_encode(["error" => "Authorization token missing"], JSON_PRETTY_PRINT);
}
?>
