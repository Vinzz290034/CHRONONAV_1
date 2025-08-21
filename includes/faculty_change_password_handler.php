<?php
// CHRONONAV_WEB_DOSS/includes/faculty_change_password_handler.php

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
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Check if any fields are empty
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    $_SESSION['message'] = "All password fields are required.";
    $_SESSION['message_type'] = 'danger';
    header('Location: ../pages/faculty/settings.php');
    exit();
}

// Check if new password matches the confirmation
if ($new_password !== $confirm_password) {
    $_SESSION['message'] = "The new password and confirmation password do not match.";
    $_SESSION['message_type'] = 'danger';
    header('Location: ../pages/faculty/settings.php');
    exit();
}

// Validate new password strength
if (strlen($new_password) < 8) {
    $_SESSION['message'] = "New password must be at least 8 characters long.";
    $_SESSION['message_type'] = 'danger';
    header('Location: ../pages/faculty/settings.php');
    exit();
}

// Fetch the user's current hashed password from the database
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // This should not happen if auth_check is working correctly, but it's a good safety net.
    $_SESSION['message'] = "User not found.";
    $_SESSION['message_type'] = 'danger';
    header('Location: ../pages/faculty/settings.php');
    exit();
}

$user = $result->fetch_assoc();
$stored_password_hash = $user['password'];
$stmt->close();

// Verify the current password
if (!password_verify($current_password, $stored_password_hash)) {
    $_SESSION['message'] = "Incorrect current password.";
    $_SESSION['message_type'] = 'danger';
    header('Location: ../pages/faculty/settings.php');
    exit();
}

// Hash the new password and update the database
$new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
$update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$update_stmt->bind_param("si", $new_hashed_password, $user_id);

if ($update_stmt->execute()) {
    $_SESSION['message'] = "Password changed successfully!";
    $_SESSION['message_type'] = 'success';
} else {
    $_SESSION['message'] = "Error changing password: " . $conn->error;
    $_SESSION['message_type'] = 'danger';
}

$update_stmt->close();
$conn->close();

header('Location: ../pages/faculty/settings.php');
exit();
?>