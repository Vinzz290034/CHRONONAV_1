<?php
// CHRONONAV_WEB_DOSS/includes/add_user_event_handler.php
require_once '../middleware/auth_check.php';
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Ensure only authenticated users can access this script
requireRole(['user']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['message'] = "Invalid request method.";
    $_SESSION['message_type'] = 'danger';
    header('Location: ../pages/user/calendar.php');
    exit();
}

$current_user_id = $_SESSION['user']['id'];
$event_name = trim($_POST['event_name'] ?? '');
$description = trim($_POST['event_description'] ?? '');
$start_date = $_POST['start_date'] ?? null;
$end_date = $_POST['end_date'] ?? null;
$location = trim($_POST['event_location'] ?? '');
$event_type = trim($_POST['event_type'] ?? '');

// Basic validation
if (empty($event_name) || empty($start_date) || empty($event_type)) {
    $_SESSION['message'] = "Event Name, Start Date, and Event Type are required.";
    $_SESSION['message_type'] = 'danger';
    header('Location: ../pages/user/calendar.php');
    exit();
}

// Convert dates to a valid format for database insertion
$start_date_db = date('Y-m-d H:i:s', strtotime($start_date));
$end_date_db = !empty($end_date) ? date('Y-m-d H:i:s', strtotime($end_date)) : null;

// Prepare and execute the insert statement
$stmt = $conn->prepare("INSERT INTO user_calendar_events (user_id, event_name, description, start_date, end_date, location, event_type) VALUES (?, ?, ?, ?, ?, ?, ?)");

if ($stmt) {
    $stmt->bind_param("issssss", $current_user_id, $event_name, $description, $start_date_db, $end_date_db, $location, $event_type);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Event added successfully!";
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = "Error adding event: " . $stmt->error;
        $_SESSION['message_type'] = 'danger';
    }
    $stmt->close();
} else {
    $_SESSION['message'] = "Database error: " . $conn->error;
    $_SESSION['message_type'] = 'danger';
}

$conn->close();
header('Location: ../pages/user/calendar.php');
exit();
?>