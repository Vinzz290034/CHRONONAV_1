<?php
// CHRONONAV_WEBZ/pages/user/announcements.php

require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php'; // Assuming functions.php exists for common functions and requireRole()

// Ensure only logged-in users (or admins who can view user pages) can access this page
requireRole(['user', 'admin']); // Allows both users and admins to view this page.

$user = $_SESSION['user'];
$user_role = $user['role'] ?? 'guest'; // Get user role
$user_id = $user['id'] ?? null;

// --- Fetch fresh user data for display in header and profile sections ---
// This is crucial for the profile picture and name in the header dropdown
$stmt_user_data = $conn->prepare("SELECT name, email, profile_img FROM users WHERE id = ?");
if ($stmt_user_data) {
    $stmt_user_data->bind_param("i", $user_id);
    $stmt_user_data->execute();
    $result_user_data = $stmt_user_data->get_result();
    if ($result_user_data->num_rows > 0) {
        $user_from_db = $result_user_data->fetch_assoc();
        $_SESSION['user'] = array_merge($_SESSION['user'], $user_from_db); // Update session with fresh data
        $user = $_SESSION['user']; // Use the updated $user array for display
    } else {
        // Handle case where user might have been deleted from DB but session persists
        error_log("Security Alert: User ID {$user_id} in session not found in database for announcements (user).");
        session_destroy();
        header('Location: ../../auth/login.php?error=user_not_found');
        exit();
    }
    $stmt_user_data->close();
} else {
    error_log("Database query preparation failed for announcements (user): " . $conn->error);
    // Optionally redirect or show a user-friendly error
}

// Prepare variables for header display
$display_username = htmlspecialchars($user['name'] ?? 'Guest');
$display_user_role = htmlspecialchars(ucfirst($user['role'] ?? 'User'));

// Determine the correct profile image source path for the header
$display_profile_img = htmlspecialchars($user['profile_img'] ?? 'uploads/profiles/default-avatar.png');
$profile_img_src = (strpos($display_profile_img, 'uploads/') === 0) ? '../../' . $display_profile_img : $display_profile_img;


$page_title = "Campus Announcements"; // Changed title to be more user-facing
$current_page = "announcements";

$message = '';
$message_type = '';

// Variables for the announcement form (for both create and edit) - only relevant if admin can manage here
$announcement_id_to_edit = null;
$announcement_title_form = '';
$announcement_content_form = '';
$form_action_text = 'Publish Announcement';

// --- Handle Announcement Deletion (Only for Admin) ---
if ($user_role === 'admin' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $announcement_id = (int)$_GET['id'];

    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $announcement_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Announcement deleted successfully!";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Error deleting announcement: " . $stmt->error;
            $_SESSION['message_type'] = 'danger';
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Database error preparing delete: " . $conn->error;
        $_SESSION['message_type'] = 'danger';
    }
    header("Location: announcements.php"); // Redirect to clear GET parameters
    exit();
}

// --- Handle Announcement Editing (Load Data into Form) (Only for Admin) ---
if ($user_role === 'admin' && isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $announcement_id_to_edit = (int)$_GET['id'];
    $form_action_text = 'Update Announcement';

    // Fetch existing announcement data
    $stmt = $conn->prepare("SELECT title, content FROM announcements WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $announcement_id_to_edit);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $announcement_data = $result->fetch_assoc();
            $announcement_title_form = htmlspecialchars($announcement_data['title']);
            $announcement_content_form = htmlspecialchars($announcement_data['content']);
        } else {
            $_SESSION['message'] = "Announcement not found.";
            $_SESSION['message_type'] = 'danger';
            header("Location: announcements.php"); // Redirect if ID is invalid
            exit();
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Database error fetching announcement for edit: " . $conn->error;
        $_SESSION['message_type'] = 'danger';
        header("Location: announcements.php");
        exit();
    }
}


// --- Handle Form Submission (New Announcement Creation or Update) (Only for Admin) ---
if ($user_role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_announcement'])) {
    $title = trim($_POST['announcement_title'] ?? '');
    $content = trim($_POST['announcement_content'] ?? '');
    $submitted_announcement_id = $_POST['announcement_id'] ?? null; // Hidden field for ID if updating

    if (empty($title) || empty($content)) {
        $message = "Please fill in both the title and content for the announcement.";
        $message_type = 'danger';
    } else {
        if ($submitted_announcement_id && is_numeric($submitted_announcement_id)) {
            // It's an UPDATE operation
            $stmt = $conn->prepare("UPDATE announcements SET title = ?, content = ?, updated_at = NOW() WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("ssi", $title, $content, $submitted_announcement_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Announcement updated successfully!";
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = "Error updating announcement: " . $stmt->error;
                    $_SESSION['message_type'] = 'danger';
                }
                $stmt->close();
            } else {
                $_SESSION['message'] = "Database error preparing update: " . $conn->error;
                $_SESSION['message_type'] = 'danger';
            }
        } else {
            // It's an INSERT (Create New) operation
            $stmt = $conn->prepare("INSERT INTO announcements (user_id, title, content) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("iss", $user_id, $title, $content);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Announcement posted successfully!";
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = "Error posting announcement: " . $stmt->error;
                    $_SESSION['message_type'] = 'danger';
                }
                $stmt->close();
            } else {
                $_SESSION['message'] = "Database error preparing announcement: " . $conn->error;
                $_SESSION['message_type'] = 'danger';
            }
        }
        // Redirect after POST to prevent resubmission on refresh
        header("Location: announcements.php");
        exit();
    }
}

// --- Fetch All Announcements (for displaying) ---
$announcements = [];
$stmt_announcements = $conn->prepare("SELECT a.*, u.name as posted_by_name FROM announcements a JOIN users u ON a.user_id = u.id ORDER BY a.published_at DESC");
if ($stmt_announcements) {
    $stmt_announcements->execute();
    $result_announcements = $stmt_announcements->get_result();
    while ($row = $result_announcements->fetch_assoc()) {
        $announcements[] = $row;
    }
    $stmt_announcements->close();
} else {
    // This error will only show if fetching fails initially
    $message = "Error fetching announcements: " . $conn->error;
    $message_type = 'danger';
}

// Check for and display session messages after all processing
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']); // Clear the message after displaying
    unset($_SESSION['message_type']);
}

?>

<?php
// Include the user-specific header
require_once '../../templates/user/header_user.php';
?>

<?php
// Include the user-specific sidebar (sidenav)
require_once '../../templates/user/sidenav_user.php';
?>

<link rel="stylesheet" href="../../assets/css/user_css/announcements.css">

<div class="main-content-wrapper">
    <div class="main-dashboard-content announcement-board-page">
        <div class="announcement-board-header">
            <h1><?= htmlspecialchars($page_title) ?></h1>
            </div>

        <?php if ($message): // Display session message ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="user-support-container">
            <?php if ($user_role === 'admin'): // Only show announcement creation/edit form for admins ?>
            <div class="announcement-section card p-4 mb-4 create-post-section">
                <h2 class="card-title mb-4"><i class="fas fa-bullhorn me-2"></i> <?= ($announcement_id_to_edit) ? 'Edit Announcement' : 'Create New Announcement' ?></h2>
                <form action="announcements.php" method="POST" class="announcement-form">
                    <?php if ($announcement_id_to_edit): // Hidden field for ID when editing ?>
                        <input type="hidden" name="announcement_id" value="<?= htmlspecialchars($announcement_id_to_edit) ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="announcement_title" class="form-label">Title:</label>
                        <input type="text" id="announcement_title" name="announcement_title" class="form-control" placeholder="e.g., Important Schedule Change" value="<?= htmlspecialchars($announcement_title_form) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="announcement_content" class="form-label">Content:</label>
                        <textarea id="announcement_content" name="announcement_content" class="form-control" rows="8" placeholder="Write your announcement details here..." required><?= htmlspecialchars($announcement_content_form) ?></textarea>
                    </div>
                    <button type="submit" name="submit_announcement" class="btn btn-primary"><?= $form_action_text ?></button>
                    <?php if ($announcement_id_to_edit): // Add a cancel button for edit mode ?>
                        <a href="announcements.php" class="btn btn-secondary ms-2">Cancel Edit</a>
                    <?php endif; ?>
                </form>
            </div>
            <?php endif; ?>

            <div class="announcement-section card p-4 view-posts-section">
                <h2 class="card-title mb-4"><i class="fas fa-clipboard-list me-2"></i> All Announcements</h2>
                <?php if (!empty($announcements)): ?>
                    <div class="announcement-list">
                        <?php foreach ($announcements as $announcement): ?>
                        <div class="announcement-item mb-3 p-3 border rounded">
                            <div class="announcement-meta mb-2">
                                <span class="badge bg-info text-dark me-2"><i class="fas fa-calendar-alt"></i> <?= date('F j, Y, g:i a', strtotime($announcement['published_at'])) ?></span>
                                <span class="badge bg-secondary"><i class="fas fa-user-tie"></i> Posted by: <?= htmlspecialchars($announcement['posted_by_name']) ?></span>
                            </div>
                            <h3><?= htmlspecialchars($announcement['title']) ?></h3>
                            <div class="announcement-content">
                                <?php if (!empty($announcement['image_path'])): ?>
                                    <img src="../../<?= htmlspecialchars($announcement['image_path']) ?>" alt="Announcement Image" class="img-fluid mb-3">
                                <?php endif; ?>
                                <p><?= nl2br(htmlspecialchars($announcement['content'])) ?></p>
                            </div>
                            <?php if ($user_role === 'admin'): ?>
                                <div class="announcement-actions mt-3">
                                    <a href="announcements.php?action=edit&id=<?= htmlspecialchars($announcement['id']) ?>" class="btn btn-warning btn-sm me-2">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="announcements.php?action=delete&id=<?= htmlspecialchars($announcement['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this announcement? This action cannot be undone.');">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No announcements available at the moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Include the footer
include_once '../../templates/footer.php';
?>