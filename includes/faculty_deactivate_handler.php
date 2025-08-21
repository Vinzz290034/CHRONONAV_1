<?php
// CHRONONAV_WEB_DOSS/includes/faculty_deactivate_handler.php

require_once '../middleware/auth_check.php';
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Ensure the user is logged in and has the 'faculty' role
requireRole(['faculty']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['message'] = "Invalid request method.";
    $_SESSION['message_type'] = 'danger';
    header('Location: ../pages/faculty/settings.php');
    exit();
}

$user_id = $_SESSION['user']['id'];
$confirm_deactivate = $_POST['confirm_deactivate'] ?? '';

// Check if the user has confirmed deactivation
if ($confirm_deactivate !== 'on') {
    $_SESSION['message'] = "You must confirm to deactivate your account.";
    $_SESSION['message_type'] = 'danger';
    header('Location: ../pages/faculty/settings.php');
    exit();
}

// Update the user's status to 'deactivated' or 'inactive'
// Assuming a column named `is_active` in the `users` table
$stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    // If deactivation is successful, destroy the session and redirect to the login page
    $_SESSION['message'] = "Your account has been successfully deactivated.";
    $_SESSION['message_type'] = 'success';
    session_destroy();
    header('Location: ../auth/login.php');
    exit();
} else {
    $_SESSION['message'] = "Error deactivating account: " . $conn->error;
    $_SESSION['message_type'] = 'danger';
    header('Location: ../pages/faculty/settings.php');
    exit();
}

$stmt->close();
$conn->close();
?>