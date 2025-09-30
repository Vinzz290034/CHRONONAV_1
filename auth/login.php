<?php
// This is my auth/login.php
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
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="../assets/styles/style.css">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png">

    <!-- <style>
        :root {
            --primary-color: #3e99f4;
            --secondary-color: #f0f2f5;
            --accent-color: #06a8f9;
            --text-dark: #111418;
            --text-muted: #5f7d8c;
            --border-color: #dbe2e6;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            --hover-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }
        
        body {
            font-family: Inter, "Noto Sans", sans-serif;
            background: linear-gradient(rgba(62, 153, 244, 0.85), rgba(6, 168, 249, 0.85)), 
                        url('https://res.cloudinary.com/deua2yipj/image/upload/v1759258431/chrononav_bg_l38ntk.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            padding: 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo {
            width: 60px;
            height: 60px;
            margin: 0 auto 1rem;
        }
        
        .brand-name {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .brand-tagline {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            background-color: rgba(248, 249, 250, 0.8);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            height: 48px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(62, 153, 244, 0.2);
            background-color: white;
            border-color: var(--primary-color);
        }
        
        .password-input {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
        }
        
        .btn-login {
            background-color: var(--accent-color);
            color: white;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            height: 48px;
            width: 100%;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        
        .btn-login:hover {
            background-color: #0588d1;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(6, 168, 249, 0.3);
        }
        
        .alert {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        
        .links-section {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .link:hover {
            color: #0588d1;
            text-decoration: underline;
        }
        
        .separator {
            margin: 0 0.5rem;
            color: var(--text-muted);
        }
        
        .terms-text {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.8rem;
            margin-top: 1.5rem;
            line-height: 1.4;
        }
        
        .footer {
            text-align: center;
            margin-top: 2rem;
            color: white;
            font-size: 0.8rem;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .footer a {
            color: white;
            text-decoration: none;
            font-weight: 500;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .login-card {
                padding: 2rem 1.5rem;
            }
            
            .logo {
                width: 50px;
                height: 50px;
            }
            
            .brand-name {
                font-size: 1.5rem;
            }
            
            body {
                padding: 15px;
                background-attachment: scroll;
            }
        }
        
        /* Animation for better visual appeal */
        .login-card {
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style> -->
</head>
<body class="auth-login-body">
    <div class="auth-login-container">
        <div class="auth-login-card">
            <div class="auth-logo-section">
                <div class="auth-logo">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100%" height="100%">
                        <image href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png" x="0" y="0" width="100" height="100" />
                    </svg>
                </div>
                <h1 class="auth-brand-name text-black-50">ChronoNav</h1>
                <p class="auth-brand-tagline">Navigate your campus with ease</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="auth-alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" id="loginForm">
                <div class="auth-form-group">
                    <label for="email" class="auth-form-label fw-bold text-black-50">Email address</label>
                    <input type="email" class="auth-form-control" id="email" name="email" placeholder="Enter your email address" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                
                <div class="auth-form-group">
                    <label for="password" class="auth-form-label fw-bolder text-black-50">Password</label>
                    <div class="auth-password-input">
                        <input type="password" class="auth-form-control" id="password" name="password" placeholder="Enter your password" required>
                        <button type="button" class="auth-password-toggle" id="togglePassword">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="auth-btn-login">Log in</button>
            </form>
            
            <div class="auth-links-section">
                <a href="forgot_password.php" class="auth-link">Forgot password?</a>
                <span class="auth-separator">•</span>
                <a href="register.php" class="auth-link">Sign up</a>
            </div>
            
            <p class="auth-terms-text">
                By signing up or logging in, you consent to ChronoNav's 
                <a href="#" class="auth-link" onclick="openAuthTerms()">Terms of Use</a> and 
                <a href="#" class="auth-link" onclick="openAuthPrivacy()">Privacy Policy</a>.
            </p>
        </div>
        
        <div class="auth-footer">
            <p>App Version 1.0.0 · © 2025 ChronoNav</p>
            <p><a href="#">Contact us</a></p>
        </div>
    </div>

    <!-- Privacy Policy Modal -->
    <div class="auth-modal" id="authPrivacyModal">
        <div class="auth-modal-content">
            <div class="auth-modal-header">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="background-color: rgba(255, 255, 255, 0.2); border-radius: 8px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div>
                        <h2 style="margin-bottom: 0.25rem;">Privacy Policy</h2>
                        <p style="margin-bottom: 0; opacity: 0.75;">Last updated: September 30, 2025</p>
                    </div>
                </div>
                <button class="auth-modal-close" onclick="closeAuthPrivacy()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="auth-modal-body">
                <!-- Privacy Policy content would go here -->
                <p>Privacy policy content...</p>
            </div>
            <div class="auth-modal-footer">
                <button class="auth-btn-login" onclick="closeAuthPrivacy()">
                    <i class="fas fa-times me-2"></i> Close
                </button>
            </div>
        </div>
    </div>
    
    <!-- Terms of Service Modal -->
    <div class="auth-modal" id="authTermsModal">
        <div class="auth-modal-content">
            <div class="auth-modal-header">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="background-color: rgba(255, 255, 255, 0.2); border-radius: 8px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <div>
                        <h2 style="margin-bottom: 0.25rem;">Terms of Service</h2>
                        <p style="margin-bottom: 0; opacity: 0.75;">Last updated: September 30, 2025</p>
                    </div>
                </div>
                <button class="auth-modal-close" onclick="closeAuthTerms()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="auth-modal-body">
                <!-- Terms of Service content would go here -->
                <p>Terms of service content...</p>
            </div>
            <div class="auth-modal-footer">
                <button class="auth-btn-login" onclick="closeAuthTerms()">
                    <i class="fas fa-times me-2"></i> Close
                </button>
            </div>
        </div>
    </div>

    <script>
        // Password visibility toggle
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Functions for modals
        function openAuthPrivacy() {
            document.getElementById('authPrivacyModal').style.display = 'flex';
            event.preventDefault();
        }
        
        function closeAuthPrivacy() {
            document.getElementById('authPrivacyModal').style.display = 'none';
        }
        
        function openAuthTerms() {
            document.getElementById('authTermsModal').style.display = 'flex';
            event.preventDefault();
        }
        
        function closeAuthTerms() {
            document.getElementById('authTermsModal').style.display = 'none';
        }
        
        // Close modals when clicking outside
        document.getElementById('authPrivacyModal').addEventListener('click', function(e) {
            if (e.target === this) closeAuthPrivacy();
        });
        
        document.getElementById('authTermsModal').addEventListener('click', function(e) {
            if (e.target === this) closeAuthTerms();
        });
    </script>
</body>
</html>