<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
require 'vendor/autoload.php';
require "database_connection.php";
define('JWT_SECRET', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c');

function getTokenFromHeader() {
    $headers = getallheaders();
    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'authorization') {
            return str_replace('Bearer ', '', $value);
        }
    }
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        return str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        return str_replace('Bearer ', '', $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
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

// Get & verify token
$token = getTokenFromHeader();
if (!$token) {
    echo json_encode(["status" => "error", "message" => "Token missing"]);
    exit();
}
$decoded = verifyJWT($token);
if (!$decoded) {
    echo json_encode(["status" => "error", "message" => "Invalid token"]);
    exit();
}
$user_id = $decoded->user_id;

// Handle DELETE Request for Memory Deletion
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $memory_id = isset($_GET['memory_id']) ? intval($_GET['memory_id']) : null;

    if (!$memory_id) {
        echo json_encode(["status" => "error", "message" => "Memory ID is required"]);
        exit();
    }

    // Check if the memory belongs to the user
    $checkMemoryQuery = "SELECT COUNT(*) FROM memory WHERE memory_id = ? AND user_id = ?";
    $stmt = $conn->prepare($checkMemoryQuery);
    $stmt->bind_param("ii", $memory_id, $user_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count === 0) {
        echo json_encode(["status" => "error", "message" => "You are not authorized to delete this memory"]);
        exit();
    }

    // Delete the memory
    $deleteMemoryQuery = "DELETE FROM memory WHERE memory_id = ?";
    $stmt = $conn->prepare($deleteMemoryQuery);
    $stmt->bind_param("i", $memory_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Memory deleted successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to delete memory"]);
    }
    $stmt->close();
    exit();
}

// Handle POST Request for Memory Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $description = $data['description'] ?? null;
    $image = $data['image'] ?? null; // Assuming image is passed as base64 encoded string

    if ($description && $image) {
        // Decode the base64 image
        $imageData = base64_decode($image);
        $imagePath = "uploads/memory/memory_" . $user_id . "_" . time() . ".png";
        file_put_contents($imagePath, $imageData);

        // Insert memory
        $memoryQuery = "INSERT INTO memory (user_id, img_name, description) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($memoryQuery);
        $stmt->bind_param("iss", $user_id, $imagePath, $description);

        if ($stmt->execute()) {
            // Success: Increase learnt_count
            $updateLogQuery = "UPDATE log SET learnt_count = learnt_count + 1 WHERE user_id = ?";
            $stmt = $conn->prepare($updateLogQuery);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();

            echo json_encode(["status" => "success", "message" => "Memory added successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to insert memory"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Missing description or image"]);
    }
    exit();
}

// Handle GET Request for Fetching Memories
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $requested_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : $user_id;

    // Fetch memories based on requested user ID
    $memoryQuery = "SELECT * FROM memory WHERE user_id = ?";
    $stmt = $conn->prepare($memoryQuery);
    $stmt->bind_param("i", $requested_user_id);
    $stmt->execute();
    $memoryResult = $stmt->get_result();

    $memory = [];
    if ($memoryResult->num_rows > 0) {
        while ($row = $memoryResult->fetch_assoc()) {
            $memory[] = $row;
        }
    }

    // Fetch user details
    $userQuery = "SELECT * FROM user WHERE user_id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("i", $requested_user_id);
    $stmt->execute();
    $userResult = $stmt->get_result();

    $user = [];
    if ($userResult->num_rows > 0) {
        while ($row = $userResult->fetch_assoc()) {
            $user[] = $row;
        }
    }

    // Combine all data into a single response
    $response = [
        "user" => $user,
        "memory" => $memory
    ];

    // Output the combined response as JSON
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit();
}
?>