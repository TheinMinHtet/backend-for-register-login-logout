<?php
    require "database_connection.php";
    
    header("Content-Type: application/json");
    
    $skillQuery = "SELECT skill.name, skill.description skill.skill_ id FROM skill";
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
    
    echo json_encode($response);
    
    $conn->close();
    ?>