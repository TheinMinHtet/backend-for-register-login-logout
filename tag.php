<?php
require "database_connection.php";

header("Content-Type: application/json");

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
