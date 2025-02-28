<?php
require "database_connection.php";

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // Allow all origins
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // Allow these HTTP methods
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Get search parameters
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : "";
$tags = isset($_GET['tag']) ? explode(" ", trim($_GET['tag'])) : [];

// Step 1: Find skill IDs that match the search criteria
$skillIdQuery = "
    SELECT DISTINCT skill.skill_id
    FROM skill
    LEFT JOIN tag ON skill.skill_id = tag.skill_id
    WHERE 1
";

$params = [];
if (!empty($keyword)) {
    $skillIdQuery .= " AND skill.name LIKE ?";
    $params[] = "%$keyword%";
}

if (!empty($tags)) {
    $skillIdQuery .= " AND (";
    $tagConditions = [];
    foreach ($tags as $tag) {
        $tagConditions[] = "tag.tag LIKE ?";
        $params[] = "%$tag%";
    }
    $skillIdQuery .= implode(" OR ", $tagConditions) . ")";
}

// Execute the skill ID query
$stmt = $conn->prepare($skillIdQuery);
if (!empty($params)) {
    $stmt->bind_param(str_repeat("s", count($params)), ...$params);
}
$stmt->execute();
$skillIdResult = $stmt->get_result();

// Collect matching skill IDs
$matchingSkillIds = [];
if ($skillIdResult->num_rows > 0) {
    while ($row = $skillIdResult->fetch_assoc()) {
        $matchingSkillIds[] = $row['skill_id'];
    }
}

// If no matching skills, return empty response
if (empty($matchingSkillIds)) {
    echo json_encode([]);
    $conn->close();
    exit;
}

// Step 2: Fetch all data for the matching skills (including all tags)
$query = "
    SELECT 
        skill.skill_id, skill.name AS skill_name, skill.description AS skill_description,
        skill.hours,
        skill.taught_count,
        user.user_id, user.username, user.email,user.profile_img,
        tag.tag_id, tag.tag
    FROM skill
    LEFT JOIN user ON skill.user_id = user.user_id
    LEFT JOIN tag ON skill.skill_id = tag.skill_id
    WHERE skill.skill_id IN (" . implode(",", array_fill(0, count($matchingSkillIds), "?")) . ")
";

// Prepare and execute the query
$stmt = $conn->prepare($query);
$stmt->bind_param(str_repeat("i", count($matchingSkillIds)), ...$matchingSkillIds);
$stmt->execute();
$result = $stmt->get_result();

// Process results
$skills = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $skillId = $row['skill_id'];
        $tagId = $row['tag_id'];

        // Decode tag_name if stored as JSON
        $tagName = json_decode($row['tag'], true);
        if (!is_array($tagName)) {
            $tagName = [$row['tag']]; // Ensure it's an array
        }

        // Initialize skill entry if not exists
        if (!isset($skills[$skillId])) {
            $skills[$skillId] = [
                "skill_id" => $skillId,
                "name" => $row['skill_name'],
                "description" => $row['skill_description'],
                "days" => $row['hours'],
                "taught_count" => $row['taught_count'],
                "user" => [
                    "user_id" => $row['user_id'],
                    "username" => $row['username'],
                    "email" => $row['email'],
                    "profile" => $row['profile_img']
                ],
                "tags" => []
            ];
        }

        // Add tag if exists
        if (!empty($tagId)) {
            foreach ($tagName as $singleTag) {
                $skills[$skillId]["tags"][] = [
                    "tag_id" => $tagId,
                    "tag" => $singleTag
                ];
            }
        }
    }
}

// Return JSON response
echo json_encode(array_values($skills));

$conn->close();
