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
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <style>
    body {
        font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        background-color: #ffffffff;
        height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        text-align: center;
        background-size: cover;
        min-height: 100vh;
    }

    .login-container {
        background: rgba(255, 255, 255, 0.65);
        border-radius: 16px;
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        /*box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);*/
        width: 100%;
        max-width: 360px;
        padding: 30px;
        color: #fff;
        text-align: center;
    }

    .logo {
        max-width: 60px;
        margin-bottom: 15px;
        border-radius: 36px;
    }

    .brand-name {
        font-size: 1.5rem;
        font-weight: bold;
        margin-bottom: 25px;
        color: #050505ff;
    }

    .form-group {
        margin-bottom: 18px;
        text-align: left;
        
    }

    .form-group label {
        font-weight: 500;
        margin-bottom: 6px;
        display: block;
        color: #050505ff; /*For word email and password*/
    }

    .form-control {
        border: none;
        border-radius: 8px;
        padding: 10px 0px;
        width: 100%;
        background: rgba(255, 255, 255, 0.15);
    }

    .form-control::placeholder {
        color: #050505ff;
    }

    .form-control:focus {
        background-color: rgba(255, 255, 255, 0.25);
        outline: none;
        box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.3);
    }

    .btn-main {
        border: none;
        padding: 12px 0px;
        border-radius: 8px;
        width: 100%;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        display: block;
        margin-top: 10px;
    }

    .btn-login {
        background-color: #258ab2ff;
        color: #ffffffff;
    }

    .btn-login:hover {
        background-color: #258ab2ff;
    }

    .btn-signup {
        background-color: rgba(162, 163, 167, 0.3);
        color: #000000ff;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .btn-signup:hover {
        background-color: rgba(255, 255, 255, 0.45);
        color: #000;
    }

    .forgot-password-link {
        display: block;
        margin-top: 12px;
        font-size: 0.9rem;
        color: #0a0a0aff;
    }

    .forgot-password-link:hover {
        text-decoration: underline;
    }

    .alert {
        margin-bottom: 20px;
        padding: 12px;
        border-radius: 8px;
        font-size: 0.9rem;
        text-align: left;
        background-color: rgba(255, 0, 0, 0.2);
        color: #fff;
        border: 1px solid rgba(255, 0, 0, 0.4);
    }

    .app-version {
        font-size: 0.75rem;
        color: #000000ff;
        margin-top: 25px;
    }

    @media (max-width: 400px) {
        .login-container {
            padding: 25px 20px;
        }
    }
</style>

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