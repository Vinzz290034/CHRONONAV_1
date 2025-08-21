<?php
$conn = new mysqli("localhost", "root", "", "chrononav_dbsi", 3306);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "✅ Database connection successful!";
}
?>
