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
    
            // Handle JSON Data from frontend
            $data = json_decode(file_get_contents('php://input'), true);
            $title = $data['title'] ?? null;
            $description = $data['description'] ?? null;
            $tags = $data['tags'] ?? [];

            if ($title && $description && $tags) {  // Ensure all three fields are provided
                // Insert the skill into the skill table
                $skillQuery = "INSERT INTO skill (user_id, name, description) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($skillQuery);
                $stmt->bind_param("iss", $user_id, $title, $description);
            
                if ($stmt->execute()) {
                    $skill_id = $stmt->insert_id;
                    $stmt->close();
            
                    // Update taught count for the skill
                    $updateskillQuery = "UPDATE skill SET taught_count = taught_count + 1 WHERE user_id = ?";
                    $stmt = $conn->prepare($updateskillQuery);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $stmt->close();
            
                    $checkLogQuery = "SELECT COUNT(*) FROM log WHERE user_id = ?";
                    $stmt = $conn->prepare($checkLogQuery);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $stmt->bind_result($count);
                    $stmt->fetch();
                    $stmt->close();

                    if ($count > 0) {
                        // If log exists, update taught_count
                        $updateLogQuery = "UPDATE log SET taught_count = taught_count + 1 WHERE user_id = ?";
                        $stmt = $conn->prepare($updateLogQuery);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $stmt->close();
                    } else {
                        // If log doesn't exist, insert a new record
                        $insertLogQuery = "INSERT INTO log (user_id, taught_count) VALUES (?, 1)";
                        $stmt = $conn->prepare($insertLogQuery);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $stmt->close();
                    }
            
                    // Insert tag into the tag table (single tag)
                    $tagJson = json_encode($tags);
                    $tagQuery = "INSERT INTO tag (skill_id, tag) VALUES (?, ?)";
                    $stmt = $conn->prepare($tagQuery);
                    $stmt->bind_param("is", $skill_id, $tagJson);  // Only one tag to insert
                    $stmt->execute();
                    $stmt->close();
            
                    echo json_encode(["status" => "success", "message" => "Skill and tag added successfully"]);
                } else {
                    echo json_encode(["status" => "error", "message" => "Failed to insert skill"]);
                }
            } else {
                echo json_encode(["status" => "error", "message" => "Missing title, description, or tag"]);
            }
            

        } else {
            echo json_encode(["status" => "error", "message" => "Invalid token"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Token missing"]);
    }
    
    $skillQuery = "SELECT skill.name, skill.description, skill.taught_count FROM skill";
    $skillResult = $conn->query($skillQuery);
    
    $skill = [];
    if ($skillResult->num_rows > 0) {
        while ($row = $skillResult->fetch_assoc()) {
            $skill[] = $row;
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

    $tagQuery = "SELECT * FROM tag";
    $tagResult = $conn->query($tagQuery);
    
    $tag = [];
    if ($tagResult->num_rows > 0) {
        while ($row = $tagResult->fetch_assoc()) {
            $tag[] = $row;
        }
    }
    
    $response = [
        "skill" => $skill,
        "user" => $user,
        "tag" => $tag
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
?>