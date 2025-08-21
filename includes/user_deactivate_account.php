<?php
// CHRONONAV_WEB_DOSS/includes/user_deactivate_account.php

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
$password = $_POST['password'] ?? '';

// Fetch the user's current hashed password from the database
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_from_db = $result->fetch_assoc();
$stmt->close();

// Verify the password before deactivating the account
if (!password_verify($password, $user_from_db['password'])) {
    $_SESSION['message'] = "Incorrect password. Account deactivation failed.";
    $_SESSION['message_type'] = 'danger';
    header('Location: ../pages/user/settings.php');
    exit();
}

// Update the user's status to inactive (e.g., set `is_active` to 0)
$deactivate_stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
$deactivate_stmt->bind_param("i", $user_id);

if ($deactivate_stmt->execute()) {
    // Deactivation was successful
    $deactivate_stmt->close();
    $conn->close();

    // Destroy the session to log the user out
    session_destroy();
    
    $_SESSION['message'] = "Your account has been successfully deactivated.";
    $_SESSION['message_type'] = 'success';
    
    // Redirect to the login page
    header('Location: ../../auth/login.php');
    exit();
} else {
    $_SESSION['message'] = "Error deactivating account: " . $conn->error;
    $_SESSION['message_type'] = 'danger';
    $deactivate_stmt->close();
    $conn->close();
    header('Location: ../pages/user/settings.php');
    exit();
}
?>