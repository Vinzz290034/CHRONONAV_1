<?php
// CHRONONAV_WEB_UNO/includes/edit_user_event_handler.php
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
    $event_id = $_POST['event_id'] ?? null;
    $event_name = trim($_POST['event_name'] ?? '');
    $description = trim($_POST['event_description'] ?? '');
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $location = trim($_POST['event_location'] ?? '');
    $event_type = trim($_POST['event_type'] ?? '');

    // Basic validation
    if (empty($event_id) || empty($event_name) || empty($start_date) || empty($event_type)) {
        $_SESSION['message'] = "Event ID, name, start date, and event type are required for editing.";
        $_SESSION['message_type'] = 'danger';
        header("Location: ../pages/faculty/calendar.php");
        exit();
    }

    try {
        // Prepare the SQL statement to update the event in user_calendar_events
        // We ensure the user owns the event to prevent unauthorized edits
        $sql = "UPDATE user_calendar_events SET event_name = ?, description = ?, start_date = ?, end_date = ?, location = ?, event_type = ? WHERE id = ? AND user_id = ?";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Database prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ssssssii", $event_name, $description, $start_date, $end_date, $location, $event_type, $event_id, $current_user_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['message'] = "Academic event updated successfully!";
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = "No changes were made to the event or event not found.";
                $_SESSION['message_type'] = 'info';
            }
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $stmt->close();

    } catch (Exception $e) {
        $_SESSION['message'] = "Error updating event: " . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
        error_log("Error updating user event: " . $e->getMessage());
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