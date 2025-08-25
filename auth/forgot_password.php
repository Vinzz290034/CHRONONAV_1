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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Forgot Password - ChronoNav</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"/>

  <link rel="stylesheet" href="../assets/css/other_css/password_forgot.css">
</head>
<body>
  <div class="forgot-password-container">
    <img src="../../chrononav_webz/assets/img/chrononav_logo.jpg" class="logo" alt="ChronoNav Logo" />
    <h2>CHRONONAV</h2>
    <p>Enter your email to receive a reset link</p>

    <?php if ($message): ?>
      <div class="alert alert-<?= htmlspecialchars($message_type) ?>">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="mb-3 text-start">
        <label for="email" class="form-label">Email address</label>
        <input type="email" class="form-control" id="email" name="email" placeholder="your@email.com" required>
      </div>
      <button type="submit" class="btn btn-glassy">Send Reset Link</button>
    </form>

    <div class="auth-links">
      <a href="login.php">← Back to Login</a>
    </div>

    <div class="app-version">App Version 1.0.0 · © 2025 ChronoNav</div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
