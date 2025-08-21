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
  <style>
    body {
      /*background: url('https://images.unsplash.com/photo-1523240795612-9a054b0db644') no-repeat center center/cover;*/
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Segoe UI', Roboto, Arial, sans-serif;
      margin: 0;
      background-color: #ffffffff;
    }

    .forgot-password-container {
      background: rgba(255, 255, 255, 0.65);
      border-radius: 20px;
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      box-shadow: 0 12px 35px rgba(0, 0, 0, 0.25);
      padding: 40px;
      max-width: 400px;
      width: 100%;
      color: #000000ff;
      text-align: center;
    }

    .forgot-password-container h2 {
      font-weight: 700;
      font-size: 1.5rem;
      color: #000000ff;
      margin-bottom: 20px;
    }

    .forgot-password-container p {
      font-size: 0.95rem;
      color: #080808ff;
      margin-bottom: 25px;
    }

    .form-label {
      font-weight: 600;
      text-align: left;
      display: block;
      color: #ffffff;
      margin-bottom: 6px;
    }

    .form-control {
        border: none;
        border-radius: 8px;
        padding: 10px 0px;
        width: 100%;
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
    }

    .form-control:focus {
      border: 2px solid #0d6efd;
      box-shadow: 0 0 8px rgba(13, 110, 253, 0.4);
      background-color: #fff;
    }

    .btn-glassy {
      background: linear-gradient(to right, #4facfe, #00f2fe);
      border: none;
      padding: 12px;
      font-size: 1rem;
      font-weight: bold;
      border-radius: 10px;
      width: 100%;
      color: white;
      transition: all 0.3s ease;
      margin-top: 20px;
    }

    .btn-glassy:hover {
      background: linear-gradient(to right, #00f2fe, #4facfe);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

    .alert {
      margin-top: 15px;
      border-radius: 10px;
      font-size: 0.9rem;
      text-align: center;
    }

    .auth-links {
      margin-top: 25px;
    }

    .auth-links a {
      color: #000000ff;
      text-decoration: underline;
      font-size: 0.95rem;
    }

    .auth-links a:hover {
      color: #cce5ff;
    }

    .logo {
      max-width: 60px;
      margin-bottom: 15px;
      border-radius: 36px;
    }

    .app-version {
      margin-top: 50px;
      font-size: 0.8rem;
      color: #000000ff;
    }
  </style>
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
