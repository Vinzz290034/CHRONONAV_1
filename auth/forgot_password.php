<?php
require_once '../config/db_connect.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    if (empty($email)) {
        $message = 'Please enter your email address.';
        $message_type = 'danger';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email format.';
        $message_type = 'danger';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $message = 'If an account with that email exists, a password reset link has been sent.';
            $message_type = 'success';
            $stmt->close();
        } else {
            $message = 'Database error. Please try again later.';
            $message_type = 'danger';
            error_log("Forgot password DB prepare failed: " . $conn->error);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - ChronoNav</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png">

    <style>
        /* Forgot Password Page Specific Styles */
        .auth-forgot-body {
            font-family: Inter, "Noto Sans", sans-serif;
            background: linear-gradient(rgba(62, 153, 244, 0.85), rgba(6, 168, 249, 0.85)), 
                        url('https://res.cloudinary.com/deua2yipj/image/upload/v1759258431/chrononav_bg_l38ntk.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #111418;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }
        
        .auth-forgot-container {
            width: 100%;
            max-width: 420px;
        }
        
        .auth-forgot-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            padding: 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .auth-logo-section {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-logo {
            width: 60px;
            height: 60px;
            margin: 0 auto 1rem;
        }
        
        .auth-brand-name {
            font-size: 1.75rem;
            font-weight: 700;
            color: #111418;
            margin-bottom: 0.5rem;
        }
        
        .auth-brand-tagline {
            color: #5f7d8c;
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        
        .auth-form-group {
            margin-bottom: 1.5rem;
        }
        
        .auth-form-label {
            font-weight: 500;
            color: #111418;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .auth-form-control {
            background-color: rgba(248, 249, 250, 0.95);
            border: 1px solid #dbe2e6;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            height: 48px;
            width: 100%;
            display: block;
            font-size: 1rem;
            color: #333;
            transition: border-color 0.25s ease, box-shadow 0.25s ease;
        }
        
        .auth-form-control:focus {
            background-color: rgba(248, 249, 250, 1);
            border-color: #3e99f4;
            outline: none;
            box-shadow: 0 0 0 3px rgba(62, 153, 244, 0.2);
        }
        
        .auth-btn-reset {
            background-color: #06a8f9;
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
        
        .auth-btn-reset:hover {
            background-color: #0588d1;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(6, 168, 249, 0.3);
        }
        
        .auth-alert {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        
        .auth-message {
            background-color: #d1edff;
            color: #05547e;
            border: 1px solid #b3d9ff;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        
        .auth-links-section {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .auth-link {
            color: #3e99f4;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .auth-link:hover {
            color: #0588d1;
            text-decoration: underline;
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            color: white;
            font-size: 0.8rem;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .auth-footer a {
            color: white;
            text-decoration: none;
            font-weight: 500;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        /* Modal Styles for Auth Pages */
        .auth-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .auth-modal-content {
            max-width: 800px;
            width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }
        
        .auth-modal-header {
            background: linear-gradient(135deg, #3e99f4, #06a8f9);
            color: white;
            padding: 1.5rem 2rem;
            position: relative;
        }
        
        .auth-modal-body {
            padding: 2rem;
            overflow-y: auto;
            flex-grow: 1;
        }
        
        .auth-modal-footer {
            padding: 1.5rem 2rem;
            background-color: #f0f2f5;
            border-top: 1px solid #dbe2e6;
            display: flex;
            justify-content: flex-end;
        }
        
        .auth-modal-close {
            position: absolute;
            top: 1.5rem;
            right: 2rem;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .auth-modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }
        
        @media (max-width: 480px) {
            .auth-forgot-card {
                padding: 2rem 1.5rem;
            }
            
            .auth-logo {
                width: 50px;
                height: 50px;
            }
            
            .auth-brand-name {
                font-size: 1.5rem;
            }
            
            .auth-forgot-body {
                padding: 15px;
                background-attachment: scroll;
            }
            
            .auth-modal {
                padding: 10px;
            }
            
            .auth-modal-body {
                padding: 1.5rem;
            }
            
            .auth-modal-header, .auth-modal-footer {
                padding: 1.25rem 1.5rem;
            }
            
            .auth-modal-close {
                top: 1.25rem;
                right: 1.5rem;
            }
        }
        
        /* Animation for better visual appeal */
        .auth-forgot-card {
            animation: authFadeInUp 0.6s ease-out;
        }
        
        @keyframes authFadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="auth-forgot-body">
    <div class="auth-forgot-container">
        <div class="auth-forgot-card">
            <div class="auth-logo-section">
                <div class="auth-logo">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100%" height="100%">
                        <image href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png" x="0" y="0" width="100" height="100" />
                    </svg>
                </div>
                <h1 class="auth-brand-name text-black-50">ChronoNav</h1>
                <p class="auth-brand-tagline">Reset your password</p>
            </div>
            
            <?php if ($message): ?>
                <div class="<?= $message_type === 'success' ? 'auth-message' : 'auth-alert' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="forgotForm">
                <div class="auth-form-group">
                    <label for="email" class="auth-form-label fw-bold text-black-50">Email Address</label>
                    <input type="email" class="auth-form-control" id="email" name="email" placeholder="Enter your email address" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                
                <button type="submit" class="auth-btn-reset">Send Reset Link</button>
            </form>
            
            <div class="auth-links-section">
                <a href="login.php" class="auth-link">
                    <i class="fas fa-arrow-left me-2"></i>Back to Login
                </a>
            </div>
            
            <p class="auth-terms-text" style="text-align: center; color: #5f7d8c; font-size: 0.8rem; margin-top: 1.5rem; line-height: 1.4;">
                Enter your email address and we'll send you a link to reset your password.
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
                <button class="auth-btn-reset" onclick="closeAuthPrivacy()">
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
                <button class="auth-btn-reset" onclick="closeAuthTerms()">
                    <i class="fas fa-times me-2"></i> Close
                </button>
            </div>
        </div>
    </div>

    <script>
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

        // Form submission feedback
        document.getElementById('forgotForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.auth-btn-reset');
            const originalText = submitBtn.textContent;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
            submitBtn.disabled = true;
            
            // In a real application, you would let the form submit normally
            // This is just for visual feedback
            setTimeout(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }, 2000);
        });
    </script>
</body>
</html>