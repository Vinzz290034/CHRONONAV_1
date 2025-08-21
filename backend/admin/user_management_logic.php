<?php
// CHRONONAV_WEB_UNO/backend/admin/user_management_logic.php

// Ensure session is started if not already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
// Adjust path based on your folder structure. Assuming 'backend/admin' is two levels below root.
require_once '../../config/db_connect.php';

// IMPORTANT: Do NOT include auth_check.php here if it's already included in user_management.php.
// Including it twice can lead to unexpected behavior or redundant checks/redirects.
// auth_check.php should primarily be included at the very top of your HTML-rendering pages.

// Initialize message variables (these will be read by user_management.php)
$message = '';
$message_type = '';

// Define common user roles
// These should match the ENUM values in your 'role' column in the 'users' table.
// Keep this consistent with your database enum, e.g., 'student' if you have it.
define('ROLES', ['admin', 'faculty', 'student', 'user', 'guest']); // Added 'student', 'guest' based on common systems

// Function to get all users using MySQLi
function getAllUsers($conn) {
    // Select id, name, email, role, and is_active status from the users table.
    $sql = "SELECT id, name, email, role, is_active FROM users ORDER BY name ASC";
    $result = $conn->query($sql); // Execute the query

    $users = [];
    if ($result) { // Check if query executed successfully
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        $result->free(); // Free result set
    } else {
        error_log("Error fetching users: " . $conn->error);
        // Do not set session message here, it's better handled in the main page for initial load errors
    }
    return $users;
}

// Function to execute a prepared statement for insert/update/delete using MySQLi
function executePreparedQuery($conn, $sql, $params, $types = '') {
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error . " for SQL: " . $sql);
        return false;
    }

    if (!empty($params) && !empty($types)) {
        // Use call_user_func_array to bind parameters safely
        // Create an array of references for bind_param
        $bind_names = array($types);
        foreach ($params as $key => $value) {
            $bind_names[] = &$params[$key]; // Pass by reference
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_names);
    }

    $success = $stmt->execute();
    if ($success === false) {
        error_log("Execute failed: " . $stmt->error . " for SQL: " . $sql);
    }
    $stmt->close();
    return $success;
}


// Handle POST requests for user management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    // Use filter_var for better integer validation
    $user_id = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);

    // Validate user_id: Must be a positive integer
    if ($user_id === false || $user_id <= 0) {
        $_SESSION['message'] = "Invalid user ID provided.";
        $_SESSION['message_type'] = "danger";
        header("Location: ../../pages/admin/user_management.php"); // Redirect back to main page
        exit();
    }

    // IMPORTANT: Prevent admin from modifying their own account via this panel
    if (isset($_SESSION['user']['id']) && (int)$user_id === (int)$_SESSION['user']['id']) {
        $_SESSION['message'] = "You cannot modify your own account's status or role from here.";
        $_SESSION['message_type'] = "warning";
        header("Location: ../../pages/admin/user_management.php"); // Redirect back to main page
        exit();
    }

    switch ($action) {
        case 'edit_role':
            $new_role = trim($_POST['new_role'] ?? '');
            if (in_array($new_role, ROLES)) {
                $sql = "UPDATE users SET role = ? WHERE id = ?";
                if (executePreparedQuery($conn, $sql, [$new_role, $user_id], 'si')) {
                    $_SESSION['message'] = "User role updated to '" . htmlspecialchars($new_role) . "' successfully!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Failed to update user role.";
                    $_SESSION['message_type'] = "danger";
                }
            } else {
                $_SESSION['message'] = "Invalid role selected.";
                $_SESSION['message_type'] = "danger";
            }
            break;

        case 'toggle_active_status':
            $current_status = filter_var($_POST['current_status'] ?? 0, FILTER_VALIDATE_INT);
            $new_status = ($current_status === 1) ? 0 : 1; // Toggle status (1 becomes 0, 0 becomes 1)
            $status_text = ($new_status === 1) ? 'enabled' : 'disabled';

            $sql = "UPDATE users SET is_active = ? WHERE id = ?";
            if (executePreparedQuery($conn, $sql, [$new_status, $user_id], 'ii')) {
                $_SESSION['message'] = "User account {$status_text} successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Failed to toggle user account status.";
                $_SESSION['message_type'] = "danger";
            }
            break;

        case 'delete_user':
            $sql = "DELETE FROM users WHERE id = ?";
            if (executePreparedQuery($conn, $sql, [$user_id], 'i')) {
                $_SESSION['message'] = "User account permanently deleted!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Failed to delete user account.";
                $_SESSION['message_type'] = "danger";
            }
            break;

        default:
            $_SESSION['message'] = "Invalid action.";
            $_SESSION['message_type'] = "danger";
            break;
    }

    // Redirect back to the user management page after processing any action
    header("Location: ../../pages/admin/user_management.php");
    exit();
}

// After handling any POST requests, fetch all users for the current display
// This part runs when the script is included by user_management.php for initial display
$users = getAllUsers($conn);

// Retrieve and clear any session messages for display on the page
// This part is redundant here if user_management.php already does it.
// It's better to manage messages solely on the rendering page after redirect.
// For robust setup, let user_management.php handle message display after the redirect.
// The `user_management.php` code you provided already does this with a check for `!empty($message)`.
// So, the logic of setting $_SESSION['message'] and $_SESSION['message_type'] above is correct.

// Close the database connection if it's not needed by other scripts that will be included later.
// However, if db_connect.php is meant to keep the connection open for the duration of the request,
// then don't close it here. Generally, PHP closes connections automatically at script end.
// $conn->close(); // Uncomment if you manage connections strictly per script inclusion.
?>