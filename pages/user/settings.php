<?php
// CHRONONAV_WEB_DOSS/pages/user/settings.php

// Start the session at the very beginning of the script
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Ensure user is logged in and has the 'user' role
requireRole(['user']);

$user = $_SESSION['user'];

// --- START: Variables for Header and Sidenav ---
$page_title = "Settings";
$current_page = "settings";

$display_username = htmlspecialchars($user['name'] ?? 'User');
$display_user_role = htmlspecialchars($user['role'] ?? 'User');

$profile_img_src = '../../uploads/profiles/default-avatar.png';
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
// --- Include the User-specific Header ---
require_once '../../templates/user/header_user.php';
?>



<style>
    /* Consistent design from previous pages */
    body {
        font-family: "Space Grotesk", "Noto Sans", sans-serif;
        background-color: #fff;
    }

    .main-content-wrapper {
        margin-left: 20%;
        transition: margin-left 0.3s ease;
        background-color: #fff;
    }

    .main-dashboard-content {
        font-family: "Space Grotesk", "Noto Sans", sans-serif;
        max-width: 100%;
        padding: 0 1rem;
    }

    .settings-container {
        padding: 1rem 0;
    }

    .settings-section {
        border: none;
        border-radius: 0.75rem;
        margin-bottom: 1.5rem;
    }

    .settings-section .card-header {
        background-color: white;
        border-bottom: 1px solid #eaedf1;
        padding: 1.25rem 1.5rem;
    }

    .settings-section .card-header h5 {
        color: #101518;
        font-weight: bold;
        font-size: 1.125rem;
        margin: 0;
    }

    .settings-section .card-body {
        padding: 1.5rem;
    }

    .settings-item {
        padding: 1rem 0;
        border-bottom: 1px solid #f0f2f5;
    }

    .settings-item:last-child {
        border-bottom: none;
    }

    .settings-item span:first-child {
        color: #101518;
        font-weight: 500;
        font-size: 0.875rem;
    }

    .settings-item span:last-child {
        color: #5c748a;
        font-size: 0.875rem;
    }

    .btn-custom-outline {
        background-color: #eaedf1;
        color: #101518;
        border: none;
        border-radius: 9999px;
        font-weight: 500;
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
        min-width: 84px;
    }

    .btn-custom-blue {
        background-color: #0b80ee;
        color: white;
        border: none;
        border-radius: 9999px;
        font-weight: bold;
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
    }

    .btn-custom-danger {
        background-color: #dc3545;
        color: white;
        border: none;
        border-radius: 9999px;
        font-weight: 500;
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
        min-width: 84px;
    }

    /* Toggle Switch Styling */
    .form-check-input:checked {
        background-color: #0b80ee;
        border-color: #0b80ee;
    }

    .form-check-input:focus {
        border-color: #0b80ee;
        box-shadow: 0 0 0 0.2rem rgba(11, 128, 238, 0.25);
    }

    .form-switch .form-check-input {
        width: 3rem;
        height: 1.5rem;
    }

    /* Modal Styling */
    .modal-header {
        border-bottom: 1px solid #eaedf1;
        padding: 1.25rem 1.5rem;
    }

    .modal-header .modal-title {
        color: #101518;
        font-weight: bold;
        font-size: 1.25rem;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .modal-footer {
        border-top: 1px solid #eaedf1;
        padding: 1rem 1.5rem;
    }

    .form-label {
        color: #101518;
        font-weight: 500;
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
    }

    .form-control {
        border: 1px solid #eaedf1;
        border-radius: 0.5rem;
        padding: 0.75rem;
        font-size: 0.875rem;
    }

    .form-control:focus {
        border-color: #0b80ee;
        box-shadow: 0 0 0 0.2rem rgba(11, 128, 238, 0.25);
    }

    .alert {
        border: none;
        border-radius: 0.75rem;
        padding: 1rem 1.25rem;
        margin: 1rem 0;
    }

    .alert-success {
        background-color: #d1e7dd;
        color: #0f5132;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
    }

    .alert-warning {
        background-color: #fff3cd;
        color: #664d03;
    }

    .alert-info {
        background-color: #cff4fc;
        color: #055160;
    }

    /* Scrollbar Styling */
    ::-webkit-scrollbar {
        width: 12px;
        height: 12px;
    }

    ::-webkit-scrollbar-track {
        background: #ffffff;
        /* white track */
    }

    ::-webkit-scrollbar-thumb {
        background-color: #737373;
        /* gray thumb */
        border-radius: 6px;
        border: 3px solid #ffffff;
        /* padding effect with white border */
    }

    ::-webkit-scrollbar-thumb:hover {
        background-color: #2e78c6;
        /* blue on hover */
    }




    /* Add these media queries at the end of your existing CSS */

    /* Mobile: 767px and below */
    @media (max-width: 767px) {
        .main-content-wrapper {
            margin-left: 0 !important;
            width: 100% !important;
            overflow-y: hidden;
        }

        .main-dashboard-content {
            padding: 0 0.5rem !important;
        }

        .settings-container {
            padding: 0.5rem 0 !important;
        }

        .settings-section .card-header {
            padding: 1rem !important;
        }

        .settings-section .card-body {
            padding: 1rem !important;
        }

        .settings-item {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 0.5rem !important;
            padding: 0.75rem 0 !important;
        }

        .settings-item span:first-child {
            font-size: 0.875rem !important;
        }

        .settings-item span:last-child {
            font-size: 0.8rem !important;
        }

        .btn-custom-outline,
        .btn-custom-danger {
            width: 100% !important;
            min-width: auto !important;
            margin-top: 0.25rem !important;
        }

        .modal-dialog {
            margin: 0.5rem !important;
            max-width: calc(100% - 1rem) !important;
        }

        .modal-content {
            border-radius: 0.5rem !important;
        }

        .d-flex.flex-wrap.justify-content-between.gap-3.p-3 {
            padding: 1rem 0.5rem !important;
        }

        .text-dark.fw-bold.fs-3.mb-0 {
            font-size: 1.5rem !important;
            width: 100%;
        }
    }

    /* Tablet: 768px to 1023px */
    @media (min-width: 768px) and (max-width: 1023px) {
        .main-content-wrapper {
            margin-left: 15% !important;
            width: 85% !important;
        }

        .main-dashboard-content {
            padding: 0 1rem !important;
            max-width: 100% !important;
        }

        .settings-container {
            padding: 1rem 0 !important;
        }

        .settings-section .card-header {
            padding: 1.125rem 1.25rem !important;
        }

        .settings-section .card-body {
            padding: 1.25rem !important;
        }

        .settings-item {
            padding: 0.875rem 0 !important;
        }

        .modal-dialog {
            max-width: 500px !important;
            margin: 1.75rem auto !important;
        }

        .btn-custom-outline,
        .btn-custom-danger {
            min-width: 120px !important;
        }
    }

    /* Desktop: 1024px and above */
    @media (min-width: 1024px) {
        .main-content-wrapper {
            margin-left: 20% !important;
            width: 80% !important;
        }

        .main-dashboard-content {
            padding: 0 1rem !important;
            max-width: 100% !important;
        }

        .settings-container {
            padding: 1rem 0 !important;
        }

        .settings-section .card-header {
            padding: 1.25rem 1.5rem !important;
        }

        .settings-section .card-body {
            padding: 1.5rem !important;
        }

        .settings-item {
            padding: 1rem 0 !important;
        }

        .modal-dialog {
            max-width: 500px !important;
            margin: 1.75rem auto !important;
        }

        .btn-custom-outline,
        .btn-custom-danger {
            min-width: 140px !important;
        }
    }
</style>

<?php
// --- Include the User-specific Sidenav ---
require_once '../../templates/user/sidenav_user.php';
?>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
<link rel="stylesheet" as="style" onload="this.rel='stylesheet'"
    href="https://fonts.googleapis.com/css2?display=swap&family=Noto+Sans:wght@400;500;700;900&family=Space+Grotesk:wght@400;500;700">

<div class="main-content-wrapper" style="margin-left: 20%;">
    <div class="main-dashboard-content">
        <!-- Header Section - Consistent with previous pages -->
        <div class="d-flex flex-wrap justify-content-between gap-3 p-3">
            <h2 class="text-dark fw-bold fs-3 mb-0" style="min-width: 288px;"><?= $page_title ?></h2>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show m-3" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="settings-container">
            <!-- Accessibility Section -->
            <div class="settings-section card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Accessibility</h5>
                </div>
                <div class="card-body">
                    <div class="settings-item d-flex justify-content-between align-items-center">
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
                    <div class="settings-item d-flex justify-content-between align-items-center">
                        <span>Dark Mode</span>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="darkModeSwitch">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Display Section -->
            <div class="settings-section card shadow-sm">
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

            <!-- Language Section -->
            <div class="settings-section card shadow-sm">
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

            <!-- Account Management Section -->
            <div class="settings-section card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Account Management</h5>
                </div>
                <div class="card-body">
                    <div class="settings-item d-flex justify-content-between align-items-center">
                        <span>Change Password</span>
                        <button type="button" class="btn btn-custom-outline btn-sm" data-bs-toggle="modal"
                            data-bs-target="#changePasswordModal">Change Password</button>
                    </div>
                    <div class="settings-item d-flex justify-content-between align-items-center">
                        <span>Deactivate Account</span>
                        <button type="button" class="btn btn-custom-danger btn-sm" data-bs-toggle="modal"
                            data-bs-target="#deactivateAccountModal">Deactivate Account</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../../includes/user_change_password_handler.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="changePasswordModalLabel">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password"
                            required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                            required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-custom-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-custom-blue">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Deactivate Account Modal -->
<div class="modal fade" id="deactivateAccountModal" tabindex="-1" aria-labelledby="deactivateAccountModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../../includes/user_deactivate_handler.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="deactivateAccountModalLabel">Deactivate Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Are you sure you want to deactivate your account? This action is
                        permanent and cannot be undone.</p>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="confirm_deactivate"
                            name="confirm_deactivate" required>
                        <label class="form-check-label" for="confirm_deactivate">I understand and want to proceed with
                            deactivating my account.</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-custom-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-custom-danger" id="deactivateSubmitButton" disabled>Deactivate
                        Account</button>
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
        confirmCheckbox.addEventListener('change', function () {
            deactivateButton.disabled = !this.checked;
        });
    }




    // Add this JavaScript for responsive behavior
    document.addEventListener('DOMContentLoaded', function () {
        // Create sidebar toggle button
        const sidebarToggle = document.createElement('button');
        sidebarToggle.className = 'sidebar-toggle d-none';
        sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
        sidebarToggle.setAttribute('aria-label', 'Toggle sidebar');
        document.body.appendChild(sidebarToggle);

        // Function to handle responsive layout
        function handleResponsiveLayout() {
            const width = window.innerWidth;
            const mainContentWrapper = document.querySelector('.main-content-wrapper');
            const sidebarToggle = document.querySelector('.sidebar-toggle');

            if (width <= 1023) {
                // Mobile and Tablet
                sidebarToggle.classList.remove('d-none');

                if (width <= 767) {
                    // Mobile specific adjustments
                    mainContentWrapper.style.marginLeft = '0';
                    mainContentWrapper.style.width = '100%';
                } else {
                    // Tablet specific adjustments
                    mainContentWrapper.style.marginLeft = '15%';
                    mainContentWrapper.style.width = '85%';
                }
            } else {
                // Desktop
                sidebarToggle.classList.add('d-none');
                mainContentWrapper.style.marginLeft = '20%';
                mainContentWrapper.style.width = '80%';
            }
        }

        // Toggle sidebar function
        function toggleSidebar() {
            const mainContentWrapper = document.querySelector('.main-content-wrapper');
            const currentMargin = mainContentWrapper.style.marginLeft;

            if (currentMargin === '0px' || !currentMargin) {
                if (window.innerWidth <= 767) {
                    mainContentWrapper.style.marginLeft = '0';
                } else {
                    mainContentWrapper.style.marginLeft = '15%';
                }
            } else {
                mainContentWrapper.style.marginLeft = '0';
            }
        }

        // Event listeners
        window.addEventListener('resize', handleResponsiveLayout);
        document.querySelector('.sidebar-toggle').addEventListener('click', toggleSidebar);

        // Initialize
        handleResponsiveLayout();

        // Enhanced modal handling for mobile
        function enhanceModals() {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.addEventListener('show.bs.modal', function () {
                    if (window.innerWidth <= 767) {
                        document.body.style.overflow = 'hidden';
                        // Add backdrop for better mobile experience
                        const backdrop = document.createElement('div');
                        backdrop.className = 'modal-backdrop-mobile';
                        backdrop.style.cssText = `
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0,0,0,0.5);
                        z-index: 1040;
                    `;
                        document.body.appendChild(backdrop);
                    }
                });

                modal.addEventListener('hidden.bs.modal', function () {
                    document.body.style.overflow = '';
                    const backdrop = document.querySelector('.modal-backdrop-mobile');
                    if (backdrop) {
                        backdrop.remove();
                    }
                });
            });
        }

        enhanceModals();

        // Improve touch interactions for mobile settings items
        if ('ontouchstart' in window) {
            document.querySelectorAll('.settings-item').forEach(item => {
                item.style.cursor = 'pointer';
                item.addEventListener('touchstart', function () {
                    this.style.backgroundColor = '#f8f9fa';
                });
                item.addEventListener('touchend', function () {
                    this.style.backgroundColor = '';
                });
            });
        }

        // Handle orientation changes
        window.addEventListener('orientationchange', function () {
            setTimeout(handleResponsiveLayout, 100);
        });

        // Enhanced toggle switch functionality for mobile
        document.querySelectorAll('.form-check-input').forEach(toggle => {
            toggle.addEventListener('touchstart', function (e) {
                e.preventDefault(); // Prevent double-tap zoom
            });

            toggle.addEventListener('touchend', function (e) {
                e.preventDefault();
                this.checked = !this.checked;
                this.dispatchEvent(new Event('change'));
            });
        });

        // Better button handling for mobile
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('touchstart', function () {
                this.style.transform = 'scale(0.98)';
            });

            button.addEventListener('touchend', function () {
                this.style.transform = '';
            });
        });
    });

    // Utility function for responsive form handling
    function handleFormResponsive() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            if (window.innerWidth <= 767) {
                form.classList.add('mobile-form');
            } else {
                form.classList.remove('mobile-form');
            }
        });
    }

    // Call this on load and resize
    window.addEventListener('load', handleFormResponsive);
    window.addEventListener('resize', handleFormResponsive);
</script>