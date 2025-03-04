<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // Allow all origins
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // Allow these HTTP methods
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Allow specific headers

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require 'vendor/autoload.php';
require "database_connection.php";

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

$user_id = $decoded->user_id; // Get user_id from token

// Handle profile image update separately
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $fileExtension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));

    if (!in_array($fileExtension, $allowedExtensions)) {
        echo json_encode(["status" => "error", "message" => "Invalid image format"]);
        exit();
    }

    $uploadDir = "uploads/profile_pics/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $profile_image = $uploadDir . "user_" . $user_id . "." . $fileExtension;
    move_uploaded_file($_FILES['profile_image']['tmp_name'], $profile_image);

    // Update only the profile image
    $updateImageQuery = "UPDATE user SET profile_img = ? WHERE user_id = ?";
    $stmt = $conn->prepare($updateImageQuery);
    $stmt->bind_param("si", $profile_image, $user_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Profile image updated successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update profile image"]);
    }
    $stmt->close();
    exit(); // Stop further execution after handling the image upload
}


// :id url
// Parse the URL path to extract user_id for GET requests
// :id url
// Parse the URL path to extract user_id for GET requests
// :id url
// Parse the URL path to extract user_id for GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $requestUri = $_SERVER['REQUEST_URI']; // Get the full request URI
    $scriptName = $_SERVER['SCRIPT_NAME']; // Get the script name (e.g., /skillSwap/skill-swap/user_profile.php)

    // Remove the script name from the request URI to get the path
    $path = substr($requestUri, strlen($scriptName));

    // Check if the path matches the pattern /:user_id
    if (preg_match('/^\/(\d+)$/', $path, $matches)) {
        $user_id = (int) $matches[1]; // Extract user_id from the path

        // Fetch user data for the given user_id
        $userQuery = "
            SELECT 
    u.user_id, 
    u.username, 
    u.email, 
    u.password, 
    u.otp, 
    u.otp_expiry, 
    u.telegram_phone, 
    u.telegram_username, 
    u.created_at, 
    u.status, 
    u.bio, 
    u.country, 
    u.region, 
    u.city, 
    u.profile_img, 
    u.points,
    m.memory_id,
    m.img_name AS memory_img, 
    m.description AS memory_description,
    s.skill_id,
    s.description AS skill_description,
    s.name AS skill_name,  -- Include skill name here
    s.hours,
    s.taught_count AS skill_taught,
    l.learnt_count, 
    l.taught_count,
    t.tag_id,
    t.tag
FROM user u
LEFT JOIN memory m ON u.user_id = m.user_id
LEFT JOIN skill s ON u.user_id = s.user_id  -- Direct join with skill table
LEFT JOIN tag t ON s.skill_id = t.skill_id
LEFT JOIN log l ON u.user_id = l.user_id
WHERE u.user_id = ?
ORDER BY u.user_id;

        ";

        $stmt = $conn->prepare($userQuery);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $user = null;
        $skills = [];
        $memories = [];

        while ($row = $result->fetch_assoc()) {
            $userId = $row['user_id'];
            $skillId = $row['skill_id'];
            $tagId = $row['tag_id'];

            // Initialize user data if not already set
            if ($user === null) {
                $user = [
                    "user_id" => $row["user_id"],
                    "username" => $row["username"],
                    "email" => $row["email"],
                    "password" => $row["password"],
                    "otp" => $row["otp"],
                    "otp_expiry" => $row["otp_expiry"],
                    "telegram_phone" => $row["telegram_phone"],
                    "telegram_username" => $row["telegram_username"],
                    "created_at" => $row["created_at"],
                    "status" => $row["status"],
                    "bio" => $row["bio"],
                    "country" => $row["country"],
                    "region" => $row["region"],
                    "city" => $row["city"],
                    "profile_img" => $row["profile_img"],
                    "skill_learnt" => $row["learnt_count"],
                    "skill_taught" => $row["taught_count"],
                    "points" => $row["points"],
                    "memories" => [],
                    "skills" => []
                ];
            }

            // Add memory if exists and not already added
            if (!empty($row['memory_img']) && !in_array($row['memory_img'], array_column($memories, 'img_name'))) {
                $memories[] = [
                    "memory_id" => $row["memory_id"],
                    "img_name" => $row["memory_img"],
                    "description" => $row["memory_description"],
                    "name" => $row["skill_name"] // Include skill name
                ];
            }

            // Add skill if not already present
            if (!empty($row['skill_description']) && !isset($skills[$skillId])) {
                $skills[$skillId] = [
                    "skill_id" => $skillId,
                    "description" => $row["skill_description"],
                    "name" => $row["skill_name"],
                    "hours" => $row["hours"],
                    "skill_taught" => $row["skill_taught"],
                    "tags" => [] // Initialize tags as an empty array
                ];
            }

            // Add tags to the corresponding skill
            if (!empty($tagId) && isset($skills[$skillId])) {
                $tagName = json_decode($row['tag'], true);
                if (!is_array($tagName)) {
                    $tagName = [$row['tag']]; // Ensure it's an array
                }

                // Add tags to the skill's tags array
                foreach ($tagName as $singleTag) {
                    // Check if the tag is already added to avoid duplicates
                    $tagExists = false;
                    foreach ($skills[$skillId]['tags'] as $existingTag) {
                        if ($existingTag['tag'] === $singleTag) {
                            $tagExists = true;
                            break;
                        }
                    }

                    if (!$tagExists) {
                        $skills[$skillId]['tags'][] = [
                            "tag_id" => $tagId,
                            "tag" => $singleTag
                        ];
                    }
                }
            }
        }

        // Attach memories and skills to the user object
        if ($user !== null) {
            $user["memories"] = $memories;
            $user["skills"] = array_values($skills); // Reset array index for skills
        }

        // Return the structured response
        echo json_encode([
            "status" => "success",
            "message" => "User data fetched successfully",
            "user" => $user
        ]);
        exit(); // Stop further execution
    }
}
// :id url

// Fetch current profile data
$currentQuery = "SELECT username, password, telegram_phone, telegram_username, city, region, country, bio, status FROM user WHERE user_id = ?";
$stmt = $conn->prepare($currentQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$currentData = $result->fetch_assoc();
$stmt->close();

// Get new fields from POST data
$username = isset($_POST['username']) ? trim($_POST['username']) : null;
$password = isset($_POST['password']) ? trim($_POST['password']) : null;
$telegram_phone = isset($_POST['telegram_phone']) ? trim($_POST['telegram_phone']) : null;
$telegram_username = isset($_POST['telegram_username']) ? trim($_POST['telegram_username']) : null;
$city = isset($_POST['city']) ? trim($_POST['city']) : null;
$region = isset($_POST['region']) ? trim($_POST['region']) : null;
$country = isset($_POST['country']) ? trim($_POST['country']) : null;
$bio = isset($_POST['bio']) ? trim($_POST['bio']) : null;
$status = isset($_POST['status']) ? trim($_POST['status']) : null;

// Check if at least one value is different
$updates = [];
$params = [];
$types = "";

if (isset($username) && $username !== $currentData["username"]) {
    $updates[] = "username = ?";
    $params[] = $username;
    $types .= "s";
}
if (isset($password) && $password !== $currentData["password"]) {
    $updates[] = "password = ?";
    $params[] = password_hash($password, PASSWORD_DEFAULT); // Hash the password
    $types .= "s";
}
if (isset($telegram_phone) && $telegram_phone !== $currentData["telegram_phone"]) {
    $updates[] = "telegram_phone = ?";
    $params[] = $telegram_phone;
    $types .= "s";
}
if (isset($telegram_username) && $telegram_username !== $currentData["telegram_username"]) {
    $updates[] = "telegram_username = ?";
    $params[] = $telegram_username;
    $types .= "s";
}
if (isset($city) && $city !== $currentData["city"]) {
    $updates[] = "city = ?";
    $params[] = $city;
    $types .= "s";
}
if (isset($region) && $region !== $currentData["region"]) {
    $updates[] = "region = ?";
    $params[] = $region;
    $types .= "s";
}
if (isset($country) && $country !== $currentData["country"]) {
    $updates[] = "country = ?";
    $params[] = $country;
    $types .= "s";
}
if (isset($bio) && $bio !== $currentData["bio"]) {
    $updates[] = "bio = ?";
    $params[] = $bio;
    $types .= "s";
}
if (isset($status) && $status !== $currentData["status"]) {
    $updates[] = "status = ?";
    $params[] = $status;
    $types .= "s";
}

if (empty($updates)) {
    // If no changes are detected, fetch the user data and return it
    $userQuery = "
        SELECT 
    u.user_id, 
    u.username, 
    u.email, 
    u.password, 
    u.otp, 
    u.otp_expiry, 
    u.telegram_phone, 
    u.telegram_username, 
    u.created_at, 
    u.status, 
    u.bio, 
    u.country, 
    u.region, 
    u.city, 
    u.profile_img, 
    u.points,
    m.memory_id,
    m.img_name AS memory_img, 
    m.description AS memory_description,
    s.skill_id,
    s.description AS skill_description,
    s.name AS skill_name,  -- Include skill name here
    s.hours,
    s.taught_count AS skill_taught,
    l.learnt_count, 
    l.taught_count,
    t.tag_id,
    t.tag
FROM user u
LEFT JOIN memory m ON u.user_id = m.user_id
LEFT JOIN skill s ON u.user_id = s.user_id  -- Direct join with skill table
LEFT JOIN tag t ON s.skill_id = t.skill_id
LEFT JOIN log l ON u.user_id = l.user_id
WHERE u.user_id = ?
ORDER BY u.user_id;

    ";

    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $user = null;
    $skills = [];
    $memories = [];

    while ($row = $result->fetch_assoc()) {
        $userId = $row['user_id'];
        $skillId = $row['skill_id'];
        $tagId = $row['tag_id'];

        // Initialize user data if not already set
        if ($user === null) {
            $user = [
                "user_id" => $row["user_id"],
                "username" => $row["username"],
                "email" => $row["email"],
                "password" => $row["password"],
                "otp" => $row["otp"],
                "otp_expiry" => $row["otp_expiry"],
                "telegram_phone" => $row["telegram_phone"],
                "telegram_username" => $row["telegram_username"],
                "created_at" => $row["created_at"],
                "status" => $row["status"],
                "bio" => $row["bio"],
                "country" => $row["country"],
                "region" => $row["region"],
                "city" => $row["city"],
                "profile_img" => $row["profile_img"],
                "skill_learnt" => $row["learnt_count"],
                "skill_taught" => $row["taught_count"],
                "points" => $row["points"],
                "memories" => [],
                "skills" => []
            ];
        }

        // Add memory if exists and not already added
        if (!empty($row['memory_img']) && !in_array($row['memory_img'], array_column($memories, 'img_name'))) {
            $memories[] = [
                "memory_id" => $row["memory_id"],
                "img_name" => $row["memory_img"],
                "description" => $row["memory_description"],
                "name" => $row["skill_name"] // Include skill name
            ];
        }

        // Add skill if not already present
        if (!empty($row['skill_description']) && !isset($skills[$skillId])) {
            $skills[$skillId] = [
                "skill_id" => $skillId,
                "description" => $row["skill_description"],
                "name" => $row["skill_name"],
                "hours" => $row["hours"],
                "skill_taught" => $row["skill_taught"],
                "tags" => [] // Initialize tags as an empty array
            ];
        }

        // Add tags to the corresponding skill
        if (!empty($tagId) && isset($skills[$skillId])) {
            $tagName = json_decode($row['tag'], true);
            if (!is_array($tagName)) {
                $tagName = [$row['tag']]; // Ensure it's an array
            }

            // Add tags to the skill's tags array
            foreach ($tagName as $singleTag) {
                // Check if the tag is already added to avoid duplicates
                $tagExists = false;
                foreach ($skills[$skillId]['tags'] as $existingTag) {
                    if ($existingTag['tag'] === $singleTag) {
                        $tagExists = true;
                        break;
                    }
                }

                if (!$tagExists) {
                    $skills[$skillId]['tags'][] = [
                        "tag_id" => $tagId,
                        "tag" => $singleTag
                    ];
                }
            }
        }
    }
    // Attach memories and skills to the user object
    if ($user !== null) {
        $user["memories"] = $memories;
        $user["skills"] = array_values($skills); // Reset array index for skills
    }

    // Return the structured response
    echo json_encode([
        "status" => "no_change",
        "message" => "No changes detected",
        "user" => $user
    ]);
} else {
    // Execute update query
    $updateQuery = "UPDATE user SET " . implode(", ", $updates) . " WHERE user_id = ?";
    $params[] = $user_id;
    $types .= "i";

    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        // Fetch updated user data
        $userQuery = "
           SELECT 
    u.user_id, 
    u.username, 
    u.email, 
    u.password, 
    u.otp, 
    u.otp_expiry, 
    u.telegram_phone, 
    u.telegram_username, 
    u.created_at, 
    u.status, 
    u.bio, 
    u.country, 
    u.region, 
    u.city, 
    u.profile_img, 
    u.points,
    m.memory_id,
    m.img_name AS memory_img, 
    m.description AS memory_description,
    s.skill_id,
    s.description AS skill_description,
    s.name AS skill_name,  -- Include skill name here
    s.hours,
    s.taught_count AS skill_taught,
    l.learnt_count, 
    l.taught_count,
    t.tag_id,
    t.tag
FROM user u
LEFT JOIN memory m ON u.user_id = m.user_id
LEFT JOIN skill s ON u.user_id = s.user_id  -- Direct join with skill table
LEFT JOIN tag t ON s.skill_id = t.skill_id
LEFT JOIN log l ON u.user_id = l.user_id
WHERE u.user_id = ?
ORDER BY u.user_id;

        ";

        $stmt = $conn->prepare($userQuery);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $user = null;
        $skills = [];
        $memories = [];

        while ($row = $result->fetch_assoc()) {
            $userId = $row['user_id'];
            $skillId = $row['skill_id'];
            $tagId = $row['tag_id'];

            // Initialize user data if not already set
            if ($user === null) {
                $user = [
                    "user_id" => $row["user_id"],
                    "username" => $row["username"],
                    "email" => $row["email"],
                    "password" => $row["password"],
                    "otp" => $row["otp"],
                    "otp_expiry" => $row["otp_expiry"],
                    "telegram_phone" => $row["telegram_phone"],
                    "telegram_username" => $row["telegram_username"],
                    "created_at" => $row["created_at"],
                    "status" => $row["status"],
                    "bio" => $row["bio"],
                    "country" => $row["country"],
                    "region" => $row["region"],
                    "city" => $row["city"],
                    "profile_img" => $row["profile_img"],
                    "skill_learnt" => $row["learnt_count"],
                    "skill_taught" => $row["taught_count"],
                    "points" => $row["points"],
                    "memories" => [],
                    "skills" => []
                ];
            }

            // Add memory if exists and not already added
            if (!empty($row['memory_img']) && !in_array($row['memory_img'], array_column($memories, 'img_name'))) {
                $memories[] = [
                    "memory_id" => $row["memory_id"],
                    "img_name" => $row["memory_img"],
                    "description" => $row["memory_description"],
                    "name" => $row["skill_name"] // Include skill name
                ];
            }

            // Add skill if not already present
            if (!empty($row['skill_description']) && !isset($skills[$skillId])) {
                $skills[$skillId] = [
                    "skill_id" => $skillId,
                    "description" => $row["skill_description"],
                    "name" => $row["skill_name"],
                    "hours" => $row["hours"],
                    "skill_taught" => $row["skill_taught"],
                    "tags" => [] // Initialize tags as an empty array
                ];
            }

            // Add tags to the corresponding skill
            if (!empty($tagId) && isset($skills[$skillId])) {
                $tagName = json_decode($row['tag'], true);
                if (!is_array($tagName)) {
                    $tagName = [$row['tag']]; // Ensure it's an array
                }

                // Add tags to the skill's tags array
                foreach ($tagName as $singleTag) {
                    if (!in_array($singleTag, $skills[$skillId]['tags'])) {
                        $skills[$skillId]['tags'][] = $singleTag;
                    }
                }
            }
        }

        // Attach memories and skills to the user object
        if ($user !== null) {
            $user["memories"] = $memories;
            $user["skills"] = array_values($skills); // Reset array index for skills
        }

        // Return the structured response
        echo json_encode([
            "status" => "success",
            "message" => "Profile updated successfully",
            "user" => $user
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update profile", "error" => $stmt->error]);
    }
    $stmt->close();
}
