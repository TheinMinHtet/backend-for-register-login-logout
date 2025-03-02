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

// Function to count consecutive streak days
function countStreakDays($timestamps, $start_time) {
    if (empty($timestamps)) {
        return 0;
    }

    sort($timestamps);
    $streak_count = 1;
    $current_day_start = $start_time;

    for ($i = 1; $i < count($timestamps); $i++) {
        $previous_day = strtotime("+" . ($streak_count) . " day", $start_time);
        if ($timestamps[$i] >= $previous_day && $timestamps[$i] < strtotime("+1 day", $previous_day)) {
            $streak_count++;
        } else {
            break; // Streak broken
        }
    }
    return $streak_count;
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $endpoint === '/session') {
    $data = json_decode(file_get_contents('php://input'), true);
    $skill_id = $data['skill_id'] ?? null;
    $user_id = $data['user_id'] ?? null;

    if (!$skill_id || !$user_id) {
        echo json_encode(["status" => "error", "message" => "Skill ID and User ID are required"]);
        exit();
    }

    // Step 1: Get Session Details and Check if Session is Active
    $query = "
        SELECT lt.user_id, lt.user_id2, lt.start_time, lt.accept, s.hours 
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

    // Check if both learner and teacher have accepted the session
    if ($session['accept'] != 1) {
        echo json_encode(["status" => "error", "message" => "Session is not active"]);
        exit();
    }

    $learner_id = $session['user_id'];
    $teacher_id = $session['user_id2'];
    $start_time = strtotime($session['start_time']); // Start time of the session
    $total_hours = (int) $session['hours']; // Total streak days required

    // Step 2: Get Memory Count for Learner & Teacher
    $query = "SELECT user_id, created_at FROM memory WHERE skill_id = ? AND (user_id = ? OR user_id = ?) ORDER BY created_at ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $skill_id, $learner_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $memories = [];
    while ($row = $result->fetch_assoc()) {
        $memories[$row['user_id']][] = strtotime($row['created_at']);
    }

    $learner_timestamps = $memories[$learner_id] ?? [];
    $teacher_timestamps = $memories[$teacher_id] ?? [];

    // Step 3: Calculate Streak Days for Learner and Teacher
    $learner_streak = countStreakDays($learner_timestamps, $start_time);
    $teacher_streak = countStreakDays($teacher_timestamps, $start_time);

    // Step 4: Calculate Remaining Streak Days
    $completed_streak_days = min($learner_streak, $teacher_streak);
    $remaining_streak_days = max(0, $total_hours - $completed_streak_days);

    // Step 5: Calculate Next Streak Start Time
    $current_time = time(); // Current timestamp
    $next_streak_start_time = $start_time + ($completed_streak_days * 24 * 3600); // Start of the next streak day

    // If the next streak start time is in the past, move to the next 24-hour window
    while ($next_streak_start_time <= $current_time) {
        $next_streak_start_time += 24 * 3600; // Add 24 hours until it's in the future
    }

    // Format the next streak start time as a human-readable string
    $next_streak_start_formatted = date("Y-m-d H:i:s", $next_streak_start_time);

    // Step 6: Handle Session Status Based on Streaks
    if (empty($learner_timestamps) && empty($teacher_timestamps)) {
        // No memories uploaded yet, session is ongoing
        $response = [
            "status" => "success",
            "message" => "Session ongoing (no memories uploaded yet)",
            "learner_streak" => 0,
            "teacher_streak" => 0,
            "remaining_streak_days" => $remaining_streak_days,
            "next_streak_start_time" => $next_streak_start_formatted
        ];
    } elseif ($learner_streak == 0 && $teacher_streak == 0 && (!empty($learner_timestamps) || !empty($teacher_timestamps))) {
        // Both streaks are 0 and at least one memory has been uploaded in the past
        // Streak is broken, end the session
        $deleteQuery = "DELETE FROM learner_teacher WHERE skill_id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("i", $skill_id);
        $stmt->execute();

        // Send Streak Loss Notifications
        $response = [
            "status" => "error",
            "message" => "Session ended due to streak loss",
            "learner_streak" => $learner_streak,
            "teacher_streak" => $teacher_streak,
            "notifications" => [
                "learner_notification" => "Your skill session for {$skill_id} has ended due to streak loss.",
                "teacher_notification" => "Your teaching session for {$skill_id} has ended due to streak loss."
            ]
        ];
    } elseif ($completed_streak_days >= $total_hours) {
        // Session completed
        $updateQuery = "UPDATE user SET status = 'completed' WHERE user_id IN (?, ?)";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ii", $learner_id, $teacher_id);
        $stmt->execute();

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
        // Session ongoing
        $response = [
            "status" => "success",
            "message" => "Session ongoing",
            "learner_streak" => $learner_streak,
            "teacher_streak" => $teacher_streak,
            "remaining_streak_days" => $remaining_streak_days,
            "next_streak_start_time" => $next_streak_start_formatted
        ];
    }

    echo json_encode($response);
}