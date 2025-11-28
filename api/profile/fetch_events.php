<?php
// Enable temporary error reporting to diagnose any remaining issues.
// REMOVE these three lines once the script works perfectly in your browser.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configuration for Database Connection
$servername = "localhost";
$username = "root"; 
$password = "";     
$dbname = "chrononav_web_doss"; // Your database name
$tableName = "user_calendar_event"; // Your table name

// Set header to JSON format
header('Content-Type: application/json');

// --- Connection ---
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- Query ---
    // The query uses SQL Aliasing (AS) to match your database column names 
    // to the field names expected by the Flutter UserCalendarEvent model.
    $stmt = $conn->prepare("SELECT 
        Id, 
        event_name AS title, 
        start_date AS start_time, 
        end_date AS end_time, 
        location, 
        -- Since you don't have a 'color_hex' column, we provide a default color 
        -- that Flutter will use for any fetched event.
        '#008080' AS color_hex 
    FROM $tableName 
    ORDER BY start_date ASC");
    
    $stmt->execute();
    
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Output ---
    // If the query is successful, output the data as JSON
    echo json_encode($result);

} catch(PDOException $e) {
    // If connection or query fails, return a detailed error message
    // (This part is useful for debugging in the browser)
    http_response_code(500);
    echo json_encode(array("error" => "Database connection or query failed: " . $e->getMessage()));
}

// Close connection
$conn = null;
?>