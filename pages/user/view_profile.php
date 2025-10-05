<?php
// CHRONONAV_WEB_DOSS/pages/user/view_profile.php

// Start the session at the very beginning of the script
session_start();

// Include necessary files
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Check if the user is logged in and has the correct role
requireRole(['user']);

$user_id = $_SESSION['user']['id'];

// Fetch the user's complete profile data from the database
$stmt = $conn->prepare("SELECT name, email, role, department, student_id, profile_img FROM users WHERE id = ?");
$user_data_full = [];

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user_data_full = $result->fetch_assoc();
        // Update session data with the fresh info from the database
        $_SESSION['user'] = array_merge($_SESSION['user'], $user_data_full);
    } else {
        // Handle case where user is not found in the database
        session_destroy();
        header('Location: ../../auth/login.php?error=user_not_found');
        exit();
    }
    $stmt->close();
} else {
    // Log a database connection error
    error_log("Database query preparation failed for view_profile (user): " . $conn->error);
}

// Get the user data from the session for display
$user = $_SESSION['user'];

// Prepare variables for display, ensuring they are safe for HTML output
$display_name = htmlspecialchars($user['name'] ?? 'N/A');
$display_email = htmlspecialchars($user['email'] ?? 'N/A');
$display_role = htmlspecialchars(ucfirst($user['role'] ?? 'N/A'));
$display_department = htmlspecialchars($user['department'] ?? 'N/A');
$display_student_id = htmlspecialchars($user['student_id'] ?? 'N/A');

// Determine the correct path for the profile image
$profile_img = $user['profile_img'] ?? 'uploads/profiles/default-avatar.png';
$profile_img_src = (strpos($profile_img, 'uploads/') === 0) ? '../../' . htmlspecialchars($profile_img) : htmlspecialchars($profile_img);

// Variables for page header and sidebar
$page_title = "My Profile";
$current_page = "profile";

// Get and clear any session messages
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message']);
unset($_SESSION['message_type']);

$header_path = '../../templates/user/header_user.php';
if (isset($user['role'])) {
    if ($user['role'] === 'admin') {
        $header_path = '../../templates/admin/header_admin.php';
    } elseif ($user['role'] === 'faculty') {
        $header_path = '../../templates/faculty/header_faculty.php';
    }
}
require_once $header_path;

?>



<link rel="stylesheet" href="../../assets/css/user_css/user_view_profiles.css">

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
<link rel="stylesheet" as="style" onload="this.rel='stylesheet'"
    href="https://fonts.googleapis.com/css2?display=swap&family=Noto+Sans:wght@400;500;700;900&family=Space+Grotesk:wght@400;500;700">

<!-- Favicon -->
<link rel="icon" type="image/x-icon"
    href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png">

<?php include('../../includes/semantics/head.php'); ?>

<?php
$sidenav_path = '../../templates/user/sidenav_user.php';
if (isset($user['role'])) {
    if ($user['role'] === 'admin') {
        $sidenav_path = '../../templates/admin/sidenav_admin.php';
    } elseif ($user['role'] === 'faculty') {
        $sidenav_path = '../../templates/faculty/sidenav_faculty.php';
    }
}
require_once $sidenav_path;
?>

<div class="layout-container">
    <style>
        body {
            font-family: 'Space Grotesk', 'Noto Sans', sans-serif;
        }
    </style>
    <div
        class="d-flex justify-content-end align-items-start flex-column flex-md-row gap-3 px-4 px-md-5 py-4 flex-grow-1">

        <!-- Main Content -->
        <div class="main-content d-flex flex-column">
            <!-- Header -->
            <div class="d-flex flex-wrap justify-content-between gap-3 p-3">
                <p class="text-dark fw-bold fs-3 mb-0" style="min-width: 288px;">Hello,
                    <?= $display_name ?>!
                </p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show m-3" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Profile Section -->
            <div class="p-3">
                <div class="d-flex flex-column gap-4 align-items-center w-100">
                    <div class="d-flex flex-column gap-4 align-items-center">
                        <div class="profile-image" style='background-image: url("<?= $profile_img_src ?>");'></div>
                        <p class="text-dark fw-bold fs-4 text-center">
                            <?= $display_name ?>
                        </p>
                    </div>
                    <button class="btn btn-custom-secondary px-4 py-2 w-100" style="max-width: 480px;"
                        data-bs-toggle="modal" data-bs-target="#editProfileModal">
                        <span>Edit</span>
                    </button>
                </div>
            </div>

            <!-- Profile Details -->
            <h2 class="text-dark fw-bold fs-4 px-3 pb-3 pt-4">Profile Details</h2>
            <div class="p-3">
                <div class="row profile-detail-row mx-0">
                    <div class="col-md-3">
                        <p class="text-muted small mb-0">Full Name</p>
                    </div>
                    <div class="col-md-9">
                        <p class="text-dark small mb-0">
                            <?= $display_name ?>
                        </p>
                    </div>
                </div>
                <div class="row profile-detail-row mx-0">
                    <div class="col-md-3">
                        <p class="text-muted small mb-0">Email</p>
                    </div>
                    <div class="col-md-9">
                        <p class="text-dark small mb-0">
                            <?= $display_email ?>
                        </p>
                    </div>
                </div>
                <?php if (($user['role'] ?? '') === 'user'): ?>
                    <div class="row profile-detail-row mx-0">
                        <div class="col-md-3">
                            <p class="text-muted small mb-0">Student ID</p>
                        </div>
                        <div class="col-md-9">
                            <p class="text-dark small mb-0">
                                <?= $display_student_id ?>
                            </p>
                        </div>
                    </div>
                    <div class="row profile-detail-row mx-0">
                        <div class="col-md-3">
                            <p class="text-muted small mb-0">Department</p>
                        </div>
                        <div class="col-md-9">
                            <p class="text-dark small mb-0">
                                <?= $display_department ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="row profile-detail-row mx-0">
                    <div class="col-md-3">
                        <p class="text-muted small mb-0">Role</p>
                    </div>
                    <div class="col-md-9">
                        <p class="text-dark small mb-0">
                            <?= $display_role ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- App Preferences -->
            <h2 class="text-dark fw-bold fs-4 px-3 pb-3 pt-4">App Preferences</h2>
            <div class="d-flex flex-column">
                <!-- Notifications -->
                <div class="preference-item d-flex align-items-center justify-content-between">
                    <p class="text-dark mb-0 flex-grow-1 text-truncate">Notifications</p>
                    <div class="flex-shrink-0">
                        <label class="toggle-switch checked">
                            <div class="toggle-knob"></div>
                            <input type="checkbox" class="toggle-input" checked>
                        </label>
                    </div>
                </div>

                <!-- Accessibility Mode -->
                <div class="preference-item d-flex align-items-center justify-content-between">
                    <p class="text-dark mb-0 flex-grow-1 text-truncate">Accessibility Mode</p>
                    <div class="flex-shrink-0">
                        <label class="toggle-switch">
                            <div class="toggle-knob"></div>
                            <input type="checkbox" class="toggle-input">
                        </label>
                    </div>
                </div>

                <!-- Voice Navigation -->
                <div class="preference-item d-flex align-items-center justify-content-between">
                    <p class="text-dark mb-0 flex-grow-1 text-truncate">Voice Navigation</p>
                    <div class="flex-shrink-0">
                        <label class="toggle-switch">
                            <div class="toggle-knob"></div>
                            <input type="checkbox" class="toggle-input">
                        </label>
                    </div>
                </div>

                <!-- Font Size -->
                <div class="preference-item d-flex align-items-center justify-content-between">
                    <p class="text-dark mb-0 flex-grow-1 text-truncate">Font Size</p>
                    <div class="flex-shrink-0">
                        <p class="text-dark mb-0">Medium</p>
                    </div>
                </div>

                <!-- Theme -->
                <div class="preference-item d-flex align-items-center justify-content-between">
                    <p class="text-dark mb-0 flex-grow-1 text-truncate">Theme</p>
                    <div class="flex-shrink-0">
                        <p class="text-dark mb-0">Light</p>
                    </div>
                </div>
            </div>

            <!-- Account Management -->
            <h2 class="text-dark fw-bold fs-4 px-3 pb-3 pt-4">Account Management</h2>
            <div class="d-flex flex-column">
                <!-- Account Setting -->
                <a href="settings.php" class="text-decoration-none">
                    <div class="account-item d-flex align-items-center justify-content-between">
                        <p class="text-dark mb-0 flex-grow-1 text-truncate">Account Setting</p>
                        <div class="flex-shrink-0">
                            <div class="text-dark d-flex align-items-center justify-content-center"
                                style="width: 28px; height: 28px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" fill="currentColor"
                                    viewBox="0 0 256 256">
                                    <path
                                        d="M221.66,133.66l-72,72a8,8,0,0,1-11.32-11.32L196.69,136H40a8,8,0,0,1,0-16H196.69L138.34,61.66a8,8,0,0,1,11.32-11.32l72,72A8,8,0,0,1,221.66,133.66Z">
                                    </path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Feedback & Suggestion -->
                <div class="account-item d-flex align-items-center justify-content-between" data-bs-toggle="modal"
                    data-bs-target="#feedbackModal">
                    <p class="text-dark mb-0 flex-grow-1 text-truncate">Feedback & Suggestion</p>
                    <div class="flex-shrink-0">
                        <div class="text-dark d-flex align-items-center justify-content-center"
                            style="width: 28px; height: 28px;">
                            <i class="fas fa-message"></i>
                        </div>
                    </div>
                </div>

                <!-- Announcement -->
                <a href="announcements.php" class="text-decoration-none">
                    <div class="account-item d-flex align-items-center justify-content-between">
                        <p class="text-dark mb-0 flex-grow-1 text-truncate">Announcement</p>
                        <div class="flex-shrink-0">
                            <div class="text-dark d-flex align-items-center justify-content-center"
                                style="width: 28px; height: 28px;">
                                <i class="fas fa-bullhorn"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Logout Button -->
            <div class="d-flex px-3 py-3 justify-content-start">
                <a href="../../auth/logout.php" class="btn btn-custom-secondary px-4 py-2">
                    <span>Logout</span>
                </a>
            </div>


        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade overflow-hidden" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../../includes/user_edit_profile_handler.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold fs-4" id="editProfileModalLabel">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Profile Image Upload -->
                    <div class="text-center mb-4">
                        <div class="profile-image mx-auto mb-3" id="profileImagePreview"
                            style='background-image: url("<?= $profile_img_src ?>"); width: 100px; height: 100px;'>
                        </div>
                        <label for="profile_img" class="form-label d-block text-primary fw-medium cursor-pointer">
                            Change Profile Picture
                        </label>
                        <input type="file" class="form-control d-none" id="profile_img" name="profile_img"
                            accept="image/*">
                        <button type="button" class="btn btn-custom-secondary btn-sm mt-2"
                            onclick="document.getElementById('profile_img').click()">
                            Choose File
                        </button>
                    </div>

                    <!-- Form Fields -->
                    <div class="d-flex flex-column gap-3">
                        <!-- Full Name -->
                        <div class="form-group">
                            <label for="edit_name" class="form-label text-muted small mb-2">Full Name</label>
                            <input type="text" class="form-control rounded-3 border-0 bg-light py-3" id="edit_name"
                                name="name" value="<?= $display_name ?>" required>
                        </div>

                        <!-- Email Address -->
                        <div class="form-group">
                            <label for="edit_email" class="form-label text-muted small mb-2">Email address</label>
                            <input type="email" class="form-control rounded-3 border-0 bg-light py-3" id="edit_email"
                                name="email" value="<?= $display_email ?>" required>
                        </div>

                        <!-- Department -->
                        <div class="form-group">
                            <label for="edit_department" class="form-label text-muted small mb-2">Department</label>
                            <input type="text" class="form-control rounded-3 border-0 bg-light py-3"
                                id="edit_department" name="department" value="<?= $display_department ?>">
                        </div>

                        <!-- Student ID -->
                        <div class="form-group">
                            <label for="edit_student_id" class="form-label text-muted small mb-2">Student ID</label>
                            <input type="text" class="form-control rounded-3 border-0 bg-light py-3"
                                id="edit_student_id" name="student_id" value="<?= $display_student_id ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-custom-secondary px-4 py-2"
                        data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 border-0 fw-medium"
                        style="background-color: #1776f1;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Feedback Modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../../includes/user_feedback_handler.php" method="POST">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold fs-4" id="feedbackModalLabel">Feedback & Suggestion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex flex-column gap-3">
                        <!-- Feedback Type -->
                        <div class="form-group">
                            <label for="feedback_type" class="form-label text-muted small mb-2">Type</label>
                            <select class="form-select rounded-3 border-0 bg-light py-3" id="feedback_type"
                                name="feedback_type" required>
                                <option value="">Select type</option>
                                <option value="suggestion">Suggestion</option>
                                <option value="bug_report">Bug Report</option>
                                <option value="feature_request">Feature Request</option>
                                <option value="general_feedback">General Feedback</option>
                            </select>
                        </div>

                        <!-- Subject -->
                        <div class="form-group">
                            <label for="feedback_subject" class="form-label text-muted small mb-2">Subject</label>
                            <input type="text" class="form-control rounded-3 border-0 bg-light py-3"
                                id="feedback_subject" name="subject" required>
                        </div>

                        <!-- Message -->
                        <div class="form-group">
                            <label for="feedback_message" class="form-label text-muted small mb-2">Message</label>
                            <textarea class="form-control rounded-3 border-0 bg-light py-3" id="feedback_message"
                                name="message" rows="5" required></textarea>
                        </div>

                        <!-- Rating -->
                        <div class="form-group">
                            <label for="feedback_rating" class="form-label text-muted small mb-2">
                                Rating (1-5) <span class="text-muted">(Optional)</span>
                            </label>
                            <select class="form-select rounded-3 border-0 bg-light py-3" id="feedback_rating"
                                name="rating">
                                <option value="">Optional</option>
                                <option value="1">1 - Poor</option>
                                <option value="2">2 - Fair</option>
                                <option value="3">3 - Average</option>
                                <option value="4">4 - Good</option>
                                <option value="5">5 - Excellent</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-custom-secondary px-4 py-2"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 border-0 fw-medium"
                        style="background-color: #1776f1;">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Toggle switch functionality
    document.querySelectorAll('.toggle-input').forEach(input => {
        // Set initial state based on checked attribute
        if (input.checked) {
            input.parentElement.classList.add('checked');
        }

        input.addEventListener('change', function () {
            if (this.checked) {
                this.parentElement.classList.add('checked');
            } else {
                this.parentElement.classList.remove('checked');
            }
        });
    });

    // Script to show a preview of the new profile image
    document.getElementById('profile_img').addEventListener('change', function (e) {
        const [file] = e.target.files;
        if (file) {
            const preview = document.getElementById('profileImagePreview');
            preview.style.backgroundImage = `url(${URL.createObjectURL(file)})`;
        }
    });

    // Populate modal with current data when it's shown
    const editProfileModal = document.getElementById('editProfileModal');
    editProfileModal.addEventListener('show.bs.modal', function (event) {
        const nameInput = document.getElementById('edit_name');
        const emailInput = document.getElementById('edit_email');
        const departmentInput = document.getElementById('edit_department');
        const studentIdInput = document.getElementById('edit_student_id');
        const profileImagePreview = document.getElementById('profileImagePreview');

        nameInput.value = "<?= $display_name ?>";
        emailInput.value = "<?= $display_email ?>";
        departmentInput.value = "<?= $display_department ?>";
        studentIdInput.value = "<?= $display_student_id ?>";
        profileImagePreview.src = "<?= $profile_img_src ?>";
    });

    // Enhanced file input functionality
    document.getElementById('profile_img').addEventListener('change', function (e) {
        const [file] = e.target.files;
        if (file) {
            const preview = document.getElementById('profileImagePreview');
            preview.style.backgroundImage = `url(${URL.createObjectURL(file)})`;

            // Show file name or feedback
            const fileName = file.name;
            // You could add a small indicator showing the file was selected
        }
    });
</script>

<?php require_once '../../templates/footer.php'; ?>

<?php include('../../includes/semantics/footer.php'); ?>