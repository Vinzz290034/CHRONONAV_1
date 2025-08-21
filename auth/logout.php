<?php
session_start();
require_once '../config/db_connect.php'; // Make sure to include your database connection file

// Check if a user is currently logged in
if (isset($_SESSION['user']['id'])) {
    // --- START OF NEW CODE: INSERT AUDIT LOG FOR LOGOUT ---
    try {
        $user_id = $_SESSION['user']['id'];
        $user_name = $_SESSION['user']['name'];
        $action = 'Logout';
        $details = "User '{$user_name}' logged out successfully.";
        
        $stmt_log = $conn->prepare("INSERT INTO audit_log (user_id, action, details) VALUES (?, ?, ?)");
        if ($stmt_log) {
            $stmt_log->bind_param("iss", $user_id, $action, $details);
            $stmt_log->execute();
            $stmt_log->close();
        }
    } catch (Exception $e) {
        // Log the error but don't stop the logout process
        error_log("Failed to insert audit log for logout: " . $e->getMessage());
    }
    // --- END OF NEW CODE ---
}

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to landing or login page
header("Location: ../index.php");
exit();