<?php


session_start();
require_once '../config/db_connect.php';

// Function to get client IP address
function get_client_ip() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

// Function to log login attempts
function log_login_attempt($conn, $username, $status, $user_id = NULL) {
    $ip_address = get_client_ip();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    $stmt = $conn->prepare("INSERT INTO login_attempts (user_id, username, ip_address, status, user_agent) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("issss", $user_id, $username, $ip_address, $status, $user_agent);
        $stmt->execute();
        $stmt->close();
    } else {
        error_log("Failed to prepare statement for login attempt logging: " . $conn->error);
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $_SESSION['message'] = "Please enter both username and password.";
        $_SESSION['message_type'] = "danger";
        header("Location: ../index.php"); // Redirect back to login page
        exit();
    }

    $stmt = $conn->prepare("SELECT id, username, password, name, email, role, course, department, profile_img FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Login Success
            $_SESSION['user'] = $user; // Store user data in session
            $_SESSION['message'] = "Welcome, " . htmlspecialchars($user['name']) . "!";
            $_SESSION['message_type'] = "success";

            // Log successful login
            log_login_attempt($conn, $user['username'], 'success', $user['id']);

            header("Location: ../pages/user/dashboard.php"); // Redirect to dashboard
            exit();
        } else {
            // Login Failed: Incorrect Password
            $_SESSION['message'] = "Invalid username/email or password.";
            $_SESSION['message_type'] = "danger";

            // Log failed login (with known username but incorrect password)
            log_login_attempt($conn, $username, 'failed', $user['id']);

            header("Location: ../index.php"); // Redirect back to login page
            exit();
        }
    } else {
        // Login Failed: User Not Found
        $_SESSION['message'] = "Invalid username/email or password.";
        $_SESSION['message_type'] = "danger";

        // Log failed login (user not found)
        log_login_attempt($conn, $username, 'failed'); // user_id is NULL here

        header("Location: ../index.php"); // Redirect back to login page
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: ../index.php"); // Redirect if not a POST request
    exit();
}
?>