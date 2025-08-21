<?php
// CHRONONAV_WEB_DOSS/includes/user_edit_profile_handler.php

session_start();

// Include necessary files
require_once '../middleware/auth_check.php';
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Check if the user is logged in and has the 'user' role
requireRole(['user']);

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['message'] = "Invalid request method.";
    $_SESSION['message_type'] = 'danger';
    header('Location: ../pages/user/view_profile.php');
    exit();
}

// Get user ID from the session
$user_id = $_SESSION['user']['id'];

// Get and sanitize form data
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$department = trim($_POST['department'] ?? '');
$student_id = trim($_POST['student_id'] ?? '');

// Set default profile image path to the current one
$profile_img_path = $_SESSION['user']['profile_img'];

// Check if required fields are not empty
if (empty($name) || empty($email)) {
    $_SESSION['message'] = "Full Name and Email are required fields.";
    $_SESSION['message_type'] = 'danger';
    header('Location: ../pages/user/view_profile.php');
    exit();
}

// Validate the email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['message'] = "Invalid email format.";
    $_SESSION['message_type'] = 'danger';
    header('Location: ../pages/user/view_profile.php');
    exit();
}

// Check for email conflicts with other users
$stmt_check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$stmt_check_email->bind_param("si", $email, $user_id);
$stmt_check_email->execute();
$result_check_email = $stmt_check_email->get_result();

if ($result_check_email->num_rows > 0) {
    $_SESSION['message'] = "This email is already in use by another account.";
    $_SESSION['message_type'] = 'danger';
    $stmt_check_email->close();
    header('Location: ../pages/user/view_profile.php');
    exit();
}
$stmt_check_email->close();

// Handle profile picture upload if a file was provided
if (isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] === UPLOAD_ERR_OK) {
    $file_tmp_path = $_FILES['profile_img']['tmp_name'];
    $file_name = $_FILES['profile_img']['name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($file_ext, $allowed_extensions)) {
        $_SESSION['message'] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
        $_SESSION['message_type'] = 'danger';
        header('Location: ../pages/user/view_profile.php');
        exit();
    }
    
    // Create a unique file name to prevent naming conflicts
    $new_file_name = uniqid('profile_', true) . '.' . $file_ext;
    $upload_dir = '../uploads/profiles/';
    $upload_path = $upload_dir . $new_file_name;

    // Ensure the uploads directory exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Move the uploaded file to the permanent location
    if (move_uploaded_file($file_tmp_path, $upload_path)) {
        // If the upload was successful, delete the old profile picture
        $old_img_path = '../' . $_SESSION['user']['profile_img'];
        // Prevent deleting the default image
        if (!empty($_SESSION['user']['profile_img']) && $_SESSION['user']['profile_img'] !== 'uploads/profiles/default-avatar.png' && file_exists($old_img_path)) {
            unlink($old_img_path);
        }
        $profile_img_path = 'uploads/profiles/' . $new_file_name;
    } else {
        $_SESSION['message'] = "Failed to upload new profile picture. Please try again.";
        $_SESSION['message_type'] = 'danger';
        header('Location: ../pages/user/view_profile.php');
        exit();
    }
}

// Update the user's data in the database
$update_stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, department = ?, student_id = ?, profile_img = ? WHERE id = ?");
$update_stmt->bind_param("sssssi", $name, $email, $department, $student_id, $profile_img_path, $user_id);

if ($update_stmt->execute()) {
    // Update the session with the new information
    $_SESSION['user']['name'] = $name;
    $_SESSION['user']['email'] = $email;
    $_SESSION['user']['department'] = $department;
    $_SESSION['user']['student_id'] = $student_id;
    $_SESSION['user']['profile_img'] = $profile_img_path;

    $_SESSION['message'] = "Profile updated successfully! ✨";
    $_SESSION['message_type'] = 'success';
} else {
    $_SESSION['message'] = "Error updating profile: " . $conn->error;
    $_SESSION['message_type'] = 'danger';
}

$update_stmt->close();
$conn->close();

// Redirect back to the profile page
header('Location: ../pages/user/view_profile.php');
exit();