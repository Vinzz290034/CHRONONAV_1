<?php
// CHRONONAV_WEB_DOSS/pages/admin/announcements.php

require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';
require_once '../../includes/audit_log.php'; // NEW: Include the audit log function

$user = $_SESSION['user'];
$user_role = $user['role'] ?? 'guest';
$user_id = $user['id'] ?? null;
$user_name = $user['name'] ?? 'Unknown Admin'; // NEW: Get user name for log details

// Ensure only admins can access this page for managing announcements
if ($user_role !== 'admin') {
    $_SESSION['message'] = "Access denied. You do not have permission to manage announcements.";
    $_SESSION['message_type'] = "danger";
    header("Location: ../user/dashboard.php");
    exit();
}

$page_title = "Manage Campus Announcements";
$current_page = "announcements";

$message = '';
$message_type = '';

// Variables for the announcement form (for both create and edit)
$announcement_id_to_edit = null;
$announcement_title_form = '';
$announcement_content_form = '';
$current_image_path = null;
$form_action_text = 'Publish Announcement';

// --- Handle Announcement Deletion ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $announcement_id = (int)$_GET['id'];
    $deleted_title = 'N/A'; // Default value in case we can't fetch it

    // First, get the image path and title to delete the file from the server and log the action
    $stmt_fetch = $conn->prepare("SELECT title, image_path FROM announcements WHERE id = ?");
    if ($stmt_fetch) {
        $stmt_fetch->bind_param("i", $announcement_id);
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();
        if ($row = $result_fetch->fetch_assoc()) {
            $image_path_to_delete = $row['image_path'];
            $deleted_title = $row['title'];
            // Check if the file exists and is not the default path before unlinking
            if (!empty($image_path_to_delete) && file_exists('../../' . $image_path_to_delete)) {
                unlink('../../' . $image_path_to_delete);
            }
        }
        $stmt_fetch->close();
    }

    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $announcement_id);
        if ($stmt->execute()) {
            // NEW: Log the deletion action
            $action = 'Announcement Deleted';
            $details = "Admin '{$user_name}' deleted announcement '{$deleted_title}' (ID: {$announcement_id}).";
            log_audit_action($conn, $user_id, $action, $details);

            $_SESSION['message'] = "Announcement and associated image deleted successfully!";
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
    header("Location: announcements.php");
    exit();
}

// --- Handle Announcement Editing (Load Data into Form) ---
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $announcement_id_to_edit = (int)$_GET['id'];
    $form_action_text = 'Update Announcement';

    // Fetch existing announcement data
    $stmt = $conn->prepare("SELECT title, content, image_path FROM announcements WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $announcement_id_to_edit);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $announcement_data = $result->fetch_assoc();
            $announcement_title_form = htmlspecialchars($announcement_data['title']);
            $announcement_content_form = htmlspecialchars($announcement_data['content']);
            $current_image_path = htmlspecialchars($announcement_data['image_path']);
        } else {
            $_SESSION['message'] = "Announcement not found.";
            $_SESSION['message_type'] = 'danger';
            header("Location: announcements.php");
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

// --- Handle Form Submission (New Announcement Creation or Update) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['announcement_title'] ?? '');
    $content = trim($_POST['announcement_content'] ?? '');
    $submitted_announcement_id = $_POST['announcement_id'] ?? null;
    $image_path = null;
    $has_new_image = false;

    if (empty($title) || empty($content)) {
        $message = "Please fill in both the title and content for the announcement.";
        $message_type = 'danger';
    } else {
        // Handle file upload
        if (isset($_FILES['announcement_image']) && $_FILES['announcement_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../uploads/announcements/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['announcement_image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($file_extension, $allowed_extensions) && $_FILES['announcement_image']['size'] < 5000000) {
                $unique_filename = uniqid('announcement_', true) . '.' . $file_extension;
                $target_file = $upload_dir . $unique_filename;

                if (move_uploaded_file($_FILES['announcement_image']['tmp_name'], $target_file)) {
                    $image_path = 'uploads/announcements/' . $unique_filename;
                    $has_new_image = true;
                } else {
                    $_SESSION['message'] = "Error uploading image.";
                    $_SESSION['message_type'] = 'danger';
                    header("Location: announcements.php");
                    exit();
                }
            } else {
                $_SESSION['message'] = "Invalid file type or size. Please use JPG, PNG, or GIF files under 5MB.";
                $_SESSION['message_type'] = 'danger';
                header("Location: announcements.php");
                exit();
            }
        }

        if ($submitted_announcement_id && is_numeric($submitted_announcement_id)) {
            // It's an UPDATE operation
            $sql = "UPDATE announcements SET title = ?, content = ?, updated_at = NOW() " . ($has_new_image ? ", image_path = ?" : "") . " WHERE id = ?";
            $stmt = $conn->prepare($sql);
            
            if ($stmt) {
                if ($has_new_image) {
                    $stmt->bind_param("sssi", $title, $content, $image_path, $submitted_announcement_id);
                } else {
                    $stmt->bind_param("ssi", $title, $content, $submitted_announcement_id);
                }

                if ($stmt->execute()) {
                    // NEW: Log the update action
                    $action = 'Announcement Updated';
                    $details = "Admin '{$user_name}' updated announcement '{$title}' (ID: {$submitted_announcement_id}).";
                    log_audit_action($conn, $user_id, $action, $details);

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
            $stmt = $conn->prepare("INSERT INTO announcements (user_id, title, content, image_path) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("isss", $user_id, $title, $content, $image_path);
                if ($stmt->execute()) {
                    // NEW: Log the creation action
                    $new_announcement_id = $conn->insert_id;
                    $action = 'New Announcement Created';
                    $details = "Admin '{$user_name}' published a new announcement '{$title}' (ID: {$new_announcement_id}).";
                    log_audit_action($conn, $user_id, $action, $details);

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
    $message = "Error fetching announcements: " . $conn->error;
    $message_type = 'danger';
}

// Check for and display session messages after all processing
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

<link rel="stylesheet" href="../../assets/css/admin_css/add_announcement.css">

<div class="main-content-wrapper">
    <div class="main-dashboard-content announcement-board-page">
        <div class="announcement-board-header">
            <h1>Campus Announcement Board</h1>
            <a href="../admin/dashboard.php" class="btn btn-secondary btn-back">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($user_role === 'admin'): ?>
        <div class="announcement-section create-post-section">
            <h2><i class="fas fa-bullhorn"></i> <?= ($announcement_id_to_edit) ? 'Edit Announcement' : 'Create New Announcement' ?></h2>
            <form action="announcements.php" method="POST" class="announcement-form" enctype="multipart/form-data">
                <?php if ($announcement_id_to_edit): ?>
                    <input type="hidden" name="announcement_id" value="<?= htmlspecialchars($announcement_id_to_edit) ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="announcement_title">Title:</label>
                    <input type="text" id="announcement_title" name="announcement_title" class="form-control" placeholder="e.g., Important Schedule Change" value="<?= htmlspecialchars($announcement_title_form) ?>" required>
                </div>
                <div class="form-group">
                    <label for="announcement_content">Content:</label>
                    <textarea id="announcement_content" name="announcement_content" class="form-control" rows="8" placeholder="Write your announcement details here..." required><?= htmlspecialchars($announcement_content_form) ?></textarea>
                </div>
                <div class="form-group">
                    <label for="announcement_image">Upload Image (Optional):</label>
                    <input type="file" id="announcement_image" name="announcement_image" class="form-control-file" accept="image/*">
                    <?php if ($current_image_path): ?>
                        <div class="mt-2">
                            <p>Current Image:</p>
                            <img src="../../<?= htmlspecialchars($current_image_path) ?>" alt="Current Announcement Image" style="max-width: 200px; height: auto;">
                        </div>
                    <?php endif; ?>
                </div>
                <button type="submit" name="submit_announcement" class="btn btn-primary"><?= $form_action_text ?></button>
                <?php if ($announcement_id_to_edit): ?>
                    <a href="announcements.php" class="btn btn-secondary">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>
        <?php endif; ?>

        <div class="announcement-section view-posts-section">
            <h2><i class="fas fa-clipboard-list"></i> All Announcements</h2>
            <?php if (!empty($announcements)): ?>
                <div class="announcement-list">
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="announcement-item">
                            <div class="announcement-meta">
                                <span class="badge badge-info"><i class="fas fa-calendar-alt"></i> <?= date('F j, Y, g:i a', strtotime($announcement['published_at'])) ?></span>
                                <span class="badge badge-secondary"><i class="fas fa-user-tie"></i> Posted by: <?= htmlspecialchars($announcement['posted_by_name']) ?></span>
                            </div>
                            <h3><?= htmlspecialchars($announcement['title']) ?></h3>
                            <div class="announcement-content">
                                <?php if (!empty($announcement['image_path'])): ?>
                                    <img src="../../<?= htmlspecialchars($announcement['image_path']) ?>" alt="Announcement Image" class="img-fluid mb-3">
                                <?php endif; ?>
                                <p><?= nl2br(htmlspecialchars($announcement['content'])) ?></p>
                            </div>
                            <?php if ($user_role === 'admin'): ?>
                                <div class="announcement-actions">
                                    <a href="announcements.php?action=edit&id=<?= htmlspecialchars($announcement['id']) ?>" class="btn btn-warning">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="announcements.php?action=delete&id=<?= htmlspecialchars($announcement['id']) ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this announcement? This action cannot be undone.');">
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

<?php require_once '../../templates/footer.php'; ?>