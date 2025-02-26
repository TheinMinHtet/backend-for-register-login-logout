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

// Handle DELETE Request for Skill Deletion
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Get skill_id from query parameters
    $skill_id = isset($_GET['skill_id']) ? intval($_GET['skill_id']) : null;

    if (!$skill_id) {
        echo json_encode(["status" => "error", "message" => "Skill ID is required"]);
        exit();
    }

    // Check if the skill belongs to the user
    $checkSkillQuery = "SELECT COUNT(*) FROM skill WHERE skill_id = ? AND user_id = ?";
    $stmt = $conn->prepare($checkSkillQuery);
    $stmt->bind_param("ii", $skill_id, $user_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count === 0) {
        echo json_encode(["status" => "error", "message" => "You are not authorized to delete this skill"]);
        exit();
    }

    // Delete associated tags
    $deleteTagQuery = "DELETE FROM tag WHERE skill_id = ?";
    $stmt = $conn->prepare($deleteTagQuery);
    $stmt->bind_param("i", $skill_id);
    $stmt->execute();
    $stmt->close();

    // Delete the skill
    $deleteSkillQuery = "DELETE FROM skill WHERE skill_id = ?";
    $stmt = $conn->prepare($deleteSkillQuery);
    $stmt->bind_param("i", $skill_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Skill deleted successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to delete skill"]);
    }
    $stmt->close();
    exit();
}

$skill_id = isset($_GET['skill_id']) ? intval($_GET['skill_id']) : null;
$requested_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

// If no user_id is provided, default to the authenticated user
if ($requested_user_id === null) {
    $requested_user_id = $user_id;
} else {
    // Check if user_id exists
    $checkUserQuery = "SELECT * FROM user WHERE user_id = ?";
    $stmt = $conn->prepare($checkUserQuery);
    $stmt->bind_param("i", $requested_user_id);
    $stmt->execute();
    $userResult = $stmt->get_result();

    if ($userResult->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Invalid user ID"]);
        exit();
    }
}

// If skill_id is provided, check if it exists
if ($skill_id !== null) {
    $checkSkillQuery = "SELECT * FROM skill WHERE skill_id = ?";
    $stmt = $conn->prepare($checkSkillQuery);
    $stmt->bind_param("i", $skill_id);
    $stmt->execute();
    $skillResult = $stmt->get_result();
    
    if ($skillResult->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Invalid skill ID"]);
        exit();
    }
}

// Handle JSON Data from frontend
$data = json_decode(file_get_contents('php://input'), true);
$title = $data['title'] ?? null;
$description = $data['description'] ?? null;
$tags = $data['tags'] ?? [];
$hours = $data['hours'] ?? null;

$response = [];

if ($title && $description && $tags && $hours) {
    // Insert the skill into the skill table
    $skillQuery = "INSERT INTO skill (user_id, name, description, hours) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($skillQuery);
    $stmt->bind_param("issi", $user_id, $title, $description, $hours);

    if ($stmt->execute()) {
        $skill_id = $stmt->insert_id;
        $stmt->close();

        // Update taught count for the skill
        $updateskillQuery = "UPDATE skill SET taught_count = taught_count + 1 WHERE user_id = ?";
        $stmt = $conn->prepare($updateskillQuery);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // Check if log exists for this user
        $checkLogQuery = "SELECT COUNT(*) FROM log WHERE user_id = ?";
        $stmt = $conn->prepare($checkLogQuery);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            // Update taught_count
            $updateLogQuery = "UPDATE log SET taught_count = taught_count + 1 WHERE user_id = ?";
            $stmt = $conn->prepare($updateLogQuery);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        } else {
            // Insert new log
            $insertLogQuery = "INSERT INTO log (user_id, taught_count) VALUES (?, 1)";
            $stmt = $conn->prepare($insertLogQuery);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        }

        // Insert tags into the tag table
        $tagJson = json_encode($tags);
        $tagQuery = "INSERT INTO tag (skill_id, tag) VALUES (?, ?)";
        $stmt = $conn->prepare($tagQuery);
        $stmt->bind_param("is", $skill_id, $tagJson);
        $stmt->execute();
        $stmt->close();

        $response["status"] = "success";
        $response["message"] = "Skill and tag added successfully";
    } else {
        $response["status"] = "error";
        $response["message"] = "Failed to insert skill";
    }
} else {
    $response["status"] = "error";
    $response["message"] = "Missing title, description, hour or tag";
}

if ($skill_id !== null) {
    $skillQuery = "SELECT * FROM skill WHERE skill_id = ?";
    $stmt = $conn->prepare($skillQuery);
    $stmt->bind_param("i", $skill_id);
} else {
    $skillQuery = "SELECT * FROM skill WHERE user_id = ?";
    $stmt = $conn->prepare($skillQuery);
    $stmt->bind_param("i", $requested_user_id);
}


// Execute and check for errors
if ($stmt->execute()) {
    $skillResult = $stmt->get_result();
} else {
    echo json_encode(["status" => "error", "message" => "Database error: " . $stmt->error]);
    exit();
}


$skill = [];
if ($skillResult->num_rows > 0) {
    while ($row = $skillResult->fetch_assoc()) {
        $skill[] = $row;
    }
}

// Fetch user details (optional, depends on your requirements)
$userQuery = "SELECT * FROM user WHERE user_id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $requested_user_id); // Use the requested user_id
$stmt->execute();
$userResult = $stmt->get_result();

$user = [];
if ($userResult->num_rows > 0) {
    while ($row = $userResult->fetch_assoc()) {
        $user[] = $row;
    }
}

if ($skill_id !== null) {
    $tagQuery = "SELECT * FROM tag WHERE skill_id = ?";
    $stmt = $conn->prepare($tagQuery);
    $stmt->bind_param("i", $skill_id);
} else {
    $tagQuery = "SELECT * FROM tag WHERE skill_id IN (SELECT skill_id FROM skill WHERE user_id = ?)";
    $stmt = $conn->prepare($tagQuery);
    $stmt->bind_param("i", $requested_user_id);
}

// Execute and check for errors
if ($stmt->execute()) {
    $tagResult = $stmt->get_result();
} else {
    echo json_encode(["status" => "error", "message" => "Database error: " . $stmt->error]);
    exit();
}


$tag = [];
if ($tagResult->num_rows > 0) {
    while ($row = $tagResult->fetch_assoc()) {
        $tag[] = $row;
    }
}

// Combine all data into a single response
$response["user"] = $user;
$response["skill"] = $skill;
$response["tag"] = $tag;

// Output the combined response as JSON
echo json_encode($response, JSON_PRETTY_PRINT);
?>
