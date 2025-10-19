<?php
// CHRONONAV_WEB_DOSS/pages/admin/feedback_list.php

require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php'; // Using mysqli_connect for consistency with audit_logs
require_once '../../includes/functions.php';

// Ensure only 'admin' role can access this page.
requireRole(['admin']);

$user = $_SESSION['user'];
$current_user_id = $user['id'];

// --- Fetch fresh admin data for display in header and profile sections ---
// This block is crucial for ensuring the header has the latest admin info
$stmt_admin = $conn->prepare("SELECT name, email, profile_img, role FROM users WHERE id = ?");
if ($stmt_admin) {
    $stmt_admin->bind_param("i", $current_user_id);
    $stmt_admin->execute();
    $result_admin = $stmt_admin->get_result();
    if ($result_admin->num_rows > 0) {
        $admin_data = $result_admin->fetch_assoc();
        // Update session with fresh data to ensure profile_img is current
        $_SESSION['user'] = array_merge($_SESSION['user'], $admin_data);
        $display_username = htmlspecialchars($admin_data['name'] ?? 'Admin');
        $display_user_role = htmlspecialchars(ucfirst($admin_data['role'] ?? 'Admin'));
        $profile_img_src = (strpos($admin_data['profile_img'] ?? '', 'uploads/') === 0) ? '../../' . htmlspecialchars($admin_data['profile_img']) : '../../uploads/profiles/default-avatar.png';
    } else {
        error_log("Security Alert: Admin User ID {$current_user_id} in session not found in database for feedback_list.");
        session_destroy();
        header('Location: ../../auth/login.php?error=user_not_found');
        exit();
    }
    $stmt_admin->close();
} else {
    error_log("Database query preparation failed for admin profile in feedback_list: " . $conn->error);
    $display_username = 'Admin User';
    $display_user_role = 'Admin';
    $profile_img_src = '../../uploads/profiles/default-avatar.png';
}

$page_title = "User Feedback List";
$current_page = "feedback_list"; // For sidenav highlighting (you might need to add this to your sidenav config)

$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// --- Fetch All Feedback Data ---
// Using 'feedback_id', 'feedback_type', and 'submitted_at' based on database schema
$feedbacks = [];
$error = '';
$stmt_feedbacks = $conn->prepare("
    SELECT f.feedback_id, f.user_id, f.feedback_type, f.subject, f.message, f.submitted_at,
           u.name AS user_name, u.email AS user_email, u.role AS user_role
    FROM feedback f
    JOIN users u ON f.user_id = u.id
    ORDER BY f.submitted_at DESC
");

if ($stmt_feedbacks) {
    $stmt_feedbacks->execute();
    $result_feedbacks = $stmt_feedbacks->get_result();
    while ($row = $result_feedbacks->fetch_assoc()) {
        $feedbacks[] = $row;
    }
    $stmt_feedbacks->close();
} else {
    $error = "Failed to load feedback due to a database error: " . $conn->error;
    error_log("Failed to prepare feedback query: " . $conn->error);
}

// Include Header and Sidenav
require_once '../../templates/admin/header_admin.php';
require_once '../../templates/admin/sidenav_admin.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<style>
    body {
        font-family: "Space Grotesk", "Noto Sans", sans-serif;
        background-color: #fff;
        min-height: 100vh;
    }

    .layout-container {
        min-height: 100vh;
    }

    .main-content-wrapper {
        margin-left: 20%;
        transition: margin-left 0.3s ease;
    }

    .main-dashboard-content {
        font-family: "Space Grotesk", "Noto Sans", sans-serif;
        max-width: 100%;
        height: 100vh;
    }

    .dashboard-header h2 {
        color: #0e151b;
        font-size: 28px;
        margin-bottom: 1.5rem;
    }

    .card {
        background-color: #ffffff;
        border: none;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
    }

    .card-header {
        background-color: #ffffff;
        border-bottom: 1px solid #d1dce6;
        padding: 1.5rem;
    }

    .card-header h5 {
        color: #0e151b;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: -0.015em;
        margin: 0;
    }

    .card-body {
        padding: 1.5rem;
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
        background-color: #f8fafb;
    }

    .table-bordered {
        border: 1px solid #d1dce6;
    }

    .table-bordered th,
    .table-bordered td {
        border: 1px solid #d1dce6;
    }

    .btn-primary {
        background-color: #1d7dd7;
        border-color: #1d7dd7;
        color: #f8fafb;
        font-weight: 600;
        letter-spacing: 0.015em;
        padding: 0.5rem 1rem;
    }

    .btn-primary:hover {
        background-color: #1a6fc0;
        border-color: #1a6fc0;
    }

    .btn-info {
        background-color: #0dcaf0;
        border-color: #0dcaf0;
        color: #000000;
        font-weight: 600;
        letter-spacing: 0.015em;
        padding: 0.5rem 1rem;
    }

    .btn-info:hover {
        background-color: #31d2f2;
        border-color: #31d2f2;
    }

    .btn-sm {
        height: 32px;
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }

    .alert {
        border: none;
        border-radius: 8px;
        padding: 1rem 1.25rem;
        margin-bottom: 1rem;
    }

    .alert-info {
        background-color: #cff4fc;
        color: #055160;
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

    .action-buttons {
        white-space: nowrap;
    }

    .action-buttons .btn {
        margin-right: 0.5rem;
    }

    .action-buttons .btn:last-child {
        margin-right: 0;
    }

    .ml-1 {
        margin-left: 0.25rem;
    }

    /* Badge Styling */
    .badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.375rem 0.75rem;
        border-radius: 0.375rem;
    }

    .bg-danger {
        background-color: #dc3545 !important;
    }

    .bg-info {
        background-color: #0dcaf0 !important;
    }

    .bg-success {
        background-color: #198754 !important;
    }

    .bg-warning {
        background-color: #ffc107 !important;
        color: #000 !important;
    }

    .bg-primary {
        background-color: #1d7dd7 !important;
    }

    .bg-secondary {
        background-color: #6c757d !important;
    }

    /* Truncate Text */
    .truncate-text {
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: inline-block;
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

    @media (max-width: 768px) {
        .main-content-wrapper {
            margin-left: 0;
        }

        .main-dashboard-content {
            padding: 1rem;
        }

        .table-responsive {
            border: 1px solid #d1dce6;
            border-radius: 8px;
        }

        .truncate-text {
            max-width: 150px;
        }

        .action-buttons {
            white-space: normal;
        }

        .action-buttons .btn {
            margin-bottom: 0.5rem;
            display: block;
            width: 100%;
        }

        [data-label]::before {
            content: attr(data-label);
            font-weight: 600;
            color: #0e151b;
            display: block;
            margin-bottom: 0.25rem;
        }

        .table td {
            display: block;
            border: none;
            border-bottom: 1px solid #f1f1f1;
        }

        .table th {
            display: none;
        }
    }
</style>

<div class="main-content-wrapper">
    <div class="main-dashboard-content p-4">
        <div class="dashboard-header">
            <h2 class="fs-3 px-3 fw-bold"><?= $page_title ?></h2>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">All User Feedback</h5>
            </div>
            <div class="card-body">
                <?php if (empty($feedbacks)): ?>
                    <div class="alert alert-info mb-0">No feedback has been submitted yet.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Submitted By</th>
                                    <th>Email</th>
                                    <th>Category</th>
                                    <th>Subject</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($feedbacks as $feedback): ?>
                                    <tr>
                                        <td data-label="ID"><?= htmlspecialchars($feedback['feedback_id']) ?></td>
                                        <td data-label="Date"><?= date('Y-m-d H:i:s', strtotime($feedback['submitted_at'])) ?>
                                        </td>
                                        <td data-label="Submitted By">
                                            <?= htmlspecialchars($feedback['user_name'] ?? 'N/A') ?>
                                            <span class="badge
                                                <?php
                                                switch (htmlspecialchars($feedback['user_role'])) {
                                                    case 'admin':
                                                        echo 'bg-danger';
                                                        break;
                                                    case 'faculty':
                                                        echo 'bg-info';
                                                        break;
                                                    case 'user':
                                                        echo 'bg-success';
                                                        break; /* Assuming 'user' is student role */
                                                    default:
                                                        echo 'bg-secondary';
                                                        break;
                                                }
                                                ?>">
                                                <?= htmlspecialchars(ucfirst($feedback['user_role'] ?? 'Unknown')) ?>
                                            </span>
                                        </td>
                                        <td data-label="Email"><?= htmlspecialchars($feedback['user_email'] ?? 'N/A') ?></td>
                                        <td data-label="Category">
                                            <span class="badge
                                                <?php
                                                // Use feedback_type here instead of category as per database schema
                                                switch (htmlspecialchars($feedback['feedback_type'])) {
                                                    case 'Suggestion':
                                                        echo 'bg-info';
                                                        break;
                                                    case 'Bug Report':
                                                        echo 'bg-danger';
                                                        break;
                                                    case 'Complaint':
                                                        echo 'bg-warning';
                                                        break;
                                                    case 'General Inquiry':
                                                        echo 'bg-primary';
                                                        break;
                                                    case 'Feature Request':
                                                        echo 'bg-success';
                                                        break; // Added based on your screenshot
                                                    default:
                                                        echo 'bg-secondary';
                                                        break;
                                                }
                                                ?>">
                                                <?= htmlspecialchars($feedback['feedback_type']) ?>
                                            </span>
                                        </td>
                                        <td data-label="Subject">
                                            <span class="truncate-text" title="<?= htmlspecialchars($feedback['subject']) ?>">
                                                <?= htmlspecialchars($feedback['subject']) ?>
                                            </span>
                                        </td>
                                        <td data-label="Message">
                                            <span class="truncate-text" title="<?= htmlspecialchars($feedback['message']) ?>">
                                                <?= htmlspecialchars($feedback['message']) ?>
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

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php
// Include the common footer which closes <body> and <html> and includes common JS
require_once '../../templates/footer.php';
?>