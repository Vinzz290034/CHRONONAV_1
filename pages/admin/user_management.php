<?php
// pages/admin/user_management.php

// Ensure user is logged in and session is started, and user data is available
require_once '../../middleware/auth_check.php';
// Include the logic file that handles database interactions and form submissions.
// This file also defines the ROLES constant and includes db_connect.php.
require_once '../../backend/admin/user_management_logic.php';

// Restrict access to 'admin' role only
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../auth/logout.php");
    exit();
}

$user = $_SESSION['user']; // Get current admin user data for display purposes

// Page-specific variables for header and sidenav
$page_title = "User Management";
$current_page = "user_management"; // Set to match potential active link in sidenav
$display_name = htmlspecialchars($user['name'] ?? 'Admin');

require_once '../../templates/admin/header_admin.php';
require_once '../../templates/admin/sidenav_admin.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'ChronoNav - User Management' ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link rel="stylesheet" as="style" onload="this.rel='stylesheet'"
        href="https://fonts.googleapis.com/css2?display=swap&family=Inter:wght@400;500;700;900&family=Noto+Sans:wght@400;500;700;900">

    <!-- Font Family -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
    <!-- important------------------------------------------------------------------------------------------------ -->

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon"
        href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png">

    <style>
        body {
            font-family: "Space Grotesk", "Noto Sans", sans-serif;
            background-color: #ffffff;
            height: 100vh;
        }

        .main-content-wrapper {
            margin-left: 20%;
            transition: margin-left 0.3s ease;
        }

        .container-fluid {
            padding: 2rem;
        }

        h2 {
            color: #0e151b;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: -0.015em;
            margin-bottom: 1.5rem;
        }

        h3 {
            color: #0e151b;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.015em;
            margin-bottom: 1rem;
        }

        .user-table-container {
            margin-bottom: 2rem;
        }

        .table {
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 0;
        }

        .table th {
            background-color: #f8fafb;
            color: #0e151b;
            font-weight: 600;
            border-bottom: 1px solid #d1dce6;
            padding: 1rem;
        }

        .table td {
            border-bottom: 1px solid #f1f1f1;
            color: #0e151b;
            padding: 1rem;
            vertical-align: middle;
        }

        .table-hover tbody tr:hover {
            background-color: #f0f2f5;
        }

        .badge {
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 4px;
        }

        .badge-active {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .badge-disabled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .btn {
            font-weight: 600;
            letter-spacing: 0.015em;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.5rem;
        }

        .btn-sm {
            height: 32px;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        .btn-warning {
            background-color: #ffc107;
            color: #000000;
        }

        .btn-warning:hover {
            background-color: #ffca2c;
            color: #000000;
        }

        .btn-danger {
            background-color: #dc3545;
            color: #ffffff;
        }

        .btn-danger:hover {
            background-color: #bb2d3b;
            color: #ffffff;
        }

        .btn-success {
            background-color: #198754;
            color: #ffffff;
        }

        .btn-success:hover {
            background-color: #157347;
            color: #ffffff;
        }

        .btn-dark {
            background-color: #212529;
            color: #ffffff;
        }

        .btn-dark:hover {
            background-color: #424649;
            color: #ffffff;
        }

        .btn-primary {
            background-color: #1d7dd7;
            border-color: #1d7dd7;
            color: #f8fafb;
        }

        .btn-primary:hover {
            background-color: #1a6fc0;
            border-color: #1a6fc0;
        }

        .table-actions {
            white-space: nowrap;
        }

        .table-actions .btn {
            margin-right: 0.5rem;
        }

        .table-actions .btn:last-child {
            margin-right: 0;
        }

        .alert {
            border: none;
            border-radius: 8px;
            padding: 1rem 1.25rem;
            margin-bottom: 1rem;
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

        /* Modal Styling */
        .modal-content {
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            background-color: #ffffff;
            border-bottom: 1px solid #d1dce6;
            padding: 1.5rem;
        }

        .modal-title {
            color: #0e151b;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.015em;
            margin: 0;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            background-color: #ffffff;
            border-top: 1px solid #d1dce6;
            padding: 1rem 1.5rem;
        }

        .form-label {
            color: #0e151b;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-control,
        .form-select {
            background-color: #f8fafb;
            border: 1px solid #d1dce6;
            color: #0e151b;
            padding: 0.75rem;
            border-radius: 0.5rem;
        }

        .form-control:focus,
        .form-select:focus {
            box-shadow: none;
            border-color: #1d7dd7;
            background-color: #ffffff;
        }

        .form-control[readonly] {
            background-color: #f8fafb;
            color: #6c757d;
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

        /* Add these media queries at the end of your existing CSS - EXACT SAME STRUCTURE AS PREVIOUS PAGES */

        /* Mobile: 767px and below */
        @media (max-width: 767px) {
            .main-content-wrapper {
                margin-left: 0 !important;
                padding: 2rem 0.5rem !important;
            }

            .container-fluid {
                padding: 0rem !important;
            }

            h2.fs-3 {
                font-size: 1.5rem !important;
                text-align: center;
                width: 100%;
            }

            h3 {
                font-size: 1.1rem !important;
                text-align: center;
            }

            .user-table-container {
                padding: 1rem !important;
                margin-bottom: 1rem !important;
            }

            .table-responsive {
                border: 1px solid #d1dce6;
                border-radius: 8px;
            }

            .table th,
            .table td {
                padding: 0.75rem 0.5rem !important;
                font-size: 0.875rem;
            }

            .table-actions {
                white-space: normal !important;
                text-align: center;
            }

            .table-actions .btn {
                margin-bottom: 0.5rem;
                display: block;
                width: 100%;
                margin-right: 0 !important;
            }

            .table-actions form {
                display: block;
                margin-bottom: 0.5rem;
            }

            .badge {
                padding: 0.375rem 0.75rem !important;
                font-size: 0.75rem;
            }

            .btn-sm {
                height: auto !important;
                padding: 0.5rem 0.75rem !important;
            }

            .modal-dialog {
                margin: 1rem !important;
                max-width: calc(100% - 2rem) !important;
            }

            .modal-content {
                max-height: 100vh;
                overflow-y: auto;
            }

            .alert {
                padding: 0.75rem 1rem !important;
                margin-bottom: 1rem !important;
            }
        }

        /* Tablet: 768px to 1023px */
        @media (min-width: 768px) and (max-width: 1023px) {
            .main-content-wrapper {
                margin-left: 15% !important;
                padding: 2rem 0.5rem !important;
            }

            .container-fluid {
                padding: 1.5rem !important;
            }

            h2.fs-3 {
                font-size: 1.75rem !important;
            }

            h3 {
                font-size: 1.25rem !important;
            }

            .user-table-container {
                padding: 1.5rem !important;
            }

            .table th,
            .table td {
                padding: 0.875rem !important;
            }

            .table-actions .btn {
                margin-right: 0.25rem !important;
                margin-bottom: 0.25rem;
            }

            .modal-dialog {
                max-width: 600px !important;
                margin: 1.75rem auto !important;
            }
        }

        /* Desktop: 1024px and above */
        @media (min-width: 1024px) {
            .main-content-wrapper {
                margin-left: 20% !important;
            }

            .container-fluid {
                padding: 2rem !important;
            }

            h2.fs-3 {
                font-size: 2rem !important;
            }

            h3 {
                font-size: 1.375rem !important;
            }

            .user-table-container {
                padding: 2rem !important;
            }

            .table th,
            .table td {
                padding: 1rem !important;
            }

            .table-actions .btn {
                margin-right: 0.5rem !important;
                margin-bottom: 0;
            }

            .modal-dialog {
                max-width: 500px !important;
                margin: 1.75rem auto !important;
            }
        }

        /* Responsive sidebar adjustments */
        @media (max-width: 1023px) {
            .sidebar-toggle {
                display: flex;
                position: fixed;
                right: 1rem;
                left: unset;
                top: 5rem;
                z-index: 1100;
                width: 30px;
                height: 30px;
                background: #f0f2f5;
                color: #111418;
                border: 1px solid #ddd;
                border-radius: 50%;
                cursor: pointer;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                transition: transform 0.3s ease, background-color 0.3s ease, right 0.3s ease;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }

        /* Ensure proper spacing on all devices */
        @media (max-width: 767px) {
            .py-4 {
                padding-top: 1rem !important;
                padding-bottom: 1rem !important;
            }

            .p-3 {
                padding: 1rem !important;
            }

            .border-1 {
                border-width: 1px !important;
            }

            .rounded-3 {
                border-radius: 0.5rem !important;
            }
        }

        /* Improve modal responsiveness */
        @media (max-width: 767px) {
            .modal-content {
                border-radius: 0.5rem !important;
            }

            .modal-header,
            .modal-body,
            .modal-footer {
                padding: 1rem !important;
            }

            .modal-title {
                font-size: 1.25rem !important;
            }

            .form-control,
            .form-select {
                font-size: 16px !important;
                /* Prevents zoom on iOS */
            }
        }

        @media (max-width: 575px) {
            .modal-content {
                margin: 0;
                border-radius: 0;
                min-height: 100vh;
            }

            .modal-dialog {
                margin: 0 !important;
                max-width: 100% !important;
                height: 100vh;
            }
        }

        /* Better touch targets for mobile */
        @media (max-width: 767px) {
            .btn {
                min-height: 44px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .table tbody tr {
                min-height: 60px;
            }

            .table-actions .btn {
                min-height: 40px;
            }
        }

        /* Enhanced table layout for mobile */
        @media (max-width: 767px) {
            .table thead {
                display: none;
            }

            .table tbody tr {
                display: block;
                margin-bottom: 1rem;
                border: 1px solid #d1dce6;
                border-radius: 0.5rem;
                padding: 1rem;
            }

            .table tbody td {
                display: block;
                text-align: right;
                padding: 0.5rem 0.75rem !important;
                border: none;
                position: relative;
            }

            .table tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #0e151b;
                position: absolute;
                left: 0.75rem;
                top: 50%;
                transform: translateY(-50%);
            }

            .table-actions td::before {
                display: none;
            }

            .table-actions {
                text-align: center !important;
                padding-top: 1rem !important;
                border-top: 1px solid #f1f1f1;
            }
        }

        /* Print styles for user management */
        @media print {
            .main-content-wrapper {
                margin-left: 0 !important;
                max-width: 100% !important;
            }

            .btn,
            .table-actions,
            .sidebar-toggle {
                display: none !important;
            }

            .table tbody tr {
                break-inside: avoid;
            }
        }

        /* Responsive sidebar toggle button */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1100;
            background: #3e99f4;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 12px;
            font-size: 1.2rem;
        }

        @media (max-width: 1023px) {
            .sidebar-toggle {
                display: flex;
                position: fixed;
                right: 1rem;
                left: unset;
                top: 5rem;
                z-index: 1100;
                width: 30px;
                height: 30px;
                background: #f0f2f5;
                color: #111418;
                border: 1px solid #ddd;
                border-radius: 50%;
                cursor: pointer;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                transition: transform 0.3s ease, background-color 0.3s ease, right 0.3s ease;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }

        /* Enhanced JavaScript functionality for responsive behavior */
        @media (max-width: 767px) {
            /* Add data-labels to table cells for mobile view */
            .table td:nth-child(1)::before { content: "ID: "; }
            .table td:nth-child(2)::before { content: "Name: "; }
            .table td:nth-child(3)::before { content: "Email: "; }
            .table td:nth-child(4)::before { content: "Role: "; }
            .table td:nth-child(5)::before { content: "Status: "; }
        }

        /* Ensure proper text sizing on mobile */
        @media (max-width: 767px) {
            .table td,
            .table th {
                font-size: 0.875rem;
            }

            .btn-sm {
                font-size: 0.8rem;
            }
        }

        /* Improved grid layout for user management */
        @media (max-width: 767px) {
            .user-table-container .row {
                flex-direction: column;
            }

            .text-center.text-muted.mt-4 {
                font-size: 0.875rem;
                padding: 0 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="main-content-wrapper">
            <div class="container-fluid py-4">
                <h2 class="fs-3"><?= $page_title ?></h2>

                <?php
                // Display session messages (success/error/warning)
                if (!empty($message)) {
                    echo '<div class="alert alert-' . htmlspecialchars($message_type) . ' alert-dismissible fade show" role="alert">';
                    echo htmlspecialchars($message);
                    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    echo '</div>';
                }
                ?>

                <div class="user-table-container border border-1 p-3 rounded-3">
                    <h3>List of ChronoNav Users</h3>
                    <?php if (!empty($users)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($u['id']) ?></td>
                                            <td><?= htmlspecialchars($u['name']) ?></td>
                                            <td><?= htmlspecialchars($u['email']) ?></td>
                                            <td><?= htmlspecialchars($u['role']) ?></td>
                                            <td>
                                                <?php if ($u['is_active'] == 1): ?>
                                                    <span class="badge badge-active">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-disabled">Disabled</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="table-actions">
                                                <button class="btn btn-sm btn-warning edit-role-btn" data-bs-toggle="modal"
                                                    data-bs-target="#editRoleModal" data-id="<?= htmlspecialchars($u['id']) ?>"
                                                    data-name="<?= htmlspecialchars($u['name']) ?>"
                                                    data-current-role="<?= htmlspecialchars($u['role']) ?>"
                                                    <?= ((int) $u['id'] === (int) $_SESSION['user']['id']) ? 'disabled' : '' ?>>
                                                    <i class="fas fa-user-tag"></i> Edit Role
                                                </button>

                                                <form action="user_management.php" method="POST" style="display:inline;"
                                                    onsubmit="return confirm('Are you sure you want to <?= $u['is_active'] == 1 ? 'disable' : 'enable' ?> this account?');">
                                                    <input type="hidden" name="action" value="toggle_active_status">
                                                    <input type="hidden" name="user_id"
                                                        value="<?= htmlspecialchars($u['id']) ?>">
                                                    <input type="hidden" name="current_status"
                                                        value="<?= htmlspecialchars($u['is_active']) ?>">
                                                    <button type="submit"
                                                        class="btn btn-sm <?= $u['is_active'] == 1 ? 'btn-danger' : 'btn-success' ?>"
                                                        <?= ((int) $u['id'] === (int) $_SESSION['user']['id']) ? 'disabled' : '' ?>>
                                                        <i
                                                            class="fas <?= $u['is_active'] == 1 ? 'fa-ban' : 'fa-check-circle' ?>"></i>
                                                        <?= $u['is_active'] == 1 ? 'Disable' : 'Enable' ?>
                                                    </button>
                                                </form>

                                                <form action="user_management.php" method="POST" style="display:inline;"
                                                    onsubmit="return confirm('WARNING: Are you absolutely sure you want to PERMANENTLY DELETE this user account? This action cannot be undone.');">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id"
                                                        value="<?= htmlspecialchars($u['id']) ?>">
                                                    <button type="submit" class="btn btn-sm btn-dark"
                                                        <?= ((int) $u['id'] === (int) $_SESSION['user']['id']) ? 'disabled' : '' ?>>
                                                        <i class="fas fa-trash"></i> Delete Perm.
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center">No users found.</div>
                    <?php endif; ?>
                </div>
            </div>

            <?php include '../../templates/footer.php'; ?>
        </div>
    </div>

    <div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="editRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRoleModalLabel">Edit User Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="user_management.php" method="POST">
                        <input type="hidden" name="action" value="edit_role">
                        <input type="hidden" id="editRoleId" name="user_id">
                        <div class="mb-3">
                            <label for="editRoleUserName" class="form-label">User Name:</label>
                            <input type="text" class="form-control" id="editRoleUserName" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="newRole" class="form-label">Select New Role</label>
                            <select class="form-select" id="newRole" name="new_role" required>
                                <?php foreach (ROLES as $role): ?>
                                    <option value="<?= htmlspecialchars($role) ?>"><?= ucfirst(htmlspecialchars($role)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Role</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="../../assets/js/script.js"></script>
    <script>
        // JavaScript to populate the Edit Role Modal when it's shown
        var editRoleModal = document.getElementById('editRoleModal');
        editRoleModal.addEventListener('show.bs.modal', function (event) {
            // Get the button that triggered the modal
            var button = event.relatedTarget;

            // Extract info from data-* attributes
            var userId = button.getAttribute('data-id');
            var userName = button.getAttribute('data-name');
            var currentRole = button.getAttribute('data-current-role');

            // Get references to the modal elements
            var modalTitle = editRoleModal.querySelector('.modal-title');
            var modalUserIdInput = editRoleModal.querySelector('#editRoleId');
            var modalUserNameInput = editRoleModal.querySelector('#editRoleUserName');
            var modalNewRoleSelect = editRoleModal.querySelector('#newRole');

            // Update the modal's content
            modalTitle.textContent = 'Edit Role for ' + userName;
            modalUserIdInput.value = userId;
            modalUserNameInput.value = userName;
            modalNewRoleSelect.value = currentRole; // Set the dropdown to the user's current role
        });
    </script>
</body>

</html>