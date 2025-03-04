<?php
require "database_connection.php";

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // Allow all origins
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // Allow specific methods
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Allow specific headers

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require 'vendor/autoload.php';

define('JWT_SECRET', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c'); // Use a secure key

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

// Fetch skills from the database
$skillQuery = "SELECT memory.*,user.*,skill.name FROM memory LEFT JOIN user ON memory.user_id=user.user_id LEFT JOIN skill ON skill.skill_id=memory.skill_id"; // Adjust the query as needed
$skillResult = $conn->query($skillQuery);

$skills = [];
if ($skillResult->num_rows > 0) {
    while ($row = $skillResult->fetch_assoc()) {
        $skills[] = $row;
    }
}

// Fetch user details (if needed)
$userQuery = "SELECT * FROM user";
$userResult = $conn->query($userQuery);

$user = [];
if ($userResult->num_rows > 0) {
    while ($row = $userResult->fetch_assoc()) {
        $user[] = $row;
    }
}

// Fetch tags (if needed)
$tagQuery = "SELECT * FROM tag";
$tagResult = $conn->query($tagQuery);

$tags = [];
if ($tagResult->num_rows > 0) {
    while ($row = $tagResult->fetch_assoc()) {
        $tags[] = $row;
    }
}

// Prepare the response
$response = [
    "user" => $user,
    "memories" => $skills,
    "tag" => $tags,
];

echo json_encode($response);

$conn->close();
