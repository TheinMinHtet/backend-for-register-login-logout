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
function getTokenFromHeader()
{
    $headers = getallheaders();
    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'authorization') {
            return str_replace('Bearer ', '', $value);
        }
    }
    return null;
}

// Function to verify the JWT
function verifyJWT($token)
{
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['accept_request'])) {
    // Get JSON data from the request body
    $data = json_decode(file_get_contents('php://input'), true);
    $teacher_id = $data['teacher_id'] ?? null;
    $skill_id = $data['skill_id'] ?? null;

    // Validate required fields
    if (!$teacher_id || !$skill_id) {
        echo json_encode(["status" => "error", "message" => "Teacher ID and Skill ID are required"]);
        exit();
    }

    // Use the authorized user's ID as the learner_id
    $learner_id = $user_id;

    // Check if the learner has at least 10 points
    $pointsQuery = "SELECT points FROM user WHERE user_id = ?";
    $stmt = $conn->prepare($pointsQuery);
    $stmt->bind_param("i", $learner_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Learner not found"]);
        exit();
    }

    $learner_data = $result->fetch_assoc();
    $learner_points = $learner_data['points'];

    if ($learner_points < 10) {
        echo json_encode(["status" => "error", "message" => "Learner must have at least 10 points to request a skill"]);
        exit();
    }

    // Fetch the hours and skill name for the given skill_id from the skill table
    $query = "SELECT hours, name FROM skill WHERE skill_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $skill_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Skill not found"]);
        exit();
    }

    $skill_data = $result->fetch_assoc();
    $hours = $skill_data['hours'];
    $skill_name = $skill_data['name'];

    // Fetch the teacher's username for the notification message
    $teacherNameQuery = "SELECT username FROM user WHERE user_id = ?";
    $teacherStmt = $conn->prepare($teacherNameQuery);
    $teacherStmt->bind_param("i", $teacher_id);
    $teacherStmt->execute();
    $teacherResult = $teacherStmt->get_result();
    $teacherData = $teacherResult->fetch_assoc();
    $teacher_name = $teacherData ? $teacherData['username'] : 'Teacher';

    // Fetch the learner's username for the notification message
    $learnerNameQuery = "SELECT username FROM user WHERE user_id = ?";
    $learnerStmt = $conn->prepare($learnerNameQuery);
    $learnerStmt->bind_param("i", $learner_id);
    $learnerStmt->execute();
    $learnerResult = $learnerStmt->get_result();
    $learnerData = $learnerResult->fetch_assoc();
    $learner_name = $learnerData ? $learnerData['username'] : 'Learner';

    // Insert into learner_teacher table with hours from the skill table
    $insertQuery = "INSERT INTO learner_teacher (user_id, user_id2, skill_id, hours) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("iiii", $teacher_id, $learner_id, $skill_id, $hours);

    if ($stmt->execute()) {
        // Retrieve the auto-generated request_id
        $request_id = $stmt->insert_id;

        // Prepare notification messages
        $learner_notification = "Your request to learn '{$skill_name}' has been sent to {$teacher_name}.";
        $teacher_notification = "You have received a request from {$learner_name} to teach '{$skill_name}'.";

        // Store notifications in the database
        $insertNotificationQuery = "INSERT INTO notification (user_id, description) VALUES (?, ?)";
        $notificationStmt = $conn->prepare($insertNotificationQuery);

        // For the learner
        $notificationStmt->bind_param("is", $learner_id, $learner_notification);
        $notificationStmt->execute();

        // For the teacher
        $notificationStmt->bind_param("is", $teacher_id, $teacher_notification);
        $notificationStmt->execute();

        // Prepare response data with actual names, skill, and request_id
        $response = [
            "status" => "success",
            "message" => "Request sent successfully.",
            "request_id" => $request_id, // Include the request_id in the response
            "notifications" => [
                "learner_notification" => $learner_notification,
                "teacher_notification" => $teacher_notification
            ]
        ];

        // Send the combined response
        echo json_encode($response);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to insert data"]);
    }

    // Close all prepared statements
    $stmt->close();
    $teacherStmt->close();
    $learnerStmt->close();
    $notificationStmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if the request is for notifications
    if (isset($_GET['noti_info'])) {
        // Get user_id from query params if provided
        $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

        // Fetch notifications for a specific user or all users
        if ($user_id) {
            $query = "SELECT * FROM notification WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $user_id);
        } else {
            $query = "SELECT * FROM notification";
            $stmt = $conn->prepare($query);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }

        $stmt->close();

        // Return the notifications
        if (!empty($notifications)) {
            echo json_encode(["status" => "success", "notifications" => $notifications]);
        } else {
            echo json_encode(["status" => "error", "message" => "No notifications found"]);
        }
    }
    // Check if the request is for pending requests
    else {
        // Get user_id from query params if provided
        $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

        // Fetch pending requests for a specific user or all users
        if ($user_id) {
            $query = "SELECT * FROM learner_teacher WHERE user_id = ? AND accept = false";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $user_id);
        } else {
            $query = "SELECT * FROM learner_teacher WHERE accept = false";
            $stmt = $conn->prepare($query);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $pendingRequests = [];
        while ($row = $result->fetch_assoc()) {
            $pendingRequests[] = $row;
        }

        $stmt->close();

        // Return the pending requests
        if (!empty($pendingRequests)) {
            echo json_encode(["status" => "success", "pending_requests" => $pendingRequests]);
        } else {
            echo json_encode(["status" => "error", "message" => "No pending requests found"]);
        }
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

        // Prepare notification messages
        if ($accept) {
            $learner_notification = "Your request to learn '{$skillName}' has been accepted by '{$teacherName}'.";
            $teacher_notification = "You have accepted '{$learnerName}'s request to teach '{$skillName}'.";

            $updateStatusQuery = "UPDATE user SET status = 'busy' WHERE user_id IN (?, ?)";
            $statusStmt = $conn->prepare($updateStatusQuery);
            $statusStmt->bind_param("ii", $learner_id, $teacher_id);
            $statusStmt->execute();
            $statusStmt->close();
        } else {
            $learner_notification = "Your request to learn '{$skillName}' has been denied by '{$teacherName}'.";
            $teacher_notification = "You have denied '{$learnerName}'s request to teach '{$skillName}'.";

            $updateStatusQuery = "UPDATE user SET status = 'available' WHERE user_id IN (?, ?)";
            $statusStmt = $conn->prepare($updateStatusQuery);
            $statusStmt->bind_param("ii", $learner_id, $teacher_id);
            $statusStmt->execute();
            $statusStmt->close();
        }

        // Store notifications in the database
        $insertNotificationQuery = "INSERT INTO notification (user_id, description) VALUES (?, ?)";
        $notificationStmt = $conn->prepare($insertNotificationQuery);

        // For the learner
        $notificationStmt->bind_param("is", $learner_id, $learner_notification);
        $notificationStmt->execute();

        // For the teacher
        $notificationStmt->bind_param("is", $teacher_id, $teacher_notification);
        $notificationStmt->execute();

        // Prepare response data with actual names and skill
        $response = [
            "status" => "success",
            "message" => $accept ? "Request accepted. Notifications sent." : "Request denied.",
            "notifications" => [
                "learner_notification" => $learner_notification,
                "teacher_notification" => $teacher_notification
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
    $notificationStmt->close();
}
