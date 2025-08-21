<?php
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';

// Check if the user is logged in and user data is available
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header('Location: ../../auth/login.php'); // Redirect to login if not logged in
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['id']; // Primary key for the user

$message = '';
$error = '';

// Define upload directory and allowed file types
$upload_dir = '../../uploads/profiles/'; // Path relative to this file
$allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
$max_file_size = 5 * 1024 * 1024; // 5MB

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newName = trim($_POST['name'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');
    $newCourse = trim($_POST['course'] ?? '');
    $newDepartment = trim($_POST['department'] ?? '');

    $newProfileImgPath = $user['profile_img']; // Default to current image path

    // --- Handle Profile Image Upload ---
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['profile_picture']['name'];
        $file_tmp_name = $_FILES['profile_picture']['tmp_name'];
        $file_size = $_FILES['profile_picture']['size'];
        $file_type = $_FILES['profile_picture']['type'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Validate file properties
        if (!in_array($file_ext, $allowed_types)) {
            $error = 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.';
        } elseif ($file_size > $max_file_size) {
            $error = 'File size exceeds 5MB limit.';
        } else {
            // Generate a unique file name to prevent conflicts
            $unique_file_name = uniqid('profile_', true) . '.' . $file_ext;
            $destination_path = $upload_dir . $unique_file_name;

            // Move the uploaded file
            if (move_uploaded_file($file_tmp_name, $destination_path)) {
                $newProfileImgPath = 'uploads/profiles/' . $unique_file_name; // Path to store in DB
                // Delete old profile picture if it's not the default one
                if ($user['profile_img'] && $user['profile_img'] !== 'uploads/profiles/default-avatar.png') {
                    $old_img_full_path = '../../' . $user['profile_img'];
                    if (file_exists($old_img_full_path)) {
                        unlink($old_img_full_path);
                    }
                }
            } else {
                $error = 'Failed to upload profile picture.';
            }
        }
    } elseif (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle other upload errors (e.g., UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE)
        $error = 'An error occurred during file upload. Error code: ' . $_FILES['profile_picture']['error'];
    }

    // --- Proceed with database update if no file upload errors occurred ---
    if (empty($error)) { // Only proceed if no errors from file upload or previous validation
        // Basic server-side validation for text fields
        if (empty($newName)) {
            $error = 'Name cannot be empty.';
        } elseif (empty($newEmail)) {
            $error = 'Email cannot be empty.';
        } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format.';
        } else {
            // Check if the new email already exists for another user
            $stmt_check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            if ($stmt_check_email) {
                $stmt_check_email->bind_param("si", $newEmail, $user_id);
                $stmt_check_email->execute();
                $stmt_check_email->store_result();

                if ($stmt_check_email->num_rows > 0) {
                    $error = 'This email is already in use by another account.';
                } else {
                    // Prepare and execute the update query for allowed fields
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, course = ?, department = ?, profile_img = ? WHERE id = ?");
                    if ($stmt) {
                        $stmt->bind_param("sssssi", $newName, $newEmail, $newCourse, $newDepartment, $newProfileImgPath, $user_id);

                        if ($stmt->execute()) {
                            $message = 'Profile updated successfully!';
                            // Update the session user data immediately
                            $_SESSION['user']['name'] = $newName;
                            $_SESSION['user']['email'] = $newEmail;
                            $_SESSION['user']['course'] = $newCourse;
                            $_SESSION['user']['department'] = $newDepartment;
                            $_SESSION['user']['profile_img'] = $newProfileImgPath;

                            // Re-fetch updated user data for display on the page
                            $user['name'] = $newName;
                            $user['email'] = $newEmail;
                            $user['course'] = $newCourse;
                            $user['department'] = $newDepartment;
                            $user['profile_img'] = $newProfileImgPath;
                        } else {
                            $error = 'Error updating profile: ' . $stmt->error;
                        }
                        $stmt->close();
                    } else {
                        $error = 'Error preparing statement: ' . $conn->error;
                    }
                }
                $stmt_check_email->close();
            } else {
                $error = 'Error preparing email check statement: ' . $conn->error;
            }
        }
    }
}

// Ensure the form displays current data or new data after a failed submission
$display_name = htmlspecialchars($user['name'] ?? '');
$display_email = htmlspecialchars($user['email'] ?? '');
$display_course = htmlspecialchars($user['course'] ?? '');
$display_department = htmlspecialchars($user['department'] ?? '');
$display_profile_img = htmlspecialchars($user['profile_img'] ?? 'uploads/profiles/default-avatar.png');

// If there was an error with submission, display the values the user tried to enter (excluding file)
if (!empty($error) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $display_name = htmlspecialchars($newName);
    $display_email = htmlspecialchars($newEmail);
    $display_course = htmlspecialchars($newCourse);
    $display_department = htmlspecialchars($newDepartment);
    // $display_profile_img remains the existing one if upload failed, or the new one if succeeded.
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .profile-card {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .profile-img-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .profile-img-container img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #ddd;
        }
        .file-upload-wrapper {
            margin-top: 15px;
        }
        .file-upload-wrapper input[type="file"] {
            display: block;
            margin-top: 5px;
        }
    </style>
</head>
<body class="container mt-5">

    <div class="profile-card">
        <div class="profile-img-container">
            <img src="../../<?= $display_profile_img ?>" alt="Profile Picture">
            <p class="mt-2 text-muted">Current Role: <strong><?= htmlspecialchars(ucfirst($user['role'] ?? 'user')) ?></strong></p>
        </div>

        <h3 class="mb-4 text-center">Welcome, <?= htmlspecialchars($user['name'] ?? 'User') ?>!</h3>
        <p class="lead text-center">Here you can view and update your profile information.</p>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success" role="alert">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <hr>

        <h4>Edit Your Information</h4>
        <form action="edit.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= $display_name ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= $display_email ?>" required>
            </div>
            <div class="form-group">
                <label for="course">Course:</label>
                <input type="text" class="form-control" id="course" name="course" value="<?= $display_course ?>">
            </div>
            <div class="form-group">
                <label for="department">Department:</label>
                <input type="text" class="form-control" id="department" name="department" value="<?= $display_department ?>">
            </div>

            <div class="form-group file-upload-wrapper">
                <label for="profile_picture">Upload New Profile Picture (Max 5MB, JPG, PNG, GIF):</label>
                <input type="file" class="form-control-file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif">
            </div>

            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="dashboard.php" class="btn btn-secondary ml-2">Go to Dashboard</a>
            <a href="view_profile.php" class="btn btn-info ml-2">View Profile</a>
        </form>
    </div>

    <script src="../../assets/js/jquery.min.js"></script>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/profile.js"></script>

</body>
</html>