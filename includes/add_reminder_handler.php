<?php
// CHRONONAV_WEB_DOSS/includes/add_reminder_handler.php for 3 user admin, faculty and user add reminder
session_start();

require_once '../middleware/auth_check.php';
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Ensure the user is logged in and has the necessary role
requireRole(['admin', 'faculty', 'user']);

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user']['id'];
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$due_date = trim($_POST['due_date'] ?? '');
$due_time = trim($_POST['due_time'] ?? null);

// Validate required fields
if (empty($title) || empty($due_date)) {
    $_SESSION['message'] = "Title and Due Date are required.";
    $_SESSION['message_type'] = 'danger';
    
    // Redirect based on the user's role
    $redirect_path = '../pages/' . $_SESSION['user']['role'] . '/schedule.php';
    header("Location: {$redirect_path}?date=" . urlencode($due_date));
    exit();
}

// Check if due_date is a valid date format
if (!DateTime::createFromFormat('Y-m-d', $due_date)) {
    $_SESSION['message'] = "Invalid due date format.";
    $_SESSION['message_type'] = 'danger';
    
    // Redirect based on the user's role
    $redirect_path = '../pages/' . $_SESSION['user']['role'] . '/schedule.php';
    header("Location: {$redirect_path}?date=" . urlencode(date('Y-m-d')));
    exit();
}

// Handle optional time field
$due_time_for_db = !empty($due_time) ? $due_time : null;

// Insert the new reminder into the database
$stmt = $conn->prepare("INSERT INTO reminders (user_id, title, description, due_date, due_time, is_completed) VALUES (?, ?, ?, ?, ?, 0)");
if ($stmt) {
    $stmt->bind_param("issss", $user_id, $title, $description, $due_date, $due_time_for_db);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Reminder added successfully!";
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = "Error adding reminder: " . $stmt->error;
        $_SESSION['message_type'] = 'danger';
    }
    $stmt->close();
} else {
    $_SESSION['message'] = "Database error: " . $conn->error;
    $_SESSION['message_type'] = 'danger';
}

// Redirect back to the correct schedule page based on the user's role
$redirect_path = '../pages/' . $_SESSION['user']['role'] . '/schedule.php';
header("Location: {$redirect_path}?date=" . urlencode($due_date));
exit();
?>