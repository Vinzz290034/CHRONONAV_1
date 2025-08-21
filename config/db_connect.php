<?php
// CHRONONAV_WEBZD/config/db_connect.php

// Prevent multiple connections if included multiple times
if (!isset($conn)) {
    $servername = "localhost";
    $username = "root"; // Your database username
    $password = "";     // Your database password
    $dbname = "chrononav_web_doss"; // Your database name

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        // Log the error securely and display a generic message
        error_log("Connection failed: " . $conn->connect_error);
        die("Database connection failed. Please try again later.");
    }
}
?>