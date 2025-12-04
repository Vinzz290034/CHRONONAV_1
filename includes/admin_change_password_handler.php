<?php
// CHRONONAV_WEB_DOSS/includes/change_password_handler.php

require_once '../middleware/auth_check.php';
require_once '../config/db_connect.php'; // Defines $conn as mysqli object

// Ensure $conn is available and is a mysqli object for IDE static analysis
/** @var \mysqli $conn */

// Ensure the user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user']['id'];
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Step 1: Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['message'] = "All fields are required.";
        $_SESSION['message_type'] = 'danger';
        header("Location: ../pages/admin/settings.php");
        exit();
    }

    if ($new_password !== $confirm_password) {
        $_SESSION['message'] = "New passwords do not match.";
        $_SESSION['message_type'] = 'danger';
        header("Location: ../pages/admin/settings.php");
        exit();
    }

    if (strlen($new_password) < 8) {
        $_SESSION['message'] = "New password must be at least 8 characters long.";
        $_SESSION['message_type'] = 'danger';
        header("Location: ../pages/admin/settings.php");
        exit();
    }

    // Step 2: Fetch the user's current hashed password from the database
    /** @var \mysqli_stmt|false $stmt */ // FIX for P1013: Undefined method 'bind_param', 'get_result', 'close'
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    
    if ($stmt) {
        $stmt->bind_param("i", $user_id); // FIX for P1013 (Line 44 area)
        $stmt->execute();
        $result = $stmt->get_result(); // FIX for P1013 (Line 46 area)
        $user = $result->fetch_assoc();
        $stmt->close(); // FIX for P1013 (Line 48 area)

        if ($user) {
            // Step 3: Verify the current password
            if (password_verify($current_password, $user['password'])) {
                
                // Step 4: Hash the new password and update the database
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                /** @var \mysqli_stmt|false $update_stmt */ // FIX for P1013: Undefined method 'bind_param', 'close'
                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                
                if ($update_stmt) {
                    $update_stmt->bind_param("si", $hashed_password, $user_id); // FIX for P1013 (Line 59 area)
                    
                    if ($update_stmt->execute()) {
                        $_SESSION['message'] = "Password changed successfully!";
                        $_SESSION['message_type'] = 'success';
                    } else {
                        // FIX for P1014: Undefined property '$error' (Line 64 area - accessing $update_stmt->error)
                        $error_message = $update_stmt instanceof \mysqli_stmt ? $update_stmt->error : "Unknown update execution error.";
                        error_log("Password Update Execute Error: " . $error_message);
                        $_SESSION['message'] = "Error updating password: " . $error_message;
                        $_SESSION['message_type'] = 'danger';
                    }
                    $update_stmt->close(); // FIX for P1013 (Line 67 area)
                } else {
                    // FIX for P1014: Undefined property '$error' (Line 69 area - accessing $conn->error)
                    $error_message = $conn instanceof \mysqli ? $conn->error : "Unknown connection error.";
                    error_log("Update Prepare Error: " . $error_message);
                    $_SESSION['message'] = "Database error (update prepare): " . $error_message;
                    $_SESSION['message_type'] = 'danger';
                }

            } else {
                $_SESSION['message'] = "Incorrect current password.";
                $_SESSION['message_type'] = 'danger';
            }
        } else {
            $_SESSION['message'] = "User not found.";
            $_SESSION['message_type'] = 'danger';
        }
    } else {
        // FIX for P1014: Undefined property '$error' (Line 82 area - accessing $conn->error)
        $error_message = $conn instanceof \mysqli ? $conn->error : "Unknown connection error.";
        error_log("Fetch Prepare Error: " . $error_message);
        $_SESSION['message'] = "Database error (fetch prepare): " . $error_message;
        $_SESSION['message_type'] = 'danger';
    }

    header("Location: ../pages/admin/settings.php");
    exit();
}