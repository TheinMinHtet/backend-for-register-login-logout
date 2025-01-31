<?php
    $host = 'localhost'; 
    $username = 'root'; 
    $password = '';  
    $dbname = 'user_system';

    $conn = mysqli_connect($host, $username, $password, $dbname);

    // Check database connection
    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }
?>