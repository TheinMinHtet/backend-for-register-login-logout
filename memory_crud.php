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

        // Get skill_id for the user
        $skill_id = null;
        $skillQuery = "SELECT skill_id FROM skill WHERE user_id = ? LIMIT 1";
        $stmt = $conn->prepare($skillQuery);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($skill_id);
        $stmt->fetch();
        $stmt->close();

        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

            if (!in_array($fileExtension, $allowedExtensions)) {
                echo json_encode(["status" => "error", "message" => "Invalid image format"]);
                exit();
            }

            $uploadDir = "uploads/memory/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $imagePath = $uploadDir . "memory_" . $user_id . "_" . time() . "." . $fileExtension;
            move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
        }
        
        $description = $_POST['description'] ?? null;

        if ($imagePath && $description) {
            // Insert memory
            $memoryQuery = "INSERT INTO memory (user_id, skill_id, img_name, description) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($memoryQuery);
            $stmt->bind_param("iiss", $user_id, $skill_id, $imagePath, $description);

            if ($stmt->execute()) {
                // Success: Increase learnt_count and taught_count
                $stmt->close();

                // Check if log entry exists for this user
                $logCheckQuery = "SELECT log_id FROM log WHERE user_id = ?";
                $stmt = $conn->prepare($logCheckQuery);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->store_result();
                $logExists = $stmt->num_rows > 0;
                $stmt->close();

                if ($logExists) {
                    $updateLogQuery = "UPDATE log SET learnt_count = learnt_count + 1 WHERE user_id = ?";
                    $stmt = $conn->prepare($updateLogQuery);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    $insertLogQuery = "INSERT INTO log (user_id, learnt_count) VALUES (?,  1)";
                    $stmt = $conn->prepare($insertLogQuery);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $stmt->close();
                }

                $updatePointsQuery = "UPDATE user SET points = points + 10 WHERE user_id = ?";
                $stmt = $conn->prepare($updatePointsQuery);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();

                echo json_encode(["status" => "success", "message" => "Image and description added successfully, log updated"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to insert memory"]);
            }

        } else {
            echo json_encode(["status" => "error", "message" => "Missing image or description"]);
        }

    } else {
        echo json_encode(["status" => "error", "message" => "Invalid token"]);
    }

} else {
    echo json_encode(["status" => "error", "message" => "Token missing"]);
}

// Fetch all memory records
$memoryQuery = "SELECT * FROM memory";
$memoryResult = $conn->query($memoryQuery);

$memory = [];
if ($memoryResult->num_rows > 0) {
    while ($row = $memoryResult->fetch_assoc()) {
        $memory[] = $row;
    }
}

// Fetch all user records
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
?>
