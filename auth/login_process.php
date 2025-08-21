<?php
// CHRONONAV_WEB_UNO/auth/login_process.php

// Start the session if it's not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include your database connection file
require_once '../../config/db_connect.php'; // Adjust path as necessary

// Only process if the request method is POST (i.e., form was submitted)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Input validation
    if (empty($email) || empty($password)) {
        $_SESSION['message'] = "Please enter both email and password.";
        $_SESSION['message_type'] = "danger";
        header("Location: ../auth/login.php"); // Redirect back to login page
        exit();
    }

    // Prepare and execute the SQL query to fetch user details, including is_active status
    $stmt = $conn->prepare("SELECT id, name, email, password, role, is_active FROM users WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param("s", $email); // 's' for string (email)
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc(); // Fetch user data as an associative array
        $stmt->close();

        // Check if user exists
        if ($user) {
            // Verify the provided password against the hashed password in the database
            if (password_verify($password, $user['password'])) {

                // *** THIS IS THE CRUCIAL CHECK FOR ACCOUNT STATUS ***
                if ($user['is_active'] == 0) { // If is_active is 0, the account is disabled
                    $_SESSION['message'] = "Your account has been disabled. Please contact support.";
                    $_SESSION['message_type'] = "danger";
                    header("Location: ../auth/login.php"); // Redirect back to login with error
                    exit();
                }

                // If account is active and password is correct, log the user in
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                    // Do NOT store sensitive information like password or is_active directly in the session for security
                ];
                $_SESSION['loggedin'] = true; // Set a flag for general login status

                // Redirect based on user role
                if ($user['role'] === 'admin') {
                    header("Location: ../admin/dashboard.php"); // Redirect to admin dashboard
                } else {
                    header("Location: ../user/dashboard.php"); // Redirect to regular user dashboard
                }
                exit(); // Important to exit after header redirect
            } else {
                // Password does not match
                $_SESSION['message'] = "Invalid email or password.";
                $_SESSION['message_type'] = "danger";
            }
        } else {
            // User not found in the database
            $_SESSION['message'] = "Invalid email or password.";
            $_SESSION['message_type'] = "danger";
        }
    } else {
        // Database preparation error
        $_SESSION['message'] = "Database error during login. Please try again later.";
        $_SESSION['message_type'] = "danger";
        error_log("Login prepare failed: " . $conn->error); // Log the actual error
    }

    // If login failed for any reason, redirect back to the login page
    header("Location: ../auth/login.php");
    exit();
} else {
    // If the script was accessed directly without a POST request, redirect to the login form
    header("Location: ../auth/login.php");
    exit();
}

// Optionally close the database connection
// $conn->close();
?>