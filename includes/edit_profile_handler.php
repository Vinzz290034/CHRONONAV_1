<?php
// CHRONONAV_WEB_DOSS/includes/edit_profile_handler.php

require_once '../middleware/auth_check.php';
require_once '../config/db_connect.php';
require_once 'functions.php';

// Ensure the user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_user_id = $_SESSION['user']['id'];
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $admin_id = trim($_POST['admin_id'] ?? null);
    $department = trim($_POST['department'] ?? null);
    $profile_img_path = $_SESSION['user']['profile_img'];

    // Basic validation
    if (empty($name) || empty($email)) {
        $_SESSION['message'] = "Full name and email are required.";
        $_SESSION['message_type'] = 'danger';
        header("Location: ../pages/admin/view_profile.php");
        exit();
    }

    // Handle image upload
    if (isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] === UPLOAD_ERR_OK) {
        
        // CORRECTION: Change the path from '../../' to '../'
        $upload_dir = '../uploads/profiles/'; 
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        $file_type = mime_content_type($_FILES['profile_img']['tmp_name']);
        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['message'] = "Invalid file type. Only JPEG, PNG, and GIF are allowed.";
            $_SESSION['message_type'] = 'danger';
            header("Location: ../pages/admin/view_profile.php");
            exit();
        }

        if ($_FILES['profile_img']['size'] > $max_size) {
            $_SESSION['message'] = "File size exceeds the 5MB limit.";
            $_SESSION['message_type'] = 'danger';
            header("Location: ../pages/admin/view_profile.php");
            exit();
        }

        // Generate a unique filename
        $file_extension = pathinfo($_FILES['profile_img']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid('profile_', true) . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;

        // Move the uploaded file
        if (move_uploaded_file($_FILES['profile_img']['tmp_name'], $upload_path)) {
            // CORRECTION: Change the path from '../../' to '../'
            $old_img = '../' . $_SESSION['user']['profile_img'];
            $default_img = '../uploads/profiles/default-avatar.png';
            
            // Delete old profile picture if it's not the default one
            if (file_exists($old_img) && $old_img !== $default_img) {
                unlink($old_img);
            }
            $profile_img_path = 'uploads/profiles/' . $new_filename;
        } else {
            $_SESSION['message'] = "Failed to upload new profile picture.";
            $_SESSION['message_type'] = 'danger';
            header("Location: ../pages/admin/view_profile.php");
            exit();
        }
    }

    // Update user data in the database
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, admin_id = ?, department = ?, profile_img = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("sssssi", $name, $email, $admin_id, $department, $profile_img_path, $current_user_id);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Profile updated successfully!";
            $_SESSION['message_type'] = 'success';
            // Update the session data immediately to reflect changes
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['admin_id'] = $admin_id;
            $_SESSION['user']['department'] = $department;
            $_SESSION['user']['profile_img'] = $profile_img_path;
        } else {
            $_SESSION['message'] = "Error updating profile: " . $stmt->error;
            $_SESSION['message_type'] = 'danger';
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Database error: " . $conn->error;
        $_SESSION['message_type'] = 'danger';
    }

    header("Location: ../pages/admin/view_profile.php");
    exit();
}