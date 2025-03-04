<?php

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $endpoint === '/upload') {
    $description = trim($_POST['description'] ?? '');
    $skill_id = isset($_POST['skill_id']) ? intval($_POST['skill_id']) : null; // Allow NULL

    // Debugging: Log incoming data
    error_log("Description: " . ($_POST['description'] ?? 'NULL'));
    error_log("Skill ID: " . ($_POST['skill_id'] ?? 'NULL'));
    error_log("Image Name: " . ($_FILES['image']['name'] ?? 'NULL'));
    error_log("Image Size: " . ($_FILES['image']['size'] ?? 'NULL'));
    error_log("Image Temp Path: " . ($_FILES['image']['tmp_name'] ?? 'NULL'));
    error_log("Image Error: " . ($_FILES['image']['error'] ?? 'NULL'));

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

    // Step 1: Check if skill_id exists (if provided)
    if ($skill_id !== null) {
        $checkSkillQuery = "
            SELECT skill_id, accept 
            FROM learner_teacher 
            WHERE skill_id = ? AND (user_id = ? OR user_id2 = ?)
        ";
        $stmt = $conn->prepare($checkSkillQuery);
        $stmt->bind_param("iii", $skill_id, $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(["status" => "error", "message" => "You are not associated with this skill"]);
            exit();
        }

        $row = $result->fetch_assoc();
        $accept_status = $row['accept'];

        // Check if the session is active (accept = 1)
        if ($accept_status != 1) {
            echo json_encode(["status" => "error", "message" => "The session for this skill is not active. Memories cannot be uploaded."]);
            exit();
        }

        // Step 2: Check if the user has already uploaded a memory for this skill today
        $current_date = date('Y-m-d');
        $checkUploadQuery = "SELECT COUNT(*) AS upload_count FROM memory WHERE user_id = ? AND skill_id = ? AND DATE(created_at) = ?";
        $stmt = $conn->prepare($checkUploadQuery);
        $stmt->bind_param("iis", $user_id, $skill_id, $current_date);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $upload_count = $row['upload_count'];

        if ($upload_count >= 1) {
            echo json_encode([
                "status" => "error",
                "message" => "You have already uploaded a memory for this skill today. Please wait until tomorrow."
            ]);
            exit();
        }
    }

    // Step 3: Check Daily Upload Limit for skill_id = null
    if ($skill_id === null) {
        // Get the current date
        $current_date = date('Y-m-d');

        // Count the number of uploads with skill_id = null for the current day
        $countQuery = "SELECT COUNT(*) AS upload_count FROM memory WHERE user_id = ? AND skill_id IS NULL AND DATE(created_at) = ?";
        $stmt = $conn->prepare($countQuery);
        $stmt->bind_param("is", $user_id, $current_date);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $upload_count = $row['upload_count'];

        // If the user has already uploaded 3 times today, return an error
        if ($upload_count >= 3) {
            echo json_encode([
                "status" => "error",
                "message" => "You have reached the daily limit of 3 uploads without a skill. Please try again tomorrow."
            ]);
            exit();
        }
    }

    // Step 4: Upload Image
    $imagePath = "uploads/memory/memory_" . $user_id . "_" . time() . "." . $fileExtension;

    if (!move_uploaded_file($image['tmp_name'], $imagePath)) {
        echo json_encode(["status" => "error", "message" => "Failed to upload image"]);
        exit();
    }

    // Check if skill_id exists in the skill table
    if ($skill_id !== null) {
        $checkSkillQuery = "SELECT skill_id FROM skill WHERE skill_id = ?";
        $stmt = $conn->prepare($checkSkillQuery);
        $stmt->bind_param("i", $skill_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            echo json_encode(["status" => "error", "message" => "Invalid skill ID"]);
            exit();
        }
    }
    // Insert memory

    // Bind parameters, allowing skill_id to be NULL
    if ($skill_id === null) {
<<<<<<< HEAD
        $memoryQuery = "INSERT INTO memory (user_id, img_name, description) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($memoryQuery);
        $stmt->bind_param("iss", $user_id, $imagePath, $description);
=======
        $null_skill_id = null; // Create a variable to hold the null value
        $stmt->bind_param("iiss", $user_id, $null_skill_id, $imagePath, $description);
>>>>>>> b477aefead1e2fdae899f23a2ac410f02283b13c
    } else {
        $memoryQuery = "INSERT INTO memory (user_id, skill_id, img_name, description) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($memoryQuery);
        $stmt->bind_param("iiss", $user_id, $skill_id, $imagePath, $description);
    }

    if ($stmt->execute()) {
        // Step 6: Update User Points (if no skill_id)
        if ($skill_id === null) {
            $updatePointsQuery = "UPDATE user SET points = points + 2 WHERE user_id = ?";
            $stmt = $conn->prepare($updatePointsQuery);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            // Fetch updated points
            $fetchPointsQuery = "SELECT points FROM user WHERE user_id = ?";
            $stmt = $conn->prepare($fetchPointsQuery);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->bind_result($points);
            $stmt->fetch();

            echo json_encode([
                "status" => "success",
                "message" => "Memory added successfully. You earned 2 points!",
                "image" => $imagePath,
                "points" => $points
            ]);
        } else {
            echo json_encode([
                "status" => "success",
                "message" => "Memory added successfully",
                "image" => $imagePath
            ]);
        }
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
    $result = $stmt->get_result();

    // Check if any rows are returned
    if ($result->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "No memories found"]);
        exit();
    }

    // Fetch all memories
    $memories = $result->fetch_all(MYSQLI_ASSOC);

    $response = [
        "status" => "success",
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

function countStreakDays($timestamps, $start_time) {
    if (empty($timestamps)) {
        return 0;
    }

    sort($timestamps);
    $streak_count = 0;
    $current_day_start = $start_time;

    foreach ($timestamps as $timestamp) {
        // Check if the timestamp falls within the current streak day
        if ($timestamp >= $current_day_start && $timestamp < strtotime("+1 day", $current_day_start)) {
            $streak_count++;
            $current_day_start = strtotime("+1 day", $current_day_start); // Move to the next day
        } elseif ($timestamp >= strtotime("+1 day", $current_day_start)) {
            // If the timestamp skips a day, reset the streak
            $streak_count = 1;
            $current_day_start = strtotime("+1 day", $current_day_start);
        } else {
            break; // Streak broken
        }
    }
    return $streak_count;
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $endpoint === '/session') {
    // Parse incoming JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    $skill_id = $data['skill_id'] ?? null;

    // Validate required parameters
    if (!$skill_id) {
        echo json_encode(["status" => "error", "message" => "Skill ID is required"]);
        exit();
    }

    // Ensure user_id is already extracted from the token (assumed to be available here)
    if (!isset($user_id)) {
        echo json_encode(["status" => "error", "message" => "User ID not found in token"]);
        exit();
    }

    // Step 1: Get Session Details and Check if Session is Active
    $query = "
        SELECT lt.user_id, lt.user_id2, lt.start_time, lt.accept 
        FROM learner_teacher lt
        WHERE lt.skill_id = ? AND (lt.user_id = ? OR lt.user_id2 = ?)
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $skill_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $session = $result->fetch_assoc();

    if (!$session) {
        echo json_encode(["status" => "error", "message" => "No active session found for the given Skill ID"]);
        exit();
    }

    // Check if both learner and teacher have accepted the session
    if ($session['accept'] != 1) {
        echo json_encode(["status" => "error", "message" => "Session is not active"]);
        exit();
    }

    // Extract session details (Corrected roles)
    if ($session['user_id'] == $user_id) {
        $teacher_id = $session['user_id']; // Current user is the teacher
        $learner_id = $session['user_id2'];
    } else {
        $teacher_id = $session['user_id']; // The teacher is always session['user_id']
        $learner_id = $session['user_id2']; // The learner is always session['user_id2']
    }

    $start_time = strtotime($session['start_time']); // Start time of the session

    // Fixed streak limit of 5 days
    $total_hours = 5; // Total streak days required (fixed)

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

    error_log("Learner Timestamps: " . json_encode($learner_timestamps));
    error_log("Teacher Timestamps: " . json_encode($teacher_timestamps));

    // Step 3: Calculate Streak Days for Learner and Teacher
    $learner_streak = countStreakDays($learner_timestamps, $start_time);
    $teacher_streak = countStreakDays($teacher_timestamps, $start_time);

    // Step 4: Calculate Remaining Streak Days
    $completed_streak_days = min($learner_streak, $teacher_streak);
    $remaining_streak_days = max(0, $total_hours - $completed_streak_days);

    // Step 5: Adjust Points Based on Streak Completion
    if ($completed_streak_days > 0) {
        // Ensure learner does not lose points if they have insufficient points
        $checkLearnerPointsQuery = "SELECT points FROM user WHERE user_id = ?";
        $stmt = $conn->prepare($checkLearnerPointsQuery);
        $stmt->bind_param("i", $learner_id);
        $stmt->execute();
        $stmt->bind_result($learner_points);
        $stmt->fetch();
        $stmt->close(); // Close the statement after fetching the result

        if ($learner_points >= 2) {
            // Deduct 2 points from learner
            $updateLearnerPointsQuery = "UPDATE user SET points = points - 2 WHERE user_id = ?";
            $stmt = $conn->prepare($updateLearnerPointsQuery);
            $stmt->bind_param("i", $learner_id);
            $stmt->execute();
            $stmt->close(); // Close the statement after execution
        }

        // Add 2 points to teacher
        $updateTeacherPointsQuery = "UPDATE user SET points = points + 2 WHERE user_id = ?";
        $stmt = $conn->prepare($updateTeacherPointsQuery);
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $stmt->close(); // Close the statement after execution
    }

    // Step 6: Calculate Next Streak Start Time
    $current_time = time(); // Current timestamp
    $next_streak_start_time = $start_time + ($completed_streak_days * 24 * 3600); // Start of the next streak day

    // If the next streak start time is in the past, move to the next 24-hour window
    while ($next_streak_start_time <= $current_time) {
        $next_streak_start_time += 24 * 3600; // Add 24 hours until it's in the future
    }

    $next_streak_start_formatted = date("Y-m-d H:i:s", $next_streak_start_time);

    // Step 7: Determine Session Status
    if ($learner_streak == 0 && $teacher_streak == 0 && empty($learner_timestamps) && empty($teacher_timestamps)) {
        // No memories uploaded yet
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

        // Store streak loss notifications in the database
        $insertNotificationQuery = "INSERT INTO notification (user_id, description) VALUES (?, ?)";
        $stmt = $conn->prepare($insertNotificationQuery);

        // For the learner
        $learner_notification = "Your learning session for {$skill_id} has ended due to streak loss.";
        $stmt->bind_param("is", $learner_id, $learner_notification);
        $stmt->execute();

        // For the teacher
        $teacher_notification = "Your teaching session for {$skill_id} has ended due to streak loss.";
        $stmt->bind_param("is", $teacher_id, $teacher_notification);
        $stmt->execute();

        // Send Streak Loss Notifications
        $response = [
            "status" => "error",
            "message" => "Session ended due to streak loss",
            "learner_streak" => $learner_streak,
            "teacher_streak" => $teacher_streak,
            "notifications" => [
                "learner_notification" => $learner_notification,
                "teacher_notification" => $teacher_notification
            ]
        ];
    } elseif ($completed_streak_days >= $total_hours) {
        // Mark the session as completed for both learner and teacher
        $updateQuery = "UPDATE user SET status = 'completed' WHERE user_id IN (?, ?)";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ii", $learner_id, $teacher_id);
        $stmt->execute();

        // Delete the session from the learner_teacher table
        $deleteSessionQuery = "DELETE FROM learner_teacher WHERE skill_id = ?";
        $stmt = $conn->prepare($deleteSessionQuery);
        $stmt->bind_param("i", $skill_id);
        $stmt->execute();

        // Delete memories for both learner and teacher
        $deleteMemoryQuery = "DELETE FROM memory WHERE skill_id = ? AND user_id IN (?, ?)";
        $stmt = $conn->prepare($deleteMemoryQuery);
        $stmt->bind_param("iii", $skill_id, $learner_id, $teacher_id);
        $stmt->execute();

        // Update log table for learner and teacher
        $logUpdateQuery = "
            INSERT INTO log (user_id, learnt_count, taught_count)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
                learnt_count = learnt_count + VALUES(learnt_count),
                taught_count = taught_count + VALUES(taught_count)
        ";
        $stmt = $conn->prepare($logUpdateQuery);

        // For the learner
        $learner_learnt_increment = 1; // Increment learnt_count for the learner
        $learner_taught_increment = 0; // Do not increment taught_count for the learner

        $stmt->bind_param("iii", 
            $learner_id, // User ID for learner
            $learner_learnt_increment, // Increment learnt_count
            $learner_taught_increment  // Do not increment taught_count
        );
        $stmt->execute();

        // For the teacher
        $teacher_learnt_increment = 0; // Do not increment learnt_count for the teacher
        $teacher_taught_increment = 1; // Increment taught_count for the teacher

        $stmt->bind_param("iii", 
            $teacher_id, // User ID for teacher
            $teacher_learnt_increment, // Do not increment learnt_count
            $teacher_taught_increment  // Increment taught_count
        );
        $stmt->execute();

        // Store session completion notifications in the database
        $insertNotificationQuery = "INSERT INTO notification (user_id, description) VALUES (?, ?)";
        $stmt = $conn->prepare($insertNotificationQuery);

        // For the learner
        $learner_notification = "Your learning session for {$skill_id} is completed!";
        $stmt->bind_param("is", $learner_id, $learner_notification);
        $stmt->execute();

        // For the teacher
        $teacher_notification = "Your teaching session for {$skill_id} is completed!";
        $stmt->bind_param("is", $teacher_id, $teacher_notification);
        $stmt->execute();

        // Send Completion Notifications
        $response = [
            "status" => "success",
            "message" => "Session completed",
            "notifications" => [
                "learner_notification" => $learner_notification,
                "teacher_notification" => $teacher_notification
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

    // Return the response as JSON
    echo json_encode($response);
}