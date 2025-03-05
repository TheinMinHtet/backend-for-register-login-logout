<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require 'vendor/autoload.php';
require "database_connection.php";
define('JWT_SECRET', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c');

function getTokenFromHeader()
{
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

function verifyJWT($token)
{
    try {
        return JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
    } catch (Exception $e) {
        return null;
    }
}

// Parse the request URI to determine the route
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/skillSwap/skill-swap/'; // Adjust this to match your base path
$route = substr($requestUri, strlen($basePath));

// Handle the /user?user_id=:id route
if (strpos($route, 'user') !== false && isset($_GET['user_id'])) {
    $user_id = $_GET['user_id']; // Get user_id from query parameter

    // Fetch memories for the specific user
    $memoryQuery = "
        SELECT memory.*, user.*, skill.name 
        FROM memory 
        LEFT JOIN user ON memory.user_id = user.user_id 
        LEFT JOIN skill ON skill.skill_id = memory.skill_id 
        WHERE memory.user_id = ?
    ";
    $stmt = $conn->prepare($memoryQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $memoryResult = $stmt->get_result();

    $memories = [];
    if ($memoryResult->num_rows > 0) {
        while ($row = $memoryResult->fetch_assoc()) {
            $memories[] = $row;
        }
    }

    // Prepare the response
    $response = [
        "memories" => $memories,
    ];

    echo json_encode($response);
    exit();
}

// Default route (fetch all skills, users, and tags)
$skillQuery = "SELECT memory.*, user.*, skill.name FROM memory LEFT JOIN user ON memory.user_id = user.user_id LEFT JOIN skill ON skill.skill_id = memory.skill_id";
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
