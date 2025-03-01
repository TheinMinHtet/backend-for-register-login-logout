<?php
require "database_connection.php";

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // Allow all origins
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // Allow these HTTP methods
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Allow specific headers

$tagQuery = "SELECT * FROM tag";
$tagResult = $conn->query($tagQuery);

$tag = [];
if ($tagResult->num_rows > 0) {
    while ($row = $tagResult->fetch_assoc()) {
        $tag[] = $row;
    }
}

$response = [
    "tag" => $tag
];

echo json_encode($response);

$conn->close();
