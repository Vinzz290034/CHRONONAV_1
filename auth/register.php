<?php
session_start();
require_once '../config/db_connect.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $role     = $_POST['role'];
    $course   = trim($_POST['course']);
    $dept     = trim($_POST['department']);

    if (empty($name) || empty($email) || empty($password) || empty($role) || empty($course) || empty($dept)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows > 0) {
            $error = "Email already registered. Please login or use another.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, course, department) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssssss", $name, $email, $hashed_password, $role, $course, $dept);
                if ($stmt->execute()) {
                    $message = "Registration successful! <a href='login.php' class='alert-link'>Login here</a>.";
                } else {
                    $error = "Error during registration: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "Database error: " . $conn->error;
            }
        }
        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register - ChronoNav</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet" />
  <style>
    body {
         font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        /*background: url('https://images.unsplash.com/photo-1523240795612-9a054b0db644') no-repeat center/cover; */
        height: 100vh; 
        display: flex; 
        background-color: #ffffffff;
        align-items: center; 
        justify-content: center; 
        flex-direction: column; 
        text-align: center; 
        color: white; 
        background-size: cover;
        min-height: 100vh;

    }

    .register-container {
        background: rgba(255, 255, 255, 0.65);
        border-radius: 16px;
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        /*box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);*/
        width: 100%;
        max-width: 360px;
        padding: 30px;
        color: white;
        text-align: center;
    }

    .register-box h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #040504ff;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      font-weight: 500;
      margin-bottom: 5px;
    }

    .form-group input,
    .form-group select {
      width: 100%;
      padding: 10px 0px;
      border: none;
      border-radius: 8px;
      background: rgba(255, 255, 255, 0.15);
      color: #040504ff;
      font-size: 16px;
    }

    .form-group input::placeholder {
       color: #040504ff;
    }

    .btn {
      display: block;
      width: 100%;
      padding: 12px;
      background: #4caf50;
      border: none;
      border-radius: 8px;
      color: #080808ff;
      font-size: 16px;
      font-weight: bold;
      transition: background 0.3s ease;
      cursor: pointer;
    }

    .btn:hover {
      background: #43a047;
    }

    .login-link {
      text-align: center;
      margin-top: 15px;
      color: #000000ff;
      margin-left: 20px;
      text-decoration: underline;
    }

    .error,
    .message {
      margin-bottom: 15px;
      padding: 10px;
      border-radius: 8px;
      text-align: center;
    }

    .error {
      background: #ffcdd2;
      color: #c62828;
    }

    .message {
      background: #c8e6c9;
      color: #2e7d32;
    }

    .logo {
      display: block;
      margin: 0 auto 10px;
      max-width: 60px;
      height: auto;
      border-radius: 36px;
    }

    @media (max-width: 480px) {
      .register-box {
        padding: 30px 20px;
      }
    }
  </style>
</head>
<body>
  <div class="register-container">
    <form class="register-box" method="POST">
      <img src="../assets/img/chrononav_logo.jpg" alt="ChronoNav Logo" class="logo" />
      <h2>Create Account</h2>

      <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if (!empty($message)): ?>
        <div class="message"><?= $message ?></div>
      <?php endif; ?>

      <div class="form-group">
        <label for="email">full name</label>
        <input type="text" name="name" placeholder="Your full name" required />
      </div>

      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" name="email" placeholder="Enter your email" required />
      </div>

      <div class="form-group">
        <label for="email">Passwors</label>
        <input type="password" name="password" placeholder="Create a password" required />
      </div>

      <div class="form-group">
        <select name="role" required>
          <option value="">Select Role</option>
          <option value="user">User</option>
          <option value="faculty">Faculty</option>
          <option value="admin">Admin</option>
        </select>
      </div>

      <div class="form-group">
        <label for="email">Course</label>
        <input type="text" name="course" placeholder="Your course" required />
      </div>

      <div class="form-group">
        <label for="email">Department</label>
        <input type="text" name="department" placeholder="Your department" required />
      </div>

      <button type="submit" class="btn">Register</button>

      <div class="login-link">
        Already have an account? <a href="login.php" style="color: #050505ff;">Login here</a>
      </div>
    </form>
  </div>
</body>
</html>
