<?php
// CHRONONAV_WEB_DOSS/includes/faculty_edit_profile_handler.php
require_once '../middleware/auth_check.php';
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

requireRole(['faculty']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['message'] = "Invalid request method.";
    $_SESSION['message_type'] = 'danger';
    header('Location: ../pages/faculty/view_profile.php');
    exit();
}

$user_id = $_SESSION['user']['id'];
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$department = trim($_POST['department'] ?? '');
$faculty_id = trim($_POST['faculty_id'] ?? '');
$profile_img_path = $_SESSION['user']['profile_img']; // Default to current image

$updates = [];
$params = [];
$param_types = '';

// Check for changes and prepare update statement
if ($name !== $_SESSION['user']['name']) {
    $updates[] = "name = ?";
    $params[] = $name;
    $param_types .= 's';
}

if ($email !== $_SESSION['user']['email']) {
    // Basic email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "Invalid email format.";
        $_SESSION['message_type'] = 'danger';
        header('Location: ../pages/faculty/view_profile.php');
        exit();
    }
    $updates[] = "email = ?";
    $params[] = $email;
    $param_types .= 's';
}

if ($department !== ($_SESSION['user']['department'] ?? '')) {
    $updates[] = "department = ?";
    $params[] = $department;
    $param_types .= 's';
}

if ($faculty_id !== ($_SESSION['user']['faculty_id'] ?? '')) {
    $updates[] = "faculty_id = ?";
    $params[] = $faculty_id;
    $param_types .= 's';
}

// Handle profile image upload
if (isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] === UPLOAD_ERR_OK) {
    $target_dir = "../uploads/profiles/";
    $imageFileType = strtolower(pathinfo($_FILES['profile_img']['name'], PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'png', 'jpeg', 'gif'];

    if (!in_array($imageFileType, $allowed_types)) {
        $_SESSION['message'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $_SESSION['message_type'] = 'danger';
        header('Location: ../pages/faculty/view_profile.php');
        exit();
    }

    $unique_name = md5(time() . uniqid()) . "." . $imageFileType;
    $target_file = $target_dir . $unique_name;

    if (move_uploaded_file($_FILES['profile_img']['tmp_name'], $target_file)) {
        $profile_img_path = 'uploads/profiles/' . $unique_name;
        $updates[] = "profile_img = ?";
        $params[] = $profile_img_path;
        $param_types .= 's';

        // Delete old profile picture if it's not the default
        $old_img = $_SESSION['user']['profile_img'];
        if ($old_img && $old_img !== 'uploads/profiles/default-avatar.png' && file_exists('../' . $old_img)) {
            unlink('../' . $old_img);
        }
    } else {
        $_SESSION['message'] = "Error uploading profile picture.";
        $_SESSION['message_type'] = 'danger';
        header('Location: ../pages/faculty/view_profile.php');
        exit();
    }
}

if (empty($updates)) {
    $_SESSION['message'] = "No changes were made.";
    $_SESSION['message_type'] = 'info';
    header('Location: ../pages/faculty/view_profile.php');
    exit();
}

// Build and execute the SQL query
$sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
$params[] = $user_id;
$param_types .= 'i';

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param($param_types, ...$params);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Profile updated successfully!";
        $_SESSION['message_type'] = 'success';
        
        // Update session data
        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['email'] = $email;
        $_SESSION['user']['department'] = $department;
        $_SESSION['user']['faculty_id'] = $faculty_id;
        $_SESSION['user']['profile_img'] = $profile_img_path;
    } else {
        $_SESSION['message'] = "Database error: " . $stmt->error;
        $_SESSION['message_type'] = 'danger';
    }
    $stmt->close();
} else {
    $_SESSION['message'] = "Failed to prepare database statement: " . $conn->error;
    $_SESSION['message_type'] = 'danger';
}

$conn->close();
header('Location: ../pages/faculty/view_profile.php');
exit();
?>