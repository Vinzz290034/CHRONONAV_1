<?php
// CHRONONAV_WEB_DOSS/pages/admin/settings.php

// Start the session at the very beginning of the script
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Ensure user is logged in and has the 'admin' role
requireRole(['admin']);

$user = $_SESSION['user'];

// --- START: Variables for Header and Sidenav ---
// These variables MUST be defined before including header_admin.php
$page_title = "Settings";
$current_page = "settings"; // For active sidebar link

// Variables for the header template (display_username, display_user_role, profile_img_src)
$display_username = htmlspecialchars($user['name'] ?? 'Admin');
$display_user_role = htmlspecialchars($user['role'] ?? 'Admin');

// Attempt to get profile image path for the header
$profile_img_src = '../../uploads/profiles/default-avatar.png'; // Default fallback
if (!empty($user['profile_img']) && file_exists('../../' . $user['profile_img'])) {
    $profile_img_src = '../../' . $user['profile_img'];
}
// --- END: Variables for Header and Sidenav ---

$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>

<?php
// --- Include the Admin-specific Header ---
require_once '../../templates/admin/header_admin.php';
?>

<?php
// --- Include the Admin-specific Sidenav ---
require_once '../../templates/admin/sidenav_admin.php';
?>

<link rel="stylesheet" href="../../assets/css/admin_css/admin_setting.css">

<div class="main-content-wrapper">
    <div class="main-dashboard-content">
        <div class="dashboard-header">
            <h2><?= $page_title ?></h2>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show m-3" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="settings-container">
            <div class="settings-section card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Accessibility</h5>
                </div>
                <div class="card-body">
                    <div class="settings-item d-flex justify-content-between align-items-center mb-3">
                        <span>Voice Guidance</span>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="voiceGuidance">
                        </div>
                    </div>
                    <div class="settings-item d-flex justify-content-between align-items-center">
                        <span>High Contrast Mode</span>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="contrastMode">
                        </div>
                    </div>
                </div>
            </div>

            <div class="settings-section card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Display</h5>
                </div>
                <div class="card-body">
                    <div class="settings-item d-flex justify-content-between align-items-center">
                        <span>Font Size</span>
                        <span>Medium</span>
                    </div>
                </div>
            </div>

            <div class="settings-section card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Language</h5>
                </div>
                <div class="card-body">
                    <div class="settings-item d-flex justify-content-between align-items-center">
                        <span>Language</span>
                        <span>English</span>
                    </div>
                </div>
            </div>

            <div class="settings-section card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Account Management</h5>
                </div>
                <div class="card-body">
                    <div class="settings-item d-flex justify-content-between align-items-center mb-3">
                        <span>Change Password</span>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#changePasswordModal">Change Password</button>
                    </div>
                    <div class="settings-item d-flex justify-content-between align-items-center">
                        <span>Deactivate Account</span>
                        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deactivateAccountModal">Deactivate Account</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../../includes/admin_change_password_handler.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deactivateAccountModal" tabindex="-1" aria-labelledby="deactivateAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../../includes/admin_deactivate_handler.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="deactivateAccountModalLabel">Deactivate Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to deactivate your account? This action is permanent and cannot be undone.</p>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirm_deactivate" name="confirm_deactivate" required>
                        <label class="form-check-label" for="confirm_deactivate">I understand and want to proceed with deactivating my account.</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="deactivateSubmitButton" disabled>Deactivate Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>

<script>
    $('#voiceGuidance, #contrastMode').change(function () {
        let setting = $(this).attr('id');
        let value = $(this).is(':checked') ? 1 : 0;
        console.log(setting + ' changed to ' + value);
    });

    const confirmCheckbox = document.getElementById('confirm_deactivate');
    const deactivateButton = document.getElementById('deactivateSubmitButton');

    if (confirmCheckbox && deactivateButton) {
        confirmCheckbox.addEventListener('change', function() {
            deactivateButton.disabled = !this.checked;
        });
    }
</script>
