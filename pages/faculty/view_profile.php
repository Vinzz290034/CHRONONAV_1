<?php
// CHRONONAV_WEB_DOSS/pages/faculty/view_profile.php

// Start the session at the very beginning of the script
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Ensure user is logged in and has the 'faculty' role
requireRole(['faculty']);

// Get basic user data from session (auth_check.php should have populated this)
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

$user = $_SESSION['user'];

// --- START: Function to prepare data for the Profile Dropdown in Header ---
function getProfileDropdownData($conn, $userId)
{
    $data = [
        'display_username' => 'Unknown User',
        'display_user_role' => 'Role Not Set',
        'profile_img_src' => '../../uploads/profiles/default-avatar.png'
    ];

    $stmt = $conn->prepare("SELECT name, email, profile_img, role FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user_from_db = $result->fetch_assoc();
            $_SESSION['user'] = array_merge($_SESSION['user'], $user_from_db);

            $data['display_username'] = htmlspecialchars($user_from_db['name']);
            $data['display_user_role'] = htmlspecialchars(ucfirst($user_from_db['role']));

            if (!empty($user_from_db['profile_img'])) {
                $display_profile_img = htmlspecialchars($user_from_db['profile_img']);
                $data['profile_img_src'] = (strpos($display_profile_img, 'uploads/') === 0) ? '../../' . $display_profile_img : $display_profile_img;
            }
        } else {
            error_log("Security Alert: User ID {$userId} in session not found in database for view_profile (faculty).");
            session_destroy();
            header('Location: ../../auth/login.php?error=user_data_missing');
            exit();
        }
        $stmt->close();
    } else {
        error_log("Database query preparation failed in getProfileDropdownData (faculty/view_profile): " . $conn->error);
    }
    return $data;
}

extract(getProfileDropdownData($conn, $user['id']));

// --- END: Function for Profile Dropdown Data ---

// Now, fetch the complete user data for the main profile display on this page.
$user_id = $_SESSION['user']['id'];
$stmt = $conn->prepare("SELECT name, email, role, department, faculty_id, profile_img FROM users WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user_data_full = $result->fetch_assoc();
        $user = array_merge($user, $user_data_full);
    } else {
        error_log("User ID {$user_id} not found in database for detailed profile (faculty).");
        session_destroy();
        header('Location: ../../auth/login.php?error=user_not_found_details');
        exit();
    }
    $stmt->close();
} else {
    error_log("Database query preparation failed for full user data in view_profile (faculty): " . $conn->error);
}

// Prepare variables for display in the profile page content, ensuring they are safe for HTML
$display_name = htmlspecialchars($user['name'] ?? 'N/A');
$display_email = htmlspecialchars($user['email'] ?? 'N/A');
$display_role = htmlspecialchars(ucfirst($user['role'] ?? 'N/A'));
$display_department = htmlspecialchars($user['department'] ?? 'N/A');
$display_faculty_id = htmlspecialchars($user['faculty_id'] ?? 'N/A');

$profile_img_src_page = (strpos($user['profile_img'] ?? 'uploads/profiles/default-avatar.png', 'uploads/') === 0) ? '../../' . htmlspecialchars($user['profile_img'] ?? 'uploads/profiles/default-avatar.png') : htmlspecialchars($user['profile_img'] ?? 'uploads/profiles/default-avatar.png');

$page_title = "My Profile";
$current_page = "profile";

$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

?>

<?php require_once '../../templates/faculty/header_faculty.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'ChronoNav - Faculty Profile' ?></title>

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
        /* Custom styles to match the original design */
        .layout-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-content {
            max-width: 80%;
            flex: 1;
            margin-left: 20%;
        }

        .nav-item {
            cursor: pointer;
        }

        .nav-item.active {
            background-color: #f0f2f5;
        }

        .nav-item:hover {
            background-color: #f8f9fa;
        }

        .profile-image {
            width: 128px;
            height: 128px;
            border-radius: 50%;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .btn.btn-custom-secondary {
            background-color: #f0f2f5;
            color: #111418;
            font-weight: bold;
            border: none;
            border-radius: 0.75rem;
        }

        .profile-detail-row {
            border-top: 1px solid #dbe0e6;
            padding: 1.25rem 0;
        }

        .preference-item {
            min-height: 56px;
            padding: 0 1rem;
        }

        .toggle-switch {
            position: relative;
            display: inline-flex;
            height: 31px;
            width: 51px;
            cursor: pointer;
            align-items: center;
            border-radius: 9999px;
            background-color: #f0f2f5;
            padding: 2px;
        }

        .toggle-switch.checked {
            background-color: #1776f1;
            justify-content: flex-end;
        }

        .toggle-knob {
            height: 100%;
            width: 27px;
            border-radius: 9999px;
            background-color: white;
            box-shadow: rgba(0, 0, 0, 0.15) 0px 3px 8px, rgba(0, 0, 0, 0.06) 0px 3px 1px;
        }

        .toggle-input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .account-item {
            min-height: 56px;
            padding: 0 1rem;
            cursor: pointer;
        }

        .account-item:hover {
            background-color: #f8f9fa;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-content {
                width: 100%;
                max-width: 100%;
                margin-left: 0;
            }
        }

        /* Alert styles */
        .alert {
            border-radius: 0.75rem;
            border: none;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        /* Edit Profile Modal */
        .form-control {
            font-family: "Space Grotesk", "Noto Sans", sans-serif;
            font-weight: 500;
        }

        .form-control:focus {
            box-shadow: 0 0 0 2px rgba(23, 118, 241, 0.2);
            border-color: #1776f1;
        }

        .bg-light {
            background-color: #f8f9fa !important;
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .rounded-3 {
            border-radius: 0.75rem !important;
        }

        /* Feedback Modal */
        .form-select,
        .form-control,
        textarea.form-control {
            font-family: "Space Grotesk", "Noto Sans", sans-serif;
            font-weight: 500;
        }

        .form-select:focus,
        .form-control:focus,
        textarea.form-control:focus {
            box-shadow: 0 0 0 2px rgba(23, 118, 241, 0.2);
            border-color: #1776f1;
            background-color: #fff;
        }

        .bg-light {
            background-color: #f8f9fa !important;
        }

        .rounded-3 {
            border-radius: 0.75rem !important;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
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
    <?php require_once '../../templates/faculty/sidenav_faculty.php'; ?>

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
                            <div class="profile-image" style='background-image: url("<?= $profile_img_src_page ?>");'>
                            </div>
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
                    <?php if (($user['role'] ?? '') === 'faculty'): ?>
                        <div class="row profile-detail-row mx-0">
                            <div class="col-md-3">
                                <p class="text-muted small mb-0">Faculty ID</p>
                            </div>
                            <div class="col-md-9">
                                <p class="text-dark small mb-0">
                                    <?= $display_faculty_id ?>
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
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px"
                                        fill="currentColor" viewBox="0 0 256 256">
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
                <form action="../../includes/faculty_edit_profile_handler.php" method="POST"
                    enctype="multipart/form-data">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold fs-4" id="editProfileModalLabel">Edit Profile</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <!-- Profile Image Upload -->
                        <div class="text-center mb-4">
                            <div class="profile-image mx-auto mb-3" id="profileImagePreview"
                                style='background-image: url("<?= $profile_img_src_page ?>"); width: 100px; height: 100px;'>
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
                                <input type="email" class="form-control rounded-3 border-0 bg-light py-3"
                                    id="edit_email" name="email" value="<?= $display_email ?>" required>
                            </div>

                            <!-- Department -->
                            <div class="form-group">
                                <label for="edit_department" class="form-label text-muted small mb-2">Department</label>
                                <input type="text" class="form-control rounded-3 border-0 bg-light py-3"
                                    id="edit_department" name="department" value="<?= $display_department ?>">
                            </div>

                            <!-- Faculty ID -->
                            <div class="form-group">
                                <label for="edit_faculty_id" class="form-label text-muted small mb-2">Faculty ID</label>
                                <input type="text" class="form-control rounded-3 border-0 bg-light py-3"
                                    id="edit_faculty_id" name="faculty_id" value="<?= $display_faculty_id ?>">
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
                <form action="../../includes/faculty_feedback_handler.php" method="POST">
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
            const facultyIdInput = document.getElementById('edit_faculty_id');
            const profileImagePreview = document.getElementById('profileImagePreview');

            nameInput.value = "<?= $display_name ?>";
            emailInput.value = "<?= $display_email ?>";
            departmentInput.value = "<?= $display_department ?>";
            facultyIdInput.value = "<?= $display_faculty_id ?>";
            profileImagePreview.src = "<?= $profile_img_src_page ?>";
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
</body>

</html>

<?php include('../../includes/semantics/footer.php'); ?>