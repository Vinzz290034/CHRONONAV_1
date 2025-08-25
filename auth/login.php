<?php

//This is my auth/login.php
session_start();
require_once '../config/db_connect.php';

$error = ''; // Initialize error message

// Handle session messages if redirected from other pages (e.g., from admin actions)
if (isset($_SESSION['message'])) {
    $error = $_SESSION['message']; // Use $error variable to display it
    unset($_SESSION['message']);
    unset($_SESSION['message_type']); // Clear the message after displaying
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? ''); // Use trim() to remove whitespace
    $password = $_POST['password'] ?? '';

    // Basic validation
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // IMPORTANT: Select the 'is_active' column from the users table
        $stmt = $conn->prepare("SELECT id, name, email, role, password, profile_img, is_active FROM users WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user   = $result->fetch_assoc();
            $stmt->close(); // Close statement

            if ($user) {
                // Verify password first
                if (password_verify($password, $user['password'])) {

                    // *** CRUCIAL CHECK: Check if the account is active ***
                    if ($user['is_active'] == 0) { // If is_active is 0, the account is disabled
                        $error = "Your account has been disabled. Please contact the administrator.";
                        // Do NOT set $_SESSION['user'] or redirect
                    } else {
                        // Account is active and password is correct, proceed to log in

                        // --- START OF NEW CODE: INSERT AUDIT LOG ---
                        try {
                            $user_id = $user['id'];
                            $user_name = $user['name'];
                            $user_role = $user['role'];
                            
                            $action = ucfirst($user_role) . ' Login';
                            $details = "User '{$user_name}' logged in successfully.";
                            
                            $stmt_log = $conn->prepare("INSERT INTO audit_log (user_id, action, details) VALUES (?, ?, ?)");
                            if ($stmt_log) {
                                $stmt_log->bind_param("iss", $user_id, $action, $details);
                                $stmt_log->execute();
                                $stmt_log->close();
                            }
                        } catch (Exception $e) {
                            // Log the error but don't stop the login process for the user
                            error_log("Failed to insert audit log for user {$user['id']}: " . $e->getMessage());
                        }
                        // --- END OF NEW CODE ---


                        $_SESSION['user'] = [
                            'id'            => $user['id'],
                            'name'          => $user['name'],
                            'email'         => $user['email'],
                            'role'          => $user['role'],
                            'profile_img'   => $user['profile_img']
                        ];
                        $_SESSION['loggedin'] = true; // A general flag for being logged in

                        // Redirect based on role
                        header("Location: ../pages/{$user['role']}/dashboard.php");
                        exit(); // Crucial to exit after a header redirect
                    }
                } else {
                    $error = "Invalid email or password."; // Password mismatch
                }
            } else {
                $error = "Invalid email or password."; // User not found
            }
        } else {
            $error = "Database query failed. Please try again later."; // Error preparing statement
            error_log("Login prepare failed: " . $conn->error); // Log the actual database error
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CHRONONAV</title>
    <link rel="stylesheet" href="../assets/css/other_css/login.css"> 
</head>
<body>
    <div class="login-container">
        <img src="../assets/img/chrononav_logo.jpg" alt="ChronoNav Logo" class="logo">
        <h1 class="brand-name">CHRONONAV</h1>

        <?php if (!empty($error)): ?>
            <div class="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your Email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your Password" required>
            </div>
            <button type="submit" class="btn-main btn-login">Login</button>
        </form>

        <a href="register.php" class="btn-main btn-signup">Sign Up</a>
        <a href="forgot_password.php" class="forgot-password-link">Reset Password</a>
        <div class="app-version">App Version 1.0.0 · © 2025 ChronoNav</div>
    </div>
</body>

</html>