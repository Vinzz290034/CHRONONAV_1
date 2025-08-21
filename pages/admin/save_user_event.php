<?php
// CHRONONAV_WEB_DOSS/pages/admin/save_user_event.php
// This script handles users (including admins acting as users) saving public events to their personal calendars.

require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php'; // Assuming requireRole is here

// This script can be accessed by both 'user' and 'admin' roles, as admins can also save events to their personal calendar.
requireRole(['user', 'admin']);

$user_id = $_SESSION['user']['id']; // Get the ID of the logged-in user (admin or regular user)

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_event'])) {
    $calendar_event_id = $_POST['calendar_event_id'] ?? null;

    // Basic validation for the incoming event ID
    if (empty($calendar_event_id) || !is_numeric($calendar_event_id)) {
        $_SESSION['message'] = "Invalid event ID provided for saving.";
        $_SESSION['message_type'] = "danger";
        header("Location: calendar.php"); // Redirect back to the calendar view
        exit();
    }

    // 1. Fetch the details of the public event from `calendar_events` table
    $stmt_fetch_public_event = $conn->prepare("SELECT event_name, description, start_date, end_date, location, event_type FROM calendar_events WHERE id = ?");
    if (!$stmt_fetch_public_event) {
        $_SESSION['message'] = "Database error fetching public event details: " . $conn->error;
        $_SESSION['message_type'] = "danger";
        header("Location: calendar.php");
        exit();
    }
    $stmt_fetch_public_event->bind_param("i", $calendar_event_id);
    $stmt_fetch_public_event->execute();
    $result_public_event = $stmt_fetch_public_event->get_result();

    if ($result_public_event->num_rows === 0) {
        $_SESSION['message'] = "The public event you tried to save does not exist.";
        $_SESSION['message_type'] = "danger";
        header("Location: calendar.php");
        exit();
    }
    $event_data = $result_public_event->fetch_assoc();
    $stmt_fetch_public_event->close();

    // 2. Check if this public event is ALREADY saved by the current user
    $stmt_check_saved = $conn->prepare("SELECT id FROM user_calendar_events WHERE user_id = ? AND calendar_event_id = ?");
    if (!$stmt_check_saved) {
        $_SESSION['message'] = "Database error checking if event is already saved: " . $conn->error;
        $_SESSION['message_type'] = "danger";
        header("Location: calendar.php");
        exit();
    }
    $stmt_check_saved->bind_param("ii", $user_id, $calendar_event_id);
    $stmt_check_saved->execute();
    $result_check_saved = $stmt_check_saved->get_result();

    if ($result_check_saved->num_rows > 0) {
        // Event is already saved, notify the user.
        $_SESSION['message'] = "This event is already in your calendar.";
        $_SESSION['message_type'] = "info";
        header("Location: calendar.php");
        exit();
    }
    $stmt_check_saved->close();

    // 3. If not already saved, insert the event into `user_calendar_events`
    // We're copying the details from the public event and linking it via calendar_event_id
    $stmt_insert = $conn->prepare("INSERT INTO user_calendar_events (user_id, calendar_event_id, event_name, description, start_date, end_date, location, event_type, is_personal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)"); // is_personal = 0 for saved public events
    if ($stmt_insert) {
        // Use the fetched event data
        $stmt_insert->bind_param(
            "iissssss",
            $user_id,
            $calendar_event_id, // This links to the original public event
            $event_data['event_name'],
            $event_data['description'],
            $event_data['start_date'],
            $event_data['end_date'],
            $event_data['location'],
            $event_data['event_type']
        );

        if ($stmt_insert->execute()) {
            $_SESSION['message'] = "'" . htmlspecialchars($event_data['event_name']) . "' added to your calendar!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error adding event to your calendar: " . $stmt_insert->error;
            $_SESSION['message_type'] = "danger";
        }
        $stmt_insert->close();
    } else {
        $_SESSION['message'] = "Database error preparing save event: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    }

    // Always redirect after POST to prevent re-submission on refresh
    header("Location: calendar.php");
    exit();

} else {
    // If the request method is not POST, or the 'save_event' parameter is missing,
    // it's an invalid request to this script.
    $_SESSION['message'] = "Invalid request to save event.";
    $_SESSION['message_type'] = "danger";
    header("Location: calendar.php");
    exit();
}
?>