<?php
// CHRONONAV_WEB_DOSS/includes/feedback_handler.php

require_once '../middleware/auth_check.php';
require_once '../config/db_connect.php';

// Ensure the user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user']['id'];
    $feedback_type = trim($_POST['type'] ?? '');
    $feedback_subject = trim($_POST['subject'] ?? '');
    $feedback_message = trim($_POST['message'] ?? '');
    $feedback_rating = $_POST['rating'] ?? null; // Can be NULL if not selected

    // Basic validation
    if (empty($feedback_type) || empty($feedback_subject) || empty($feedback_message)) {
        $_SESSION['message'] = "Please fill out the type, subject, and message fields.";
        $_SESSION['message_type'] = 'danger';
        header("Location: ../pages/admin/view_profile.php");
        exit();
    }

    // Insert the feedback into the database
    $stmt = $conn->prepare("INSERT INTO feedback (user_id, feedback_type, subject, message, rating) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isssi", $user_id, $feedback_type, $feedback_subject, $feedback_message, $feedback_rating);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Thank you for your feedback! It has been submitted successfully.";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Error submitting feedback: " . $stmt->error;
            $_SESSION['message_type'] = 'danger';
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Database error: " . $conn->error;
        $_SESSION['message_type'] = 'danger';
    }

    // Redirect back to the profile page
    header("Location: ../pages/admin/view_profile.php");
    exit();
}

// If the form wasn't submitted via POST, redirect to the profile page
header("Location: ../pages/admin/view_profile.php");
exit();