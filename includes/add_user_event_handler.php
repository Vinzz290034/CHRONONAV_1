<?php
// CHRONONAV_WEB_UNO/includes/add_user_event_handler.php
require_once '../middleware/auth_check.php';
require_once '../config/db_connect.php';

// Ensure the user is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'faculty') {
    header("Location: ../auth/login.php");
    exit();
}

$current_user_id = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $event_name = trim($_POST['event_name'] ?? '');
    $description = trim($_POST['event_description'] ?? '');
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $location = trim($_POST['event_location'] ?? '');
    $event_type = trim($_POST['event_type'] ?? '');

    // Basic validation
    if (empty($event_name) || empty($start_date) || empty($event_type)) {
        $_SESSION['message'] = "Event name, start date, and event type are required.";
        $_SESSION['message_type'] = 'danger';
        header("Location: ../pages/faculty/calendar.php");
        exit();
    }

    try {
        // Prepare the SQL statement to insert the new event into user_calendar_events
        $sql = "INSERT INTO user_calendar_events (user_id, event_name, description, start_date, end_date, location, event_type) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Database prepare failed: " . $conn->error);
        }

        $stmt->bind_param("issssss", $current_user_id, $event_name, $description, $start_date, $end_date, $location, $event_type);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Academic event added successfully!";
            $_SESSION['message_type'] = 'success';
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $stmt->close();

    } catch (Exception $e) {
        $_SESSION['message'] = "Error adding event: " . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
        error_log("Error adding user event: " . $e->getMessage());
    }
    
    // Redirect back to the calendar page
    header("Location: ../pages/faculty/calendar.php");
    exit();
} else {
    // Not a POST request
    $_SESSION['message'] = "Invalid request method.";
    $_SESSION['message_type'] = 'danger';
    header("Location: ../pages/faculty/calendar.php");
    exit();
}
?>