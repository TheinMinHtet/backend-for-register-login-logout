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

// Handle file upload for profile image
$profile_image = null;
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

    // Set profile image path
    $profile_image = $uploadDir . "user_" . $user_id . "." . $fileExtension;
    move_uploaded_file($_FILES['profile_image']['tmp_name'], $profile_image);
}

// Update user profile (excluding telegram_username and telegram_phone)
$city = $_POST['city'] ?? null;
$region = $_POST['region'] ?? null;
$country = $_POST['country'] ?? null;
$bio = $_POST['bio'] ?? null;
$status = $_POST['status'] ?? null;

// If any details are provided, update the user table
if ($city || $region || $country || $bio || $status || $profile_image) {
    $updateQuery = "UPDATE user SET 
        city = IFNULL(?, city), 
        region = IFNULL(?, region), 
        country = IFNULL(?, country), 
        bio = IFNULL(?, bio), 
        status = IFNULL(?, status),
        profile_img = IFNULL(?, profile_img)
        WHERE user_id = ?";

    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ssssssi", $city, $region, $country, $bio, $status, $profile_image, $user_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Profile updated successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update profile"]);
    }
    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "No data to update"]);
}

$userQuery = "
    SELECT 
        u.user_id, u.username, u.email, u.password, u.otp, u.otp_expiry, 
        u.telegram_phone, u.telegram_username, u.created_at, u.status, 
        u.bio, u.country, u.region, u.city, u.profile_img, u.points,
        m.img_name AS memory_img, m.description AS memory_description,
        s.img_name AS skill_img, s.description AS skill_description
    FROM user u
    LEFT JOIN memory m ON u.user_id = m.user_id
    LEFT JOIN skill s ON u.user_id = s.user_id  -- Assuming skill table links directly to user_id
    WHERE u.user_id = ? 
    ORDER BY u.user_id
";

$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $user_id); // Bind user_id parameter
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $userId = $row['user_id'];

    // Initialize user data if not already set
    if (!isset($users[$userId])) {
        $users[$userId] = [
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
            "points" => $row["points"],
            "memories" => [],
            "skills" => [] 
        ];
    }

    if (!empty($row['memory_img']) && !in_array($row['memory_img'], array_column($users[$userId]['memories'], 'img_name'))) {
        $users[$userId]["memories"][] = [
            "img_name" => $row["memory_img"],
            "description" => $row["memory_description"]
        ];
    }

    if (!empty($row['skill_img']) && !in_array($row['skill_img'], array_column($users[$userId]['skills'], 'img_name'))) {
        $users[$userId]["skills"][] = [
            "img_name" => $row["skill_img"],
            "description" => $row["skill_description"]
        ];
    }
}

// Reset array index
$users = array_values($users);

// Return the structured response
echo json_encode(["user" => $users], JSON_PRETTY_PRINT);
?>
