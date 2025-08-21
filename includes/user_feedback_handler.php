<?php
// CHRONONAV_WEB_DOSS/includes/user_feedback_handler.php

session_start();

// Include necessary files
require_once '../middleware/auth_check.php';
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Ensure the user is logged in
requireRole(['user', 'admin', 'faculty']);

// Check if the form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['message'] = "Invalid request method.";
    $_SESSION['message_type'] = 'danger';
    header('Location: ../pages/user/view_profile.php');
    exit();
}

$user_id = $_SESSION['user']['id'];
$feedback_type = trim($_POST['feedback_type'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');
$rating = trim($_POST['rating'] ?? null); // Rating is optional

// Validate required fields
if (empty($feedback_type) || empty($subject) || empty($message)) {
    $_SESSION['message'] = "Please select a feedback type, subject, and enter your message.";
    $_SESSION['message_type'] = 'danger';
    header('Location: ../pages/user/view_profile.php');
    exit();
}

// Prepare an insert statement to save the feedback
// The column names in the query are updated to match your database schema
$stmt = $conn->prepare("INSERT INTO feedback (user_id, feedback_type, subject, message, rating) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("isssi", $user_id, $feedback_type, $subject, $message, $rating);

if ($stmt->execute()) {
    $_SESSION['message'] = "Thank you for your feedback! We appreciate your input. 📝";
    $_SESSION['message_type'] = 'success';
} else {
    $_SESSION['message'] = "An error occurred while submitting your feedback: " . $conn->error;
    $_SESSION['message_type'] = 'danger';
}

$stmt->close();
$conn->close();

header('Location: ../pages/user/view_profile.php');
exit();
?>