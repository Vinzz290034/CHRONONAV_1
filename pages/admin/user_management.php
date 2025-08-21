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
<link rel="stylesheet" href="../../assets/css/admin_css/user_managements.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <div class="wrapper">
        <div class="main-content-wrapper">
            <div class="container-fluid py-4">
                <h2><?= $page_title ?></h2>

                <?php
                // Display session messages (success/error/warning)
                if (!empty($message)) {
                    echo '<div class="alert alert-' . htmlspecialchars($message_type) . ' alert-dismissible fade show" role="alert">';
                    echo htmlspecialchars($message);
                    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    echo '</div>';
                }
                ?>

                <div class="user-table-container">
                    <h3>List of ChronoNav Users</h3>
                    <?php if (!empty($users)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
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
                                                <button class="btn btn-sm btn-warning edit-role-btn"
                                                    data-bs-toggle="modal" data-bs-target="#editRoleModal"
                                                    data-id="<?= htmlspecialchars($u['id']) ?>"
                                                    data-name="<?= htmlspecialchars($u['name']) ?>"
                                                    data-current-role="<?= htmlspecialchars($u['role']) ?>"
                                                    <?= ((int)$u['id'] === (int)$_SESSION['user']['id']) ? 'disabled' : '' ?>
                                                >
                                                    <i class="fas fa-user-tag"></i> Edit Role
                                                </button>

                                                <form action="user_management.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to <?= $u['is_active'] == 1 ? 'disable' : 'enable' ?> this account?');">
                                                    <input type="hidden" name="action" value="toggle_active_status">
                                                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($u['id']) ?>">
                                                    <input type="hidden" name="current_status" value="<?= htmlspecialchars($u['is_active']) ?>">
                                                    <button type="submit" class="btn btn-sm <?= $u['is_active'] == 1 ? 'btn-danger' : 'btn-success' ?>"
                                                        <?= ((int)$u['id'] === (int)$_SESSION['user']['id']) ? 'disabled' : '' ?>
                                                    >
                                                        <i class="fas <?= $u['is_active'] == 1 ? 'fa-ban' : 'fa-check-circle' ?>"></i> <?= $u['is_active'] == 1 ? 'Disable' : 'Enable' ?>
                                                    </button>
                                                </form>

                                                <form action="user_management.php" method="POST" style="display:inline;" onsubmit="return confirm('WARNING: Are you absolutely sure you want to PERMANENTLY DELETE this user account? This action cannot be undone.');">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($u['id']) ?>">
                                                    <button type="submit" class="btn btn-sm btn-dark"
                                                        <?= ((int)$u['id'] === (int)$_SESSION['user']['id']) ? 'disabled' : '' ?>
                                                    >
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

            <?php include '../../templates/footer.php'; // Your footer ?>
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
                                    <option value="<?= htmlspecialchars($role) ?>"><?= ucfirst(htmlspecialchars($role)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Role</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    
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