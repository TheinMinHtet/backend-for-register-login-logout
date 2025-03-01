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

// Function to check if a skill title is unique for a user
function isSkillTitleUnique($conn, $user_id, $title, $skill_id = null) {
    $query = "SELECT COUNT(*) AS count FROM skill WHERE user_id = ? AND name = ?";
    $params = [$user_id, $title];
    $types = "is";

    if ($skill_id !== null) {
        $query .= " AND skill_id != ?";
        $params[] = $skill_id;
        $types .= "i";
    }

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Database error: " . $conn->error);
        return false;
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $count = $row['count'] ?? 0;
    $stmt->close();

    return $count == 0; // If count is 0, the title is unique
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
    $data = json_decode(file_get_contents('php://input'), true);
    $skill_id = $data['skill_id'] ?? null;

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

// Handle PUT Request for Skill Update
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Decode the JSON input from the request body
    $data = json_decode(file_get_contents('php://input'), true);
    $skill_id = $data['skill_id'] ?? null;
    $title = $data['title'] ?? null;
    $description = $data['description'] ?? null;
    $hours = $data['hours'] ?? null;
    $tags = $data['tags'] ?? [];

    // Log the request data for debugging
    error_log("PUT Request Data: " . print_r($data, true));

    // Validate required fields
    if (!$skill_id || !$title || !$description || !$hours) {
        echo json_encode(["status" => "error", "message" => "Skill ID, title, description, and hours are required"]);
        exit();
    }

    // Check if the skill belongs to the user
    $checkSkillQuery = "SELECT COUNT(*) FROM skill WHERE skill_id = ? AND user_id = ?";
    $stmt = $conn->prepare($checkSkillQuery);
    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
        exit();
    }
    $stmt->bind_param("ii", $skill_id, $user_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count === 0) {
        echo json_encode(["status" => "error", "message" => "You are not authorized to update this skill"]);
        exit();
    }

    // Check if the skill title is unique for the user (excluding the current skill)
    if (!isSkillTitleUnique($conn, $user_id, $title, $skill_id)) {
        echo json_encode(["status" => "error", "message" => "Skill title already exists"]);
        exit();
    }

    // Update the skill
    $updateSkillQuery = "UPDATE skill SET name = ?, description = ?, hours = ? WHERE skill_id = ?";
    $stmt = $conn->prepare($updateSkillQuery);
    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
        exit();
    }
    $stmt->bind_param("ssii", $title, $description, $hours, $skill_id);

    if ($stmt->execute()) {
        $stmt->close();

        // Update tags
        $deleteTagsQuery = "DELETE FROM tag WHERE skill_id = ?";
        $deleteStmt = $conn->prepare($deleteTagsQuery);
        if (!$deleteStmt) {
            echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
            exit();
        }
        $deleteStmt->bind_param("i", $skill_id);
        $deleteStmt->execute();
        $deleteStmt->close();

        // Insert new tags
        $insertTagQuery = "INSERT INTO tag (skill_id, tag) VALUES (?, ?)";
        $insertStmt = $conn->prepare($insertTagQuery);
        if (!$insertStmt) {
            echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
            exit();
        }
        foreach ($tags as $tag) {
            $insertStmt->bind_param("is", $skill_id, $tag);
            $insertStmt->execute();
        }
        $insertStmt->close();

        echo json_encode(["status" => "success", "message" => "Skill updated successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update skill"]);
    }
    exit();
}

// Handle POST Request for Skill Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decode the JSON input from the request body
    $data = json_decode(file_get_contents('php://input'), true);
    $title = $data['title'] ?? null;
    $description = $data['description'] ?? null;
    $tags = $data['tags'] ?? [];
    $hours = $data['hours'] ?? null;

    // Validate required fields
    if (!$title || !$description || !$hours || empty($tags)) {
        echo json_encode(["status" => "error", "message" => "Title, description, hours, and tags are required"]);
        exit();
    }

    // Check if the skill title is unique for the user
    if (!isSkillTitleUnique($conn, $user_id, $title)) {
        echo json_encode(["status" => "error", "message" => "Skill title already exists"]);
        exit();
    }

    // Insert the skill into the skill table
    $skillQuery = "INSERT INTO skill (user_id, name, description, hours) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($skillQuery);
    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
        exit();
    }
    $stmt->bind_param("issi", $user_id, $title, $description, $hours);

    if ($stmt->execute()) {
        // Get the ID of the newly inserted skill
        $skill_id = $stmt->insert_id;
        $stmt->close();

        // Insert the tags into the tag table
        $insertTagQuery = "INSERT INTO tag (skill_id, tag) VALUES (?, ?)";
        $stmt = $conn->prepare($insertTagQuery);
        if (!$stmt) {
            echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
            exit();
        }

        // Insert each tag
        foreach ($tags as $tag) {
            $stmt->bind_param("is", $skill_id, $tag);
            if (!$stmt->execute()) {
                echo json_encode(["status" => "error", "message" => "Failed to insert tag: " . $stmt->error]);
                exit();
            }
        }
        $stmt->close();

        echo json_encode(["status" => "success", "message" => "Skill and tags added successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to insert skill: " . $stmt->error]);
    }
    exit();
}

// Retrieve skill_id and user_id from the query parameters
$skill_id = isset($_GET['skill_id']) ? intval($_GET['skill_id']) : null;
$requested_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

// If no user_id is provided, default to the authenticated user
if ($requested_user_id === null) {
    $requested_user_id = $user_id; // Use the authenticated user's ID
} else {
    // Validate that the requested user_id exists in the database
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

// If skill_id is provided, validate that it exists in the database
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

// Fetch skill details
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
    $response["status"] = "error";
    $response["message"] = "Database error: " . $stmt->error;
    echo json_encode($response);
    exit();
}

$skill = [];
if ($skillResult->num_rows > 0) {
    while ($row = $skillResult->fetch_assoc()) {
        $skill[] = $row;
    }
}

// Extract the user_id of the skill owner
if (!empty($skill)) {
    $skill_owner_id = $skill[0]['user_id'];
} else {
    $skill_owner_id = null;
}

// Fetch user details for the skill owner
if ($skill_owner_id !== null) {
    $userQuery = "SELECT * FROM user WHERE user_id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("i", $skill_owner_id); // Use the skill owner's user_id
    $stmt->execute();
    $userResult = $stmt->get_result();

    $user = [];
    if ($userResult->num_rows > 0) {
        while ($row = $userResult->fetch_assoc()) {
            $user[] = $row;
        }
    }
} else {
    $user = [];
}

// Fetch tags associated with the skills
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
    $response["status"] = "error";
    $response["message"] = "Database error: " . $stmt->error;
    echo json_encode($response);
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
