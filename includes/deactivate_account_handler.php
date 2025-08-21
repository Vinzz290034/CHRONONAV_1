<?php
// CHRONONAV_WEB_DOSS/includes/deactivate_account_handler.php

require_once '../middleware/auth_check.php';
require_once '../config/db_connect.php';

// Ensure the user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user']['id'];
    $password = $_POST['password'] ?? '';

    // Step 1: Validate input
    if (empty($password)) {
        $_SESSION['message'] = "Password is required to deactivate your account.";
        $_SESSION['message_type'] = 'danger';
        header("Location: ../pages/admin/settings.php");
        exit();
    }

    // Step 2: Fetch the user's current hashed password from the database
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            // Step 3: Verify the entered password
            if (password_verify($password, $user['password'])) {
                
                // Step 4: Delete the user's account
                $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                
                if ($delete_stmt) {
                    $delete_stmt->bind_param("i", $user_id);
                    if ($delete_stmt->execute()) {
                        // Account successfully deactivated. Destroy the session and redirect.
                        session_destroy();
                        header("Location: ../../auth/login.php?message=account_deactivated");
                        exit();
                    } else {
                        $_SESSION['message'] = "Error deactivating account: " . $delete_stmt->error;
                        $_SESSION['message_type'] = 'danger';
                    }
                    $delete_stmt->close();
                } else {
                    $_SESSION['message'] = "Database error (delete): " . $conn->error;
                    $_SESSION['message_type'] = 'danger';
                }

            } else {
                $_SESSION['message'] = "Incorrect password.";
                $_SESSION['message_type'] = 'danger';
            }
        } else {
            $_SESSION['message'] = "User not found.";
            $_SESSION['message_type'] = 'danger';
        }
    } else {
        $_SESSION['message'] = "Database error (fetch): " . $conn->error;
        $_SESSION['message_type'] = 'danger';
    }

    header("Location: ../pages/admin/settings.php");
    exit();
}