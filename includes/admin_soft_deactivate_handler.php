<?php
// CHRONONAV_WEB_DOSS/includes/admin_soft_deactivate_handler.php
// Handles setting the admin's is_active status to 0 (suspending the account)

require_once '../middleware/auth_check.php';
require_once '../config/db_connect.php'; 

/** @var \mysqli $conn */

// --- FIX START: Define the Project Root URL for reliable redirection ---
// Since the path is '/CHRONONAV_WEB_DOSS/' from the host root, we build the full URL dynamically.

// 1. Get the HTTP protocol (http or https)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
// 2. Define the project folder name
$project_folder = "CHRONONAV_WEB_DOSS"; 
// 3. Construct the base URL
define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . '/' . $project_folder . '/');

// --- FIX END ---

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    // Redirect if not logged in using the new BASE_URL
    header('Location: ' . BASE_URL . 'auth/login.php'); 
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user']['id'];
    $password = $_POST['password'] ?? '';

    // 1. Password Validation
    if (empty($password)) {
        $_SESSION['message'] = "Password is required to suspend your account.";
        $_SESSION['message_type'] = 'danger';
        header("Location: ../pages/admin/settings.php");
        exit();
    }

    // 2. Fetch password and verify
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id); 
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        $stmt->close(); 

        if ($user_data && password_verify($password, $user_data['password'])) {
            
            // 3. Update is_active status to 0 (Soft Deactivation)
            $update_stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
            
            if ($update_stmt) {
                $update_stmt->bind_param("i", $user_id);
                
                if ($update_stmt->execute()) {
                    // Account suspended. Destroy the session and redirect to login.
                    session_destroy();
                    session_start(); 
                    $_SESSION['message'] = "Your admin account has been suspended (is_active=0). Contact another admin to re-enable it.";
                    
                    // FIX: Use BASE_URL for the final redirect to guarantee the path works
                    header("Location: " . BASE_URL . "auth/login.php"); 
                    exit();
                } else {
                    $error_message = $update_stmt->error;
                    error_log("Soft Deactivation Error (ID: {$user_id}): " . $error_message);
                    $_SESSION['message'] = "Error suspending account. Please try again.";
                    $_SESSION['message_type'] = 'danger';
                }
                $update_stmt->close();
            } else {
                $_SESSION['message'] = "Database error during status update.";
                $_SESSION['message_type'] = 'danger';
            }
        } else {
            $_SESSION['message'] = "Incorrect password provided. Suspension failed.";
            $_SESSION['message_type'] = 'danger';
        }
    } else {
        $_SESSION['message'] = "Database fetch error.";
        $_SESSION['message_type'] = 'danger';
    }

    header("Location: ../pages/admin/settings.php");
    exit();
} else {
    header("Location: ../pages/admin/settings.php");
    exit();
}