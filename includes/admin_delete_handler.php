<?php
// CHRONONAV_WEB_DOSS/includes/admin_deactivate_handler.php
// NOTE: This handler performs PERMANENT ACCOUNT DELETION (DELETE FROM users)

require_once '../middleware/auth_check.php';
require_once '../config/db_connect.php'; // Defines $conn as mysqli object

/** @var \mysqli $conn */

// Ensure the user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: ../../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user']['id'];
    $password = $_POST['password'] ?? '';
    $is_confirmed = isset($_POST['confirm_deactivate']);

    // Step 1: Validate input (Password and Checkbox Confirmation)
    if (!$is_confirmed) {
        $_SESSION['message'] = "You must confirm the deletion to proceed.";
        $_SESSION['message_type'] = 'danger';
        header("Location: ../pages/admin/settings.php");
        exit();
    }
    if (empty($password)) {
        $_SESSION['message'] = "Your current password is required to confirm deletion.";
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
        $user_data = $result->fetch_assoc();
        $stmt->close(); 

        if ($user_data) {
            // Step 3: Verify the entered password
            if (password_verify($password, $user_data['password'])) {
                
                // Step 4: Delete the user's account
                $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                
                if ($delete_stmt) {
                    $delete_stmt->bind_param("i", $user_id);
                    
                    if ($delete_stmt->execute()) {
                        // Account successfully deleted. Destroy the session and redirect.
                        session_destroy();
                        session_start(); 
                        $_SESSION['message'] = "Your administrator account has been permanently deleted.";
                        header("Location: ../../auth/login.php");
                        exit();
                    } else {
                        // Error during deletion execution
                        $error_message = $delete_stmt->error;
                        error_log("Account Deletion Execute Error (ID: {$user_id}): " . $error_message);
                        $_SESSION['message'] = "Database error during deletion: " . $error_message;
                        $_SESSION['message_type'] = 'danger';
                    }
                    $delete_stmt->close();
                } else {
                    // Error during statement preparation
                    $error_message = $conn->error;
                    error_log("Delete Prepare Error: " . $error_message);
                    $_SESSION['message'] = "Database error (delete prepare): " . $error_message;
                    $_SESSION['message_type'] = 'danger';
                }

            } else {
                $_SESSION['message'] = "Incorrect password provided. Deletion failed.";
                $_SESSION['message_type'] = 'danger';
            }
        } else {
            $_SESSION['message'] = "User record not found.";
            $_SESSION['message_type'] = 'danger';
        }
    } else {
        $error_message = $conn->error;
        error_log("Password Fetch Prepare Error: " . $error_message);
        $_SESSION['message'] = "Database error (fetch prepare): " . $error_message;
        $_SESSION['message_type'] = 'danger';
    }

    // Fallback redirect if deletion failed
    header("Location: ../pages/admin/settings.php");
    exit();
} else {
    // If accessed via GET method
    header("Location: ../pages/admin/settings.php");
    exit();
}