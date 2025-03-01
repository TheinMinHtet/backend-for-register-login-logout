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

// Function to extract token from the Authorization header
function getTokenFromHeader() {
    $headers = getallheaders();
    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'authorization') {
            return str_replace('Bearer ', '', $value);
        }
    }
    return null;
}

// Function to verify the JWT
function verifyJWT($token) {
    try {
        return JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
    } catch (Exception $e) {
        return null;
    }
}

// Get & verify token once at the beginning
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

// Handle POST request for adding a learner-teacher relationship
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['accept_request'])) {
    
    $data = json_decode(file_get_contents('php://input'), true);

    $learner_id = $data['learner_id'] ?? null;
    $teacher_id = $data['teacher_id'] ?? null;
    $skill_id = $data['skill_id'] ?? null;

    // Validate if all required fields are provided
    if (!$learner_id || !$teacher_id || !$skill_id) {
        echo json_encode(["status" => "error", "message" => "Missing required fields"]);
        exit();
    }

    // Insert data into the learner_teacher table
    $insertQuery = "INSERT INTO learner_teacher (user_id, user_id2, skill_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("iii", $teacher_id, $learner_id, $skill_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Learner and teacher added successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to insert data"]);
    }

    $stmt->close();
}

// Handle GET request for retrieving pending requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get user_id from query params
    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

    if (!$user_id) {
        echo json_encode(["status" => "error", "message" => "User ID is missing"]);
        exit();
    }

    // Query to fetch the pending requests for the teacher (accept = false)
    $query = "SELECT * FROM learner_teacher WHERE user_id = ? AND accept = false";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $pendingRequests = [];

    while ($row = $result->fetch_assoc()) {
        $pendingRequests[] = $row;
    }

    $stmt->close();
    $conn->close();

    // Return the pending requests
    if (count($pendingRequests) > 0) {
        echo json_encode(["status" => "success", "pending_requests" => $pendingRequests]);
    } else {
        echo json_encode(["status" => "error", "message" => "No pending requests found"]);
    }
}

// Handle POST request for accepting or denying a request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['accept_request'])) {
    
    $data = json_decode(file_get_contents('php://input'), true);

    $request_id = $data['request_id'] ?? null;
    $accept = isset($data['accept']) ? $data['accept'] : null;

    // Validate if required fields are provided
    if (!$request_id || !isset($accept)) {
        echo json_encode(["status" => "error", "message" => "Missing required fields"]);
        exit();
    }

    // Get learner_id and teacher_id based on request_id
    $getUserIdsQuery = "SELECT user_id, user_id2, skill_id FROM learner_teacher WHERE request_id = ?";
    $getUserIdsStmt = $conn->prepare($getUserIdsQuery);
    $getUserIdsStmt->bind_param("i", $request_id);
    $getUserIdsStmt->execute();
    $result = $getUserIdsStmt->get_result();
    $userIds = $result->fetch_assoc();

    $teacher_id = $userIds['user_id'] ?? null;
    $learner_id = $userIds['user_id2'] ?? null;
    $skill_id = $userIds['skill_id'] ?? null;

    if (!$learner_id || !$teacher_id || !$skill_id) {
        echo json_encode(["status" => "error", "message" => "Could not retrieve learner or teacher ID"]);
        exit();
    }

    // Now perform the update query as before
    if ($accept) {
        $updateQuery = "
            UPDATE learner_teacher 
            SET accept = true, start_time = CURRENT_TIMESTAMP 
            WHERE request_id = ? AND (user_id = ? OR user_id2 = ?)
        ";
    } else {
        $updateQuery = "
            UPDATE learner_teacher 
            SET accept = false 
            WHERE request_id = ? AND (user_id = ? OR user_id2 = ?)
        ";
    }

    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("iii", $request_id, $teacher_id, $learner_id);

    if ($stmt->execute()) {
        // Fetch the actual names for learner and teacher
        $nameQuery = "SELECT username FROM user WHERE user_id = ?";
        
        $teacherStmt = $conn->prepare($nameQuery);
        $teacherStmt->bind_param("i", $teacher_id);
        $teacherStmt->execute();
        $teacherResult = $teacherStmt->get_result();
        $teacherData = $teacherResult->fetch_assoc();
        $teacherName = $teacherData ? $teacherData['username'] : 'Teacher Not Found';

        $learnerStmt = $conn->prepare($nameQuery);
        $learnerStmt->bind_param("i", $learner_id);
        $learnerStmt->execute();
        $learnerResult = $learnerStmt->get_result();
        $learnerData = $learnerResult->fetch_assoc();
        $learnerName = $learnerData ? $learnerData['username'] : 'Learner Not Found';

        // Fetch the skill name
        $skillQuery = "SELECT name FROM skill WHERE skill_id = ?";
        $skillStmt = $conn->prepare($skillQuery);
        $skillStmt->bind_param("i", $skill_id);
        $skillStmt->execute();
        $skillResult = $skillStmt->get_result();
        $skillData = $skillResult->fetch_assoc();
        $skillName = $skillData ? $skillData['name'] : 'Skill Not Found';

        // Prepare response data with actual names and skill
        $response = [
            "status" => "success",
            "message" => $accept ? "Request accepted. Notifications sent." : "Request denied.",
            "notifications" => [
                "learner_notification" => "Your request to learn '{$skillName}' has been accepted by '{$teacherName}'.",
                "teacher_notification" => "You have accepted '{$learnerName}'s' request to teach '{$skillName}'."
            ]
        ];

        // Send the combined response
        echo json_encode($response);

    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update request status"]);
    }

    $stmt->close();
    $teacherStmt->close();
    $learnerStmt->close();
    $skillStmt->close();
}

?>
