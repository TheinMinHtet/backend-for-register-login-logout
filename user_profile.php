<?php
header("Content-Type: application/json");

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require 'vendor/autoload.php';
require "database_connection.php";

define('JWT_SECRET', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c'); // Use a proper secret key

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

// Get token from request
$token = getTokenFromHeader();

// If no token, return error and stop execution
if (!$token) {
    echo json_encode(["status" => "error", "message" => "Token missing"]);
    exit();
}

// Verify the token
$decoded = verifyJWT($token);
if (!$decoded) {
    echo json_encode(["status" => "error", "message" => "Invalid token"]);
    exit();
}

// If the token is valid, proceed with fetching user ID
$user_id = $decoded->user_id;

// Handle file upload
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

// Handle description
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

// Fetch memory data (Only for authenticated users)
$memoryQuery = "SELECT memory.img_name, memory.description FROM memory WHERE user_id = ?";
$stmt = $conn->prepare($memoryQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$memory = [];
while ($row = $result->fetch_assoc()) {
    $memory[] = $row;
}
$stmt->close();

// Fetch user details (Only for authenticated users)
$userQuery = "SELECT user.*, memory.img_name, memory.description FROM user JOIN memory ON user.user_id = memory.user_id;";
$stmt = $conn->prepare($userQuery);
$stmt->execute();
$result = $stmt->get_result();

$user = [];
while ($row = $result->fetch_assoc()) {
    $user[] = $row;
}
$stmt->close();

$response = [
    "memory" => $memory,
    "user" => $user
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>
