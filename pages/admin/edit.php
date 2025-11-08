<?php
// pages/admin/edit.php
session_start();
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Check if the user is an admin using the custom function
requireRole(['admin']);

$user = $_SESSION['user'];
$user_id = $user['id'];

// Page specific variables
$page_title = "Edit Profile";
$current_page = "profile";

$message = '';
$error = '';

// Define upload directory and allowed file types
$upload_dir = '../../uploads/profiles/';
$allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
$max_file_size = 5 * 1024 * 1024;

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newName = trim($_POST['name'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');
    $newCourse = trim($_POST['course'] ?? '');
    $newDepartment = trim($_POST['department'] ?? '');

    $newProfileImgPath = $user['profile_img'];

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
                $newProfileImgPath = 'uploads/profiles/' . $unique_file_name;
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
        $error = 'An error occurred during file upload. Error code: ' . $_FILES['profile_picture']['error'];
    }

    // --- Proceed with database update if no file upload errors occurred ---
    if (empty($error)) {
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

// If there was an error with submission, display the values the user tried to enter
if (!empty($error) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $display_name = htmlspecialchars($newName);
    $display_email = htmlspecialchars($newEmail);
    $display_course = htmlspecialchars($newCourse);
    $display_department = htmlspecialchars($newDepartment);
}

// Variables for header
$display_username = htmlspecialchars($user['name'] ?? 'Admin');
$display_user_role = htmlspecialchars($user['role'] ?? 'Admin');
$profile_img_src = '../../uploads/profiles/default-avatar.png';
if (!empty($user['profile_img']) && file_exists('../../' . $user['profile_img'])) {
    $profile_img_src = '../../' . $user['profile_img'];
}

// Use admin-specific templates
require_once '../../templates/admin/header_admin.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'ChronoNav - Edit Profile' ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link rel="stylesheet" as="style" onload="this.rel='stylesheet'"
        href="https://fonts.googleapis.com/css2?display=swap&family=Noto+Sans:wght@400;500;700;900&family=Space+Grotesk:wght@400;500;700">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon"
        href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png">

    <style>
        body {
            font-family: "Space Grotesk", "Noto Sans", sans-serif;
            background-color: #fff;
        }

        /* Exact styles from the first code */
        .layout-content-container {
            max-width: 80%;
            flex: 1;
            margin: 0 auto;
            margin-left: 20%;
        }

        .class-item {
            min-height: 72px;
            background-color: #f8fafc;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .class-icon {
            width: 48px;
            height: 48px;
            background-color: #e7edf4;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .floating-btn {
            background-color: #565e64;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            position: fixed;
            bottom: 1.25rem;
            right: 1.25rem;
            color: white;
            text-decoration: none;
        }

        .floating-btn.fw-bold {
            background-color: #2E78C6;
        }

        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        @media (min-width: 768px) {
            .floating-btn {
                position: static;
                width: auto;
                height: auto;
                border-radius: 9999px;
                padding: 0.875rem 1.5rem;
                gap: 1rem;
            }
        }

        /* Additional styles for profile page */
        .main-dashboard-content {
            padding: 2rem 1rem;
        }

        .alert {
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }

        [type=button]:not(:disabled),
        [type=reset]:not(:disabled),
        [type=submit]:not(:disabled),
        button:not(:disabled) {
            cursor: pointer;
            background-color: #f0f2f5;
            color: #111418;
            font-weight: bold;
            border: none;
            border-radius: 0.75rem;
        }

        .profile-card {
            background: #fff;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .profile-img-container {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-img-container img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #e7edf4;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-control {
            border: 1px solid #cedce8;
            border-radius: 0.5rem;
            padding: 0.75rem;
            font-family: "Space Grotesk", "Noto Sans", sans-serif;
        }

        .form-control:focus {
            border-color: #2E78C6;
            box-shadow: 0 0 0 0.2rem rgba(46, 120, 198, 0.25);
        }

        .btn-primary {
            background-color: #2E78C6;
            border-color: #2E78C6;
            padding: 0.75rem 1.5rem;
            font-weight: bold;
        }

        .btn-primary:hover {
            background-color: #2563b0;
            border-color: #2563b0;
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 12px;
            height: 12px;
        }

        ::-webkit-scrollbar-track {
            background: #ffffff;
        }

        ::-webkit-scrollbar-thumb {
            background-color: #737373;
            border-radius: 6px;
            border: 3px solid #ffffff;
        }

        ::-webkit-scrollbar-thumb:hover {
            background-color: #2e78c6;
        }
    </style>
</head>

<body>
    <?php require_once '../../templates/admin/sidenav_admin.php'; ?>

    <div class="layout-content-container d-flex flex-column mb-5 p-3 px-5 justify-content-end">
        <!-- Header -->
        <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
            <p class="text-dark fw-bold fs-3 mb-0" style="min-width: 288px;">Edit Profile</p>
        </div>

        <!-- Profile Card Section -->
        <div class="profile-card">
            <div class="profile-img-container">
                <img src="../../<?= $display_profile_img ?>" alt="Profile Picture" class="img-fluid">
                <p class="mt-2 text-muted">Current Role:
                    <strong><?= htmlspecialchars(ucfirst($user['role'] ?? 'user')) ?></strong>
                </p>
            </div>

            <h3 class="mb-4 text-center">Welcome, <?= htmlspecialchars($user['name'] ?? 'User') ?>!</h3>
            <p class="lead text-center">Here you can view and update your profile information.</p>

            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <hr>

            <h4 class="text-dark fw-bold fs-5 mb-4">Edit Your Information</h4>
            <form action="edit.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name" class="form-label fw-medium">Name:</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= $display_name ?>" required>
                </div>
                <div class="form-group">
                    <label for="email" class="form-label fw-medium">Email:</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= $display_email ?>"
                        required>
                </div>
                <div class="form-group">
                    <label for="course" class="form-label fw-medium">Course:</label>
                    <input type="text" class="form-control" id="course" name="course" value="<?= $display_course ?>">
                </div>
                <div class="form-group">
                    <label for="department" class="form-label fw-medium">Department:</label>
                    <input type="text" class="form-control" id="department" name="department"
                        value="<?= $display_department ?>">
                </div>

                <div class="form-group">
                    <label for="profile_picture" class="form-label fw-medium">Upload New Profile Picture (Max 5MB, JPG,
                        PNG, GIF):</label>
                    <input type="file" class="form-control" id="profile_picture" name="profile_picture"
                        accept="image/jpeg,image/png,image/gif">
                </div>

                <div class="d-flex gap-3 mt-4">
                    <button type="submit" class="btn btn-primary px-4 py-2">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary px-4 py-2">
                        <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                    </a>
                    <a href="view_profile.php" class="btn btn-info px-4 py-2">
                        <i class="fas fa-eye me-2"></i>View Profile
                    </a>
                </div>
            </form>
        </div>

        <!-- Floating Action Button -->
        <div class="d-flex justify-content-end overflow-hidden p-0 pt-3">
            <a href="view_profile.php" class="floating-btn fw-bold text-white">
                <i class="fas fa-eye"></i>
                <span class="d-none d-md-inline">View Profile</span>
            </a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Handle file input change to show preview
            const fileInput = document.getElementById('profile_picture');
            const profileImg = document.querySelector('.profile-img-container img');

            fileInput.addEventListener('change', function (e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        profileImg.src = e.target.result;
                    }
                    reader.readAsDataURL(file);
                }
            });
        });
    </script>
</body>
<?php include('../../includes/semantics/footer.php'); ?>

</html>

<?php $conn->close(); ?>