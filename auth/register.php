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
  <link rel="stylesheet" href="../assets/css/other_css/register.css"> 
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
