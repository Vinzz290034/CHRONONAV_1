<?php
// C:\xampp1\htdocs\chrononav_web_doss\config\db_connect.php

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'chrononav_web_doss'); // Your database name
define('DB_USER', 'root');
define('DB_PASS', ''); // Your database password

/**
 * Establishes and returns a PDO database connection.
 * @return PDO The PDO database connection object.
 * @throws PDOException If the connection fails.
 */
function get_db_connection() {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Log the error for debugging
        error_log("Database Connection Error: " . $e->getMessage());
        // Optionally, display a user-friendly error message
        die("Database connection failed. Please try again later. (Error Code: " . $e->getCode() . ")");
    }
}
?>