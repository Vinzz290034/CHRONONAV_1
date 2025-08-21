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
/**
 * Fetches user profile data for the header dropdown.
 * Updates $_SESSION['user'] with the latest data from the database.
 *
 * @param mysqli $conn The database connection object.
 * @param int $userId The ID of the current user.
 * @return array An associative array containing 'display_username', 'display_user_role', and 'profile_img_src'.
 */
function getProfileDropdownData($conn, $userId) {
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
<?php require_once '../../templates/faculty/sidenav_faculty.php'; ?>


<link rel="stylesheet" href="../../assets/css/faculty_css/faculty_view_profile.css">

<div class="main-content-wrapper">
    <div class="main-dashboard-content">
        <div class="profile-container card">
            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show m-3" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <div class="profile-header text-center py-4">
                <img src="<?= $profile_img_src_page ?>" alt="<?= $display_name ?>'s Profile Picture" class="profile-avatar mb-3">
                <h4 class="mb-1"><?= $display_name ?></h4>
                <button class="btn btn-light-grey btn-sm px-4 mt-2" data-bs-toggle="modal" data-bs-target="#editProfileModal">Edit</button>
            </div>

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
                    <?php if (($user['role'] ?? '') === 'faculty'): ?>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <small class="text-muted">Faculty ID</small>
                                <p class="mb-0 profile-value"><?= $display_faculty_id ?></p>
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
                        Notifications
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="notificationsToggle" checked>
                        </div>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        Accessibility Mode
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="accessibilityToggle">
                        </div>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        Voice Navigation
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="voiceNavToggle">
                        </div>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        Font Size
                        <span>Medium</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        Theme
                        <span>Light</span>
                    </div>
                </div>
            </div>

            <div class="account-management-section p-4 border-top">
                <h5 class="section-title mb-3 text-center">Account Management</h5>
                <div class="list-group list-group-flush management-list-group">
                    <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                        Edit Account Info <i class="fas fa-arrow-right"></i>
                    </button>
                    <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-bs-toggle="modal" data-bs-target="#feedbackModal">
                        Feedback & Suggestion <i class="fas fa-arrow-right"></i>
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
            <form action="../../includes/faculty_edit_profile_handler.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="<?= $profile_img_src_page ?>" alt="Profile Picture" class="profile-avatar mb-2" id="profileImagePreview">
                        <label for="profile_img" class="form-label d-block text-primary">Change Profile Picture</label>
                        <input type="file" class="form-control" id="profile_img" name="profile_img" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" value="<?= $display_name ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="edit_email" name="email" value="<?= $display_email ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_department" class="form-label">Department</label>
                        <input type="text" class="form-control" id="edit_department" name="department" value="<?= $display_department ?>">
                    </div>
                    <div class="mb-3">
                        <label for="edit_faculty_id" class="form-label">Faculty ID</label>
                        <input type="text" class="form-control" id="edit_faculty_id" name="faculty_id" value="<?= $display_faculty_id ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../../includes/faculty_feedback_modal.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="feedbackModalLabel">Feedback & Suggestions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="feedback_type" class="form-label">Type of Feedback</label>
                        <select class="form-select" id="feedback_type" name="feedback_type" required>
                            <option value="">Select a type</option>
                            <option value="Suggestion">Suggestion</option>
                            <option value="Bug Report">Bug Report</option>
                            <option value="General Feedback">General Feedback</option>
                            <option value="Feature Request">Feature Request</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="feedback_subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="feedback_subject" name="feedback_subject" placeholder="e.g., UI Issue on Calendar Page" required>
                    </div>
                    <div class="mb-3">
                        <label for="feedback_message" class="form-label">Your Message</label>
                        <textarea class="form-control" id="feedback_message" name="feedback_message" rows="5" placeholder="Please be as detailed as possible." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rating</label>
                        <div class="d-flex align-items-center">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="feedback_rating" id="rating_1" value="1">
                                <label class="form-check-label" for="rating_1">1</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="feedback_rating" id="rating_2" value="2">
                                <label class="form-check-label" for="rating_2">2</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="feedback_rating" id="rating_3" value="3">
                                <label class="form-check-label" for="rating_3">3</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="feedback_rating" id="rating_4" value="4">
                                <label class="form-check-label" for="rating_4">4</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="feedback_rating" id="rating_5" value="5" checked>
                                <label class="form-check-label" for="rating_5">5</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Feedback</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Script to show a preview of the new profile image
    document.getElementById('profile_img').addEventListener('change', function(e) {
        const [file] = e.target.files;
        if (file) {
            document.getElementById('profileImagePreview').src = URL.createObjectURL(file);
        }
    });

    // Populate modal with current data when it's shown
    const editProfileModal = document.getElementById('editProfileModal');
    editProfileModal.addEventListener('show.bs.modal', function(event) {
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
</script>

<?php require_once '../../templates/footer.php'; ?>