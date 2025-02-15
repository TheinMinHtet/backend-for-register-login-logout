<?php
    require "database_connection.php";
    
    header("Content-Type: application/json");
    
    $memoryQuery = "SELECT memory.img_name, memory.description FROM memory";
    $memoryResult = $conn->query($memoryQuery);
    
    $memories = [];
    if ($memoryResult->num_rows > 0) {
        while ($row = $memoryResult->fetch_assoc()) {
            $memories[] = $row;
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
    
    $response = [
        "memories" => $memories,
        "user" => $user
    ];
    
    echo json_encode($response);
    
    $conn->close();
    ?>