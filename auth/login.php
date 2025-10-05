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
    $email = trim($_POST['email'] ?? ''); // Use trim() to remove whitespace
    $password = $_POST['password'] ?? '';

    // Basic validation
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // IMPORTANT: Select the 'is_active' column from the users table
        // This query requires the 'is_active' column in the users table.
        $stmt = $conn->prepare("SELECT id, name, email, role, password, profile_img, is_active FROM users WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
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

                            // NOTE: Changed 'audit_log' to 'audit_logs' for common convention.
                            // If your table is named 'audit_log' (singular), revert this change.
                            $stmt_log = $conn->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (?, ?, ?)");
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
                            'id' => $user['id'],
                            'name' => $user['name'],
                            'email' => $user['email'],
                            'role' => $user['role'],
                            'profile_img' => $user['profile_img']
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <link rel="stylesheet" href="../assets/styles/style.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link rel="stylesheet" as="style" onload="this.rel='stylesheet'"
        href="https://fonts.googleapis.com/css2?display=swap&family=Noto+Sans:wght@400;500;700;900&family=Space+Grotesk:wght@400;500;700">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon"
        href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png">

</head>

<style>
    .auth-login-body {
        font-family: "Space Grotesk", "Noto Sans", sans-serif;
    }
</style>

<body class="auth-login-body">
    <div class="auth-login-container">
        <div class="auth-login-card">
            <div class="auth-logo-section">
                <div class="auth-logo">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100%" height="100%">
                        <image
                            href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png"
                            x="0" y="0" width="100" height="100" />
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
                    <input type="email" class="auth-form-control" id="email" name="email"
                        placeholder="Enter your email address" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required>
                </div>

                <div class="auth-form-group">
                    <label for="password" class="auth-form-label fw-bolder text-black-50">Password</label>
                    <div class="auth-password-input">
                        <input type="password" class="auth-form-control" id="password" name="password"
                            placeholder="Enter your password" required>
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
            <p><a href="#" class="auth-link">Contact us</a></p>
        </div>
    </div>

    <div class="auth-modal" id="authPrivacyModal">
        <div class="auth-modal-content">
            <div class="auth-modal-header">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div
                        style="background-color: rgba(255, 255, 255, 0.2); border-radius: 8px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
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
                <p class="mb-4">Welcome to <strong>CHRONONAV</strong>! Your privacy is very important to us. This
                    Privacy
                    Policy explains how we collect, use, and protect your information when you use our mobile and web
                    application.</p>

                <h4 class="section-title">
                    <span class="privacy-icon">
                        <i class="fas fa-info-circle"></i>
                    </span>
                    Information We Collect
                </h4>
                <p>We collect only the information necessary to provide our services:</p>
                <ul class="custom-list">
                    <li><strong>Account Information:</strong> Name, email address, and student ID (if provided during
                        registration)</li>
                    <li><strong>Schedule Data:</strong> Uploaded study loads (PDFs or images) processed using Optical
                        Character Recognition (OCR)</li>
                    <li><strong>Location Data:</strong> GPS location for navigation within the campus</li>
                    <li><strong>Device Information:</strong> Basic device and app usage data (for troubleshooting and
                        improvements)</li>
                </ul>

                <h4 class="section-title">
                    <span class="privacy-icon">
                        <i class="fas fa-cogs"></i>
                    </span>
                    How We Use Your Information
                </h4>
                <p>Your data is used to:</p>
                <ul class="custom-list">
                    <li>Generate and organize your personalized schedule</li>
                    <li>Provide real-time navigation and directions within the campus</li>
                    <li>Send reminders and notifications for upcoming classes</li>
                    <li>Improve app features, performance, and user experience</li>
                </ul>
                <div class="highlight-box">
                    <p class="mb-0"><strong>We do not sell, rent, or share your information with third parties.</strong>
                    </p>
                </div>

                <h4 class="section-title">
                    <span class="privacy-icon">
                        <i class="fas fa-database"></i>
                    </span>
                    Data Storage and Security
                </h4>
                <ul class="custom-list">
                    <li>All schedule and navigation data are securely stored in encrypted databases</li>
                    <li>Access is limited to authorized CHRONONAV developers and administrators</li>
                    <li>Offline data (such as cached maps) is stored only on your device and is cleared when you
                        uninstall
                        the app</li>
                </ul>

                <h4 class="section-title">
                    <span class="privacy-icon">
                        <i class="fas fa-share-alt"></i>
                    </span>
                    Data Sharing
                </h4>
                <p>We may share anonymized usage statistics (e.g., most used features, error logs) to improve the
                    system.
                </p>
                <p>We will never disclose personal information unless:</p>
                <ul class="custom-list">
                    <li>Required by law, or</li>
                    <li>Necessary to protect the rights, property, or safety of CHRONONAV users</li>
                </ul>

                <h4 class="section-title">
                    <span class="privacy-icon">
                        <i class="fas fa-user-check"></i>
                    </span>
                    Your Rights
                </h4>
                <p>You have the right to:</p>
                <ul class="custom-list">
                    <li><strong>Access</strong> your personal information stored in CHRONONAV</li>
                    <li><strong>Update or correct</strong> your account details</li>
                    <li><strong>Request deletion</strong> of your account and related data at any time</li>
                </ul>
                <p>To exercise these rights, please contact us at: <strong>chrononav.support@yourdomain.com</strong></p>

                <h4 class="section-title">
                    <span class="privacy-icon">
                        <i class="fas fa-child"></i>
                    </span>
                    Children's Privacy
                </h4>
                <p>CHRONONAV is designed for university students and staff. We do not knowingly collect personal data
                    from
                    children under 13 years old.</p>

                <h4 class="section-title">
                    <span class="privacy-icon">
                        <i class="fas fa-sync-alt"></i>
                    </span>
                    Updates to this Privacy Policy
                </h4>
                <p>We may update this Privacy Policy from time to time to reflect changes in technology, laws, or our
                    services. We will notify users of significant updates via in-app notifications or email.</p>

                <h4 class="section-title">
                    <span class="privacy-icon">
                        <i class="fas fa-envelope"></i>
                    </span>
                    Contact Us
                </h4>
                <p>If you have any questions, feedback, or concerns about this Privacy Policy, please contact us at:</p>
                <div class="contact-info">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <strong>Email:</strong> support@ChronoNav.com
                    </div>
                </div>
                <div class="contact-info">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div>
                        <strong>Address:</strong> Sanciangko St, Cebu City, 6000 Cebu
                    </div>
                </div>
            </div>
            <div class="auth-modal-footer">
                <button class="auth-btn-login" onclick="closeAuthPrivacy()">
                    <i class="fas fa-times me-2"></i> Close
                </button>
            </div>
        </div>
    </div>

    <div class="auth-modal" id="authTermsModal">
        <div class="auth-modal-content">
            <div class="auth-modal-header">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div
                        style="background-color: rgba(255, 255, 255, 0.2); border-radius: 8px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
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
                <p class="mb-4">Welcome to <strong>CHRONONAV</strong>! These Terms of Service ("Terms") govern your
                    use
                    of
                    the CHRONONAV mobile and web application. By downloading, accessing, or using CHRONONAV, you
                    agree
                    to
                    comply with these Terms. Please read them carefully.</p>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-check-circle"></i>
                    </span>
                    Acceptance of Terms
                </h4>
                <p>By using CHRONONAV, you confirm that you:</p>
                <ul class="custom-list">
                    <li>Are at least 13 years of age (or the minimum required by your institution)</li>
                    <li>Agree to follow these Terms and our Privacy Policy</li>
                    <li>Use CHRONONAV only for lawful academic and personal purposes</li>
                </ul>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-list-alt"></i>
                    </span>
                    Description of Service
                </h4>
                <p>CHRONONAV provides:</p>
                <ul class="custom-list">
                    <li>OCR-based schedule import from official study loads</li>
                    <li>Personalized timetable management</li>
                    <li>Campus navigation and turn-by-turn directions</li>
                    <li>Class reminders and alerts</li>
                    <li>Limited offline navigation functionality</li>
                </ul>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-user-shield"></i>
                    </span>
                    User Responsibilities
                </h4>
                <p>You agree not to:</p>
                <ul class="custom-list">
                    <li>Upload false or misleading information</li>
                    <li>Share your account credentials with others</li>
                    <li>Use CHRONONAV to engage in cheating, harassment, or illegal activities</li>
                    <li>Tamper with or attempt to hack the system</li>
                </ul>
                <p>You are responsible for ensuring your device is compatible with the app.</p>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-database"></i>
                    </span>
                    Data and Privacy
                </h4>
                <p>Our use of your data is described in the <strong>Privacy Policy</strong>. By using CHRONONAV, you
                    consent
                    to the collection and use of your information as outlined there.</p>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-check-double"></i>
                    </span>
                    Accuracy of Information
                </h4>
                <p>While CHRONONAV strives to provide accurate schedules and navigation, we do not guarantee that:
                </p>
                <ul class="custom-list">
                    <li>All schedules imported via OCR will be 100% error-free</li>
                    <li>Campus navigation routes will always reflect real-time construction, closures, or events
                    </li>
                </ul>
                <p>Users should verify critical details with their school's official sources.</p>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-copyright"></i>
                    </span>
                    Intellectual Property
                </h4>
                <p>All rights, trademarks, and content within CHRONONAV belong to the development team and/or the
                    affiliated
                    university. You may not copy, modify, or redistribute CHRONONAV without permission.</p>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </span>
                    Limitation of Liability
                </h4>
                <p>CHRONONAV is provided <strong>"as is."</strong> We are not responsible for:</p>
                <ul class="custom-list">
                    <li>Missed classes, delays, or wrong directions caused by inaccurate data</li>
                    <li>Damages caused by reliance on the app in critical situations</li>
                </ul>
                <p>Your use of the app is at your own risk.</p>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-sync-alt"></i>
                    </span>
                    Service Modifications
                </h4>
                <p>We reserve the right to update, modify, or discontinue CHRONONAV at any time, with or without
                    notice.
                </p>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-ban"></i>
                    </span>
                    Termination
                </h4>
                <p>We may suspend or terminate your access if you violate these Terms. You may also delete your
                    account
                    at
                    any time.</p>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-balance-scale"></i>
                    </span>
                    Governing Law
                </h4>
                <p>These Terms shall be governed by the laws of the Republic of the Philippines.</p>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-envelope"></i>
                    </span>
                    Contact Us
                </h4>
                <p>If you have questions about these Terms, please contact us:</p>
                <div class="contact-info">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <strong>Email:</strong> support@ChronoNav.com
                    </div>
                </div>
                <div class="contact-info">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div>
                        <strong>Address:</strong> Sanciangko St, Cebu City, 6000 Cebu
                    </div>
                </div>
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
        document.getElementById('togglePassword').addEventListener('click', function () {
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
        document.getElementById('authPrivacyModal').addEventListener('click', function (e) {
            if (e.target === this) closeAuthPrivacy();
        });

        document.getElementById('authTermsModal').addEventListener('click', function (e) {
            if (e.target === this) closeAuthTerms();
        });
    </script>
</body>

</html>