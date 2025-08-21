<?php
// CHRONONAV_WEB_DOSS/pages/admin/audit_logs.php
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Ensure only 'admin' role can access this page.
requireRole(['admin']);

$user = $_SESSION['user'];
$current_user_id = $user['id'];

// --- Fetch fresh admin data for display in header and profile sections ---
$stmt_admin = $conn->prepare("SELECT name, email, profile_img, role FROM users WHERE id = ?");
if ($stmt_admin) {
    $stmt_admin->bind_param("i", $current_user_id);
    $stmt_admin->execute();
    $result_admin = $stmt_admin->get_result();
    if ($result_admin->num_rows > 0) {
        $admin_data = $result_admin->fetch_assoc();
        $_SESSION['user'] = array_merge($_SESSION['user'], $admin_data);
        $display_username = htmlspecialchars($admin_data['name'] ?? 'Admin');
        $display_user_role = htmlspecialchars(ucfirst($admin_data['role'] ?? 'Admin'));
        $profile_img_src = (strpos($admin_data['profile_img'] ?? '', 'uploads/') === 0) ? '../../' . htmlspecialchars($admin_data['profile_img']) : '../../uploads/profiles/default-avatar.png';
    } else {
        error_log("Security Alert: Admin User ID {$current_user_id} in session not found in database for audit_logs.");
        session_destroy();
        header('Location: ../../auth/login.php?error=user_not_found');
        exit();
    }
    $stmt_admin->close();
} else {
    error_log("Database query preparation failed for admin profile in audit_logs: " . $conn->error);
    $display_username = 'Admin User';
    $display_user_role = 'Admin';
    $profile_img_src = '../../uploads/profiles/default-avatar.png';
}

$page_title = "System Audit Logs";
$current_page = "audit_logs";

$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// --- Audit Log Fetching Logic ---
$audit_logs = [];
$error = '';
$stmt_logs = $conn->prepare("
    SELECT al.id, al.user_id, al.action, al.timestamp, al.details,
           u.name AS user_name, u.role AS user_role
    FROM audit_log al
    JOIN users u ON al.user_id = u.id
    ORDER BY al.timestamp DESC
    LIMIT 500
");

if ($stmt_logs) {
    $stmt_logs->execute();
    $result_logs = $stmt_logs->get_result();
    while ($row = $result_logs->fetch_assoc()) {
        $audit_logs[] = $row;
    }
    $stmt_logs->close();
} else {
    $error = "Failed to load audit logs due to a database error: " . $conn->error;
    error_log("Failed to prepare audit logs query: " . $conn->error);
}

// --- START HTML STRUCTURE ---
require_once '../../templates/admin/header_admin.php';
require_once '../../templates/admin/sidenav_admin.php';
?>

<link rel="stylesheet" href="../../assets/css/admin_css/audit_log.css">

<div class="main-content-wrapper">
    <div class="container-fluid py-4">
        <h2 class="mb-4"><?= $page_title ?></h2>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Recent Audit Activities</h5>
            </div>
            <div class="card-body">
                <?php if (empty($audit_logs)): ?>
                    <div class="alert alert-info text-center">No audit logs found in the system.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Timestamp</th>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($audit_logs as $log): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($log['id']) ?></td>
                                        <td><?= date('Y-m-d H:i:s', strtotime($log['timestamp'])) ?></td>
                                        <td><?= htmlspecialchars($log['user_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="badge 
                                                <?php 
                                                    switch(htmlspecialchars($log['user_role'])) {
                                                        case 'admin': echo 'bg-danger'; break;
                                                        case 'faculty': echo 'bg-info'; break;
                                                        case 'student': echo 'bg-success'; break;
                                                        default: echo 'bg-secondary'; break;
                                                    }
                                                ?>">
                                                <?= htmlspecialchars(ucfirst($log['user_role'] ?? 'Unknown')) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($log['action']) ?></td>
                                        <td>
                                            <span class="d-inline-block text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($log['details']) ?>">
                                                <?= htmlspecialchars($log['details']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="../../assets/js/jquery.min.js"></script>
<script src="../../assets/js/script.js"></script>
<?php
require_once '../../templates/footer.php';
?>

<style>
  
</style>