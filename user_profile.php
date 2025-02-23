<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // Allow all origins
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // Allow these HTTP methods
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Allow specific headers

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require 'vendor/autoload.php';
require "database_connection.php";

define('JWT_SECRET', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c');




function getTokenFromHeader()
{
    $headers = getallheaders(); // Get headers

    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'authorization') {
            return str_replace('Bearer ', '', $value);
        }
    }

    // Alternative check via $_SERVER
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        return str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        return str_replace('Bearer ', '', $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    }

    return null;
}

function verifyJWT($token)
{
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

        $profile_image = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            $fileExtension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));

            if (!in_array($fileExtension, $allowedExtensions)) {
                echo json_encode(["status" => "error", "message" => "Invalid image format"]);
                exit();
            }

            $uploadDir = "uploads/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $profile_image = $uploadDir . "user_" . $user_id . "_" . time() . "." . $fileExtension;
            move_uploaded_file($_FILES['profile_image']['tmp_name'], $profile_image);
        }


        // Handle JSON Data from frontend
        $description = $_POST['description'] ?? null;

        if ($profile_image && $description) {
            $memoryQuery = "INSERT INTO memory (user_id, img_name, description) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($memoryQuery);
            $stmt->bind_param("iss", $user_id, $profile_image, $description);

            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Image and description added successfully"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to insert into memory table"]);
            }
            $stmt->close();
        } else {
            echo json_encode(["status" => "error", "message" => "Missing image or description"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid token"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Token missing"]);
}

// Fetch memory data
$memoryQuery = "SELECT memory.img_name, memory.description FROM memory";
$memoryResult = $conn->query($memoryQuery);

$memory = [];
if ($memoryResult->num_rows > 0) {
    while ($row = $memoryResult->fetch_assoc()) {
        $memory[] = $row;
    }
}

// Fetch all user details
$userQuery = "SELECT * FROM user";
$userResult = $conn->query($userQuery);

$user = [];
if ($userResult->num_rows > 0) {
    while ($row = $userResult->fetch_assoc()) {
        $user[] = $row;
    }
}

$response = [
    "memory" => $memory,
    "user" => $user
];

echo json_encode($response, JSON_PRETTY_PRINT);
