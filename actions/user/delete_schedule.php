<?php
// CHRONONAV_WEB_DOSS/actions/user/delete_schedule.php

session_start();

require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php'; 
require_once '../../includes/functions.php';

// Ensure $conn is available and is a mysqli object
/** @var \mysqli $conn */ 

// -----------------------------------------------------------
// 1. Validate Request Method and Input
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['message'] = "Invalid request method.";
    $_SESSION['message_type'] = 'danger';
    header('Location: ../../pages/user/dashboard.php');
    exit();
}

$user_id = $_SESSION['user']['id'];

// Get the schedule ID from the POST data
// The mobile app uses an API, but the web uses a form submission.
// We expect the schedule ID to be named 'schedule_id' in the POST data.
$schedule_id = $_POST['schedule_id'] ?? null;

if (empty($schedule_id)) {
    $_SESSION['message'] = "Error: Schedule identifier is missing.";
    $_SESSION['message_type'] = 'danger';
    header('Location: ../../pages/user/dashboard.php');
    exit();
}

// -----------------------------------------------------------
// 2. Database Deletion Query
// -----------------------------------------------------------
// CRITICAL: Ensure the deletion is scoped to the user_id from the session!
$delete_query = "DELETE FROM add_pdf WHERE id = ? AND user_id = ?";

$stmt = $conn->prepare($delete_query);

if ($stmt === false) {
    error_log("Delete Schedule Prepare Error: " . $conn->error);
    $_SESSION['message'] = "An internal error occurred (DB Prep).";
    $_SESSION['message_type'] = 'danger';
    header('Location: ../../pages/user/dashboard.php');
    exit();
}

$stmt->bind_param("ii", $schedule_id, $user_id);

if ($stmt->execute()) {
    // Check if any row was actually deleted
    if ($stmt->affected_rows > 0) {
        $_SESSION['message'] = "Schedule deleted successfully! 🗑️";
        $_SESSION['message_type'] = 'success';
    } else {
        // This means the ID was not found, or it didn't belong to the user
        $_SESSION['message'] = "Error: Schedule not found or unauthorized to delete.";
        $_SESSION['message_type'] = 'warning';
    }
} else {
    error_log("Delete Schedule Execute Error: " . $stmt->error);
    $_SESSION['message'] = "Error deleting schedule: Please try again.";
    $_SESSION['message_type'] = 'danger';
}

$stmt->close();

// -----------------------------------------------------------
// 3. Redirect back to the dashboard
// -----------------------------------------------------------
header('Location: ../../pages/user/dashboard.php');
exit();
?>