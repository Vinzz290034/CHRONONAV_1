<?php
// CHRONONAV_WEB_DOSS/pages/admin/view_profile.php

require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

requireRole(['admin']);

$user = $_SESSION['user'];

$user_id = $user['id'];
$stmt = $conn->prepare("SELECT name, email, role, department, admin_id, profile_img FROM users WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user_from_db = $result->fetch_assoc();
        $_SESSION['user'] = array_merge($_SESSION['user'], $user_from_db);
        $user = $_SESSION['user'];
    } else {
        session_destroy();
        header('Location: ../../auth/login.php?error=user_not_found');
        exit();
    }
    $stmt->close();
} else {
    error_log("Database query preparation failed for view_profile (user): " . $conn->error);
}

$display_name = htmlspecialchars($user['name'] ?? 'N/A');
$display_email = htmlspecialchars($user['email'] ?? 'N/A');
$display_role = htmlspecialchars(ucfirst($user['role'] ?? 'N/A'));
$display_department = htmlspecialchars($user['department'] ?? 'N/A');
$display_admin_id = htmlspecialchars($user['admin_id'] ?? 'N/A');

$display_profile_img = htmlspecialchars($user['profile_img'] ?? 'uploads/profiles/default-avatar.png');
$profile_img_src = (strpos($display_profile_img, 'uploads/') === 0) ? '../../' . $display_profile_img : $display_profile_img;

$page_title = "My Profile";
$current_page = "profile";

// Handle messages from the session
$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

?>
<?php require_once '../../templates/admin/header_admin.php'; ?>
<?php require_once '../../templates/admin/sidenav_admin.php'; ?>

<link rel="stylesheet" href="../../assets/css/admin_css/view_profile.css">

<div class="main-content-wrapper">
    <div class="main-dashboard-content">
        <div class="profile-container card">
            <div class="profile-header text-center py-4">
                <img src="<?= $profile_img_src ?>" alt="<?= $display_name ?>'s Profile Picture" class="profile-avatar mb-3">
                <h4 class="mb-1"><?= $display_name ?></h4>
                <button class="btn btn-light-grey btn-sm px-4 mt-2" data-bs-toggle="modal" data-bs-target="#editProfileModal">Edit</button>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show m-4" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="profile-details-section p-4">
                <h5 class="section-title mb-3 text-center">Profile Details</h5>
                <div class="row g-3 profile-details-grid">
                    <div class="col-md-6">
                        <div class="detail-item">
                            <small class="text-muted">Full Name</small>
                            <p class="mb-0 profile-value"><?= $display_name ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-item">
                            <small class="text-muted">Email</small>
                            <p class="mb-0 profile-value"><?= $display_email ?></p>
                        </div>
                    </div>
                    <?php if (($user['role'] ?? '') === 'admin'): ?>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <small class="text-muted">Admin ID</small>
                                <p class="mb-0 profile-value"><?= $display_admin_id ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <small class="text-muted">Department</small>
                                <p class="mb-0 profile-value"><?= $display_department ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="col-md-6">
                        <div class="detail-item">
                            <small class="text-muted">Role</small>
                            <p class="mb-0 profile-value"><?= $display_role ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-preferences-section p-4 border-top">
                <h5 class="section-title mb-3 text-center">App Preferences</h5>
                <div class="list-group list-group-flush preferences-list-group">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Notifications</span>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="notificationsToggle" checked>
                        </div>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Accessibility Mode</span>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="accessibilityToggle">
                        </div>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Voice Navigation</span>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="voiceNavToggle">
                        </div>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Font Size</span>
                        <span>Medium</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Theme</span>
                        <span>Light</span>
                    </div>
                </div>
            </div>

            <div class="account-management-section p-4 border-top">
                <h5 class="section-title mb-3 text-center">Account Management</h5>
                <div class="list-group list-group-flush management-list-group">
                    <a href="settings.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        Account Setting <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="support_center.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        Support and Ask question <i class="fas fa-arrow-right"></i>
                    </a>
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-bs-toggle="modal" data-bs-target="#feedbackModal">
                        Feedback & Suggestion<i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="app-version text-center text-muted mt-4">
            App Version 1.0.0 · © 2025 ChronoNav
        </div>
    </div>
</div>

<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editProfileForm" action="../../includes/edit_profile_handler.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">
                    <div class="mb-3 text-center">
                        <img id="profileImagePreview" src="<?= $profile_img_src ?>" alt="Profile Picture" class="profile-avatar mb-2">
                        <label for="profileImage" class="form-label d-block btn btn-outline-secondary btn-sm">Change Picture</label>
                        <input type="file" class="form-control d-none" id="profileImage" name="profile_img" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= $display_name ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= $display_email ?>" required>
                    </div>
                    <?php if (($user['role'] ?? '') === 'admin'): ?>
                        <div class="mb-3">
                            <label for="adminId" class="form-label">Admin ID</label>
                            <input type="text" class="form-control" id="adminId" name="admin_id" value="<?= $display_admin_id ?>">
                        </div>
                        <div class="mb-3">
                            <label for="department" class="form-label">Department</label>
                            <input type="text" class="form-control" id="department" name="department" value="<?= $display_department ?>">
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" form="editProfileForm" class="btn btn-primary">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="feedbackModalLabel">Feedback & Suggestion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="feedbackForm" action="../../includes/feedback_handler.php" method="POST">
                    <div class="mb-3">
                        <label for="feedbackType" class="form-label">Type</label>
                        <select class="form-select" id="feedbackType" name="type" required>
                            <option value="" selected disabled>Select type</option>
                            <option value="Bug Report">Bug Report</option>
                            <option value="Feature Request">Feature Request</option>
                            <option value="General Feedback">General Feedback</option>
                            <option value="Suggestion">Suggestion</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="feedbackSubject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="feedbackSubject" name="subject" required>
                    </div>
                    <div class="mb-3">
                        <label for="feedbackMessage" class="form-label">Message</label>
                        <textarea class="form-control" id="feedbackMessage" name="message" rows="5" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="feedbackRating" class="form-label">Rating (1-5)</label>
                        <select class="form-select" id="feedbackRating" name="rating">
                            <option value="" selected disabled>Optional</option>
                            <option value="1">1 - Terrible</option>
                            <option value="2">2 - Bad</option>
                            <option value="3">3 - Okay</option>
                            <option value="4">4 - Good</option>
                            <option value="5">5 - Excellent</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="feedbackForm" class="btn btn-primary">Submit</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('profileImage').addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profileImagePreview').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
</script>

<?php require_once '../../templates/footer.php'; ?>