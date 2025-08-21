<?php
// CHRONONAV_WEB_DOSS/includes/user_change_password.php

session_start();

require_once '../middleware/auth_check.php';
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Ensure only logged-in users can access this page
requireRole(['user', 'admin', 'faculty']);

// Check if the form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['message'] = "Invalid request method.";
    $_SESSION['message_type'] = 'danger';
    header('Location: ../pages/user/settings.php');
    exit();
}

$user_id = $_SESSION['user']['id'];
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_new_password = $_POST['confirm_new_password'] ?? '';

// Fetch the user's current hashed password from the database
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_from_db = $result->fetch_assoc();
$stmt->close();

// Check if the current password is correct
if (!password_verify($current_password, $user_from_db['password'])) {
    $_SESSION['message'] = "Incorrect current password.";
    $_SESSION['message_type'] = 'danger';
    header('Location: ../pages/user/settings.php');
    exit();
}

// Validate the new passwords
if (empty($new_password) || empty($confirm_new_password)) {
    $_SESSION['message'] = "All password fields are required.";
    $_SESSION['message_type'] = 'danger';
    header('Location: ../pages/user/settings.php');
    exit();
}

if ($new_password !== $confirm_new_password) {
    $_SESSION['message'] = "New passwords do not match.";
    $_SESSION['message_type'] = 'danger';
    header('Location: ../pages/user/settings.php');
    exit();
}

// Hash the new password before updating the database
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update the password in the database
$update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$update_stmt->bind_param("si", $hashed_password, $user_id);

if ($update_stmt->execute()) {
    $_SESSION['message'] = "Password changed successfully! 🎉";
    $_SESSION['message_type'] = 'success';
} else {
    $_SESSION['message'] = "Error changing password: " . $conn->error;
    $_SESSION['message_type'] = 'danger';
}

$update_stmt->close();
$conn->close();

header('Location: ../pages/user/settings.php');
exit();
?>