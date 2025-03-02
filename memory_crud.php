<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require 'vendor/autoload.php';
require "database_connection.php";
define('JWT_SECRET', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

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

// Get & verify token
$token = getTokenFromHeader();
if (!$token) {
    echo json_encode(["status" => "error", "message" => "Token missing"]);
    exit();
}
$decoded = verifyJWT($token);
if (!$decoded) {
    echo json_encode(["status" => "error", "message" => "Invalid or expired token"]);
    exit();
}
$user_id = $decoded->user_id;

// Extract the endpoint from the request URI
$request_uri = $_SERVER['REQUEST_URI'];
$endpoint = parse_url($request_uri, PHP_URL_PATH); // Extract the path
$endpoint = str_replace('/skillSwap/skill-swap/memory_crud.php', '', $endpoint); // Remove the base path

// ✅ **Handle Memory Upload (multipart/form-data)**
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $endpoint === '/upload') {
    $description = trim($_POST['description'] ?? '');
    $skill_id = isset($_POST['skill_id']) ? intval($_POST['skill_id']) : null; // Allow NULL

    if (empty($description) || !isset($_FILES['image'])) {
        echo json_encode(["status" => "error", "message" => "Description and image are required"]);
        exit();
    }

    // Validate image file
    $image = $_FILES['image'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $fileExtension = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));

    if (!in_array($fileExtension, $allowedExtensions)) {
        echo json_encode(["status" => "error", "message" => "Invalid image format. Allowed formats: jpg, jpeg, png, gif"]);
        exit();
    }

    // Upload image
    $imagePath = "uploads/memory/memory_" . $user_id . "_" . time() . "." . $fileExtension;

    if (!move_uploaded_file($image['tmp_name'], $imagePath)) {
        echo json_encode(["status" => "error", "message" => "Failed to upload image"]);
        exit();
    }

    // Insert memory
    $memoryQuery = "INSERT INTO memory (user_id, skill_id, img_name, description) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($memoryQuery);

    // Bind parameters, allowing skill_id to be NULL
    if ($skill_id === null) {
        $stmt->bind_param("iiss", $user_id, $skill_id, $imagePath, $description);
    } else {
        $stmt->bind_param("iiss", $user_id, $skill_id, $imagePath, $description);
    }

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Memory added successfully", "image" => $imagePath]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to insert memory"]);
    }
    exit();
}

// ✅ **Handle Fetching a Particular Memory**
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['memory_id'])) {
    $memory_id = intval($_GET['memory_id']);

    if ($memory_id <= 0) {
        echo json_encode(["status" => "error", "message" => "Invalid memory ID"]);
        exit();
    }

    // Fetch the memory
    $memoryQuery = "SELECT * FROM memory WHERE memory_id = ?";
    $stmt = $conn->prepare($memoryQuery);
    $stmt->bind_param("i", $memory_id);
    $stmt->execute();
    $memoryResult = $stmt->get_result();

    if ($memoryResult->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Memory not found"]);
        exit();
    }

    $memory = $memoryResult->fetch_assoc();
    $is_owner = ($memory['user_id'] == $user_id);

    $response = [
        "memory" => $memory,
        "can_edit" => $is_owner
    ];

    echo json_encode($response, JSON_PRETTY_PRINT);
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'GET') {



    // Fetch the memory
    $memoryQuery = "SELECT * FROM memory WHERE user_id = ?";
$stmt = $conn->prepare($memoryQuery);
$stmt->bind_param("i", $user_id);  // Assuming user_id is an integer
$stmt->execute();
$memoryResult = $stmt->get_result();

if ($memoryResult->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "No memories found"]);
    exit();
}

// Fetch all memories instead of a single row
$memories = $memoryResult->fetch_all(MYSQLI_ASSOC);

$response = [
    "memories" => $memories,
];

echo json_encode($response, JSON_PRETTY_PRINT);
exit();
}

// ✅ **Handle Memory Edit (Only by Owner)** 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $endpoint === '/edit') {
    // Debugging: Log incoming data
    error_log("Memory ID: " . ($_POST['memory_id'] ?? 'NULL'));
    error_log("Description: " . ($_POST['description'] ?? 'NULL'));
    error_log("Image: " . ($_FILES['image']['name'] ?? 'NULL'));

    $memory_id = intval($_POST['memory_id'] ?? 0);
    $new_description = trim($_POST['description'] ?? '');
    $new_image = $_FILES['image'] ?? null;

    // Check if memory_id is provided (for updates)
    if ($memory_id > 0) {
        // Check ownership
        $checkQuery = "SELECT img_name, user_id FROM memory WHERE memory_id = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("i", $memory_id);
        $stmt->execute();
        $stmt->bind_result($old_img_name, $owner_id);
        $stmt->fetch();
        $stmt->close();

        if ($owner_id != $user_id) {
            echo json_encode(["status" => "error", "message" => "Unauthorized"]);
            exit();
        }

        // Handle image update
        $new_image_path = $old_img_name; // Default to old image path
        if ($new_image && $new_image['error'] === UPLOAD_ERR_OK) {
            // Validate new image
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            $fileExtension = strtolower(pathinfo($new_image['name'], PATHINFO_EXTENSION));

            if (!in_array($fileExtension, $allowedExtensions)) {
                echo json_encode(["status" => "error", "message" => "Invalid image format. Allowed formats: jpg, jpeg, png, gif"]);
                exit();
            }

            // Upload new image
            $new_image_path = "uploads/memory/memory_" . $user_id . "_" . time() . "." . $fileExtension;
            if (!move_uploaded_file($new_image['tmp_name'], $new_image_path)) {
                echo json_encode(["status" => "error", "message" => "Failed to upload new image"]);
                exit();
            }

            // Delete old image
            if (file_exists($old_img_name)) {
                unlink($old_img_name);
            }
        }

        // Update memory
        $updateQuery = "UPDATE memory SET description = ?, img_name = ? WHERE memory_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssi", $new_description, $new_image_path, $memory_id);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Memory updated successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update memory"]);
        }
        exit();
    } else {
        echo json_encode(["status" => "error", "message" => "Memory ID is required for editing"]);
        exit();
    }
}

// ✅ **Handle Memory Deletion (Only by Owner)**
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $_DELETE);
    $memory_id = intval($_DELETE['memory_id'] ?? 0);

    if ($memory_id <= 0) {
        echo json_encode(["status" => "error", "message" => "Memory ID is required"]);
        exit();
    }

    // Check ownership
    $checkQuery = "SELECT img_name, user_id FROM memory WHERE memory_id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("i", $memory_id);
    $stmt->execute();
    $stmt->bind_result($img_name, $owner_id);
    $stmt->fetch();
    $stmt->close();

    if ($owner_id != $user_id) {
        echo json_encode(["status" => "error", "message" => "Unauthorized"]);
        exit();
    }

    // Delete image file
    if (file_exists($img_name)) {
        if (!unlink($img_name)) {
            echo json_encode(["status" => "error", "message" => "Failed to delete image file"]);
            exit();
        }
    }

    // Delete memory
    $deleteQuery = "DELETE FROM memory WHERE memory_id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $memory_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Memory deleted successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to delete memory"]);
    }
    exit();
}

// ✅ **Handle Session Completion Logic**
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $endpoint === '/complete_session') {
    $data = json_decode(file_get_contents('php://input'), true);
    $skill_id = $data['skill_id'] ?? null;

    if (!$skill_id) {
        echo json_encode(["status" => "error", "message" => "Skill ID is required"]);
        exit();
    }

    // Step 1: Get Learner and Teacher from learner_teacher table
    $query = "
        SELECT lt.learner_id, lt.teacher_id, lt.start_time, s.hours 
        FROM learner_teacher lt
        JOIN skill s ON lt.skill_id = s.skill_id
        WHERE lt.skill_id = ?
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $skill_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $session = $result->fetch_assoc();

    if (!$session) {
        echo json_encode(["status" => "error", "message" => "No active session found"]);
        exit();
    }

    $learner_id = $session['learner_id'];
    $teacher_id = $session['teacher_id'];
    $start_time = strtotime($session['start_time']);
    $total_hours = (int) $session['hours'];

    // Step 2: Get Memory Count for Learner & Teacher (Filter by Time Bound)
    $query = "SELECT user_id, created_at FROM memory WHERE skill_id = ? AND (user_id = ? OR user_id = ?) ORDER BY created_at ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $skill_id, $learner_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $memories = [];
    while ($row = $result->fetch_assoc()) {
        $memories[$row['user_id']][] = strtotime($row['created_at']);
    }

    $learner_days = countValidDays($memories[$learner_id] ?? [], $start_time);
    $teacher_days = countValidDays($memories[$teacher_id] ?? [], $start_time);

    $completed_days = min($learner_days, $teacher_days);

    // Step 3: Check if Session is Complete
    if ($completed_days >= $total_hours) {
        // Update user status to "completed"
        $updateQuery = "UPDATE user SET status = 'completed' WHERE user_id IN (?, ?)";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ii", $learner_id, $teacher_id);
        $stmt->execute();

        // Delete session from learner_teacher table
        $deleteQuery = "DELETE FROM learner_teacher WHERE skill_id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("i", $skill_id);
        $stmt->execute();

        // Send Completion Notifications
        $response = [
            "status" => "success",
            "message" => "Session completed",
            "notifications" => [
                "learner_notification" => "Your skill session for {$skill_id} is completed!",
                "teacher_notification" => "Your teaching session for {$skill_id} is completed!"
            ]
        ];
    } else {
        // Session ongoing, return streak info
        $response = [
            "status" => "success",
            "message" => "Session ongoing",
            "learner_days" => $learner_days,
            "teacher_days" => $teacher_days,
            "remaining_hours" => $total_hours - $completed_days
        ];
    }

    echo json_encode($response);
}

// Function to count valid days of memory streaks
function countValidDays($timestamps, $start_time) {
    if (empty($timestamps)) {
        return 0;
    }

    $day_count = 0;
    $current_day_start = $start_time;
    $current_day_end = strtotime("+1 day", $current_day_start);

    foreach ($timestamps as $timestamp) {
        if ($timestamp < $start_time) continue;

        if ($timestamp >= $current_day_start && $timestamp <= $current_day_end) {
            continue;
        } else {
            $day_count++;
            $current_day_start = strtotime("+1 day", $current_day_end);
            $current_day_end = strtotime("+1 day", $current_day_start);
        }
    }

    return $day_count;
}
?>