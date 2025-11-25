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
    $announcement_id = (int) $_GET['id'];
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
    $announcement_id_to_edit = (int) $_GET['id'];
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

<!-- Font Family -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
<!-- important------------------------------------------------------------------------------------------------ -->

<!-- Favicon -->
<link rel="icon" type="image/x-icon"
    href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png">
</style>

<style>
    /* Enhanced Announcements Styles */
    body {
        font-family: "Space Grotesk", "Noto Sans", sans-serif;
        background-color: #f8fafb;
        min-height: 100vh;
    }

    .main-content-wrapper {
        background-color: #f8fafb;
        min-height: 100vh;
    }

    .main-dashboard-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1.5rem;
    }

    .announcement-board-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #e8edf3;
    }

    .announcement-board-header h1 {
        color: #0e151b;
        font-size: 1.8rem;
        font-weight: 700;
        letter-spacing: -0.015em;
        margin: 0;
    }

    /* Alert Styling */
    .alert {
        border-radius: 0.5rem;
        border: none;
        padding: 1rem 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .alert-success {
        background-color: #e8f5e9;
        color: #078838;
        border-left: 4px solid #078838;
    }

    .alert-danger {
        background-color: #ffebee;
        color: #e73908;
        border-left: 4px solid #e73908;
    }

    /* Section Styling */
    .announcement-section {
        border-radius: 0.75rem;
        padding: 2rem;
        margin-bottom: 2.5rem;
        transition: box-shadow 0.2s ease;
    }

    .announcement-section:hover {
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.08);
    }

    .announcement-section h2 {
        color: #0e151b;
        font-size: 1.5rem;
        font-weight: 700;
        letter-spacing: -0.015em;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #e8edf3;
    }

    .announcement-section h2 i {
        margin-right: 0.75rem;
        color: #507495;
        font-size: 1.25rem;
    }

    /* Form Styling */
    .form-group {
        margin-bottom: 1.75rem;
    }

    .form-group label {
        color: #0e151b;
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
        display: block;
    }

    .form-control {
        background-color: #f8fafb;
        border: 1px solid #d1dce6;
        color: #0e151b;
        height: 3.5rem;
        padding: 1rem 1.25rem;
        border-radius: 0.5rem;
        font-size: 1rem;
        transition: all 0.2s ease;
        width: 50%;
    }

    .form-control:focus {
        box-shadow: 0 0 0 3px rgba(80, 116, 149, 0.15);
        border-color: #507495;
        background-color: #fff;
    }

    .form-control::placeholder {
        color: #8fa3b8;
        font-weight: 400;
    }

    textarea.form-control {
        min-height: 12rem;
        resize: vertical;
        line-height: 1.6;
    }

    .form-control-file {
        padding: 1rem;
        border: 2px dashed #d1dce6;
        border-radius: 0.5rem;
        background-color: #f8fafb;
        transition: all 0.2s ease;
        width: 100%;
    }

    .form-control-file:hover {
        border-color: #507495;
        background-color: #e8edf3;
    }

    /* Button Styling */
    .btn {
        border-radius: 0.5rem;
        font-weight: 600;
        font-size: 0.875rem;
        padding: 0.75rem 1.5rem;
        border: none;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-secondary {
        background-color: #e8edf3;
        color: #0e151b;
        font-weight: 600;
        text-decoration: none;
    }

    .btn-secondary:hover {
        background-color: #d1dce6;
        color: #0e151b;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .btn-primary {
        background-color: #1d7dd7;
        color: #fff;
        font-weight: 600;
    }

    .btn-primary:hover {
        background-color: #1669b8;
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(29, 125, 215, 0.3);
    }

    .btn-warning {
        background-color: #ffc107;
        color: #0e151b;
        font-weight: 600;
        text-decoration: none;
    }

    .btn-warning:hover {
        background-color: #e0a800;
        color: #0e151b;
        transform: translateY(-1px);
    }

    .btn-danger {
        background-color: #dc3545;
        color: #fff;
        font-weight: 600;
        text-decoration: none;
    }

    .btn-danger:hover {
        background-color: #c82333;
        color: #fff;
        transform: translateY(-1px);
    }

    /* Announcement Item Styling */
    .announcement-item {
        border-radius: 0.75rem;
        padding: 2rem;
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .announcement-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(135deg, #1d7dd7, #507495);
    }

    .announcement-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .announcement-meta {
        margin-bottom: 1rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .badge {
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
    }

    .badge-info {
        background-color: #e3f2fd;
        color: #1976d2;
        border: 1px solid #bbdefb;
    }

    .badge-secondary {
        background-color: #e8edf3;
        color: #507495;
        border: 1px solid #d1dce6;
    }

    .announcement-item h3 {
        color: #0e151b;
        font-size: 1.375rem;
        font-weight: 700;
        margin-bottom: 1rem;
        line-height: 1.4;
    }

    .announcement-content {
        color: #2d3748;
        line-height: 1.7;
        margin-bottom: 1.5rem;
    }

    .announcement-content img {
        border-radius: 0.5rem;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        margin-bottom: 1rem;
        max-width: 100%;
        height: auto;
    }

    .announcement-actions {
        display: flex;
        gap: 0.75rem;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e8edf3;
    }

    /* Image Preview */
    .mt-2 img {
        border-radius: 0.5rem;
        border: 2px solid #e8edf3;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: transform 0.2s ease;
    }

    .mt-2 img:hover {
        transform: scale(1.02);
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

    /* Add these media queries at the end of your existing CSS - ENHANCED MOBILE & TABLET RESPONSIVENESS */

    /* Mobile: 767px and below - ENHANCED */
    @media (max-width: 767px) {
        .main-dashboard-content {
            padding: 0.75rem !important;
            margin-left: 0 !important;
            max-width: 100% !important;
        }

        .announcement-board-header {
            flex-direction: column !important;
            align-items: center !important;
            gap: 1rem !important;
            margin-bottom: 1rem !important;
            padding: 1rem 0 !important;
            text-align: center;
        }

        .announcement-board-header h1.fs-3 {
            font-size: 1.4rem !important;
            width: 100%;
            min-width: auto !important;
            margin-bottom: 0.5rem;
        }

        .btn-back {
            width: 100% !important;
            max-width: 280px;
            justify-content: center;
        }

        .announcement-section {
            padding: 1rem !important;
            margin-bottom: 1.25rem !important;
            border-radius: 0.5rem !important;
        }

        .announcement-section h2 {
            font-size: 1.1rem !important;
            text-align: center;
            flex-direction: column;
            gap: 0.5rem;
            padding-bottom: 0.5rem !important;
            margin-bottom: 1rem !important;
        }

        .announcement-section h2 i {
            font-size: 1.1rem !important;
            margin-right: 0 !important;
        }

        /* Form enhancements for mobile */
        .form-group {
            margin-bottom: 1.25rem !important;
        }

        .form-group label {
            font-size: 0.9rem !important;
            text-align: left;
            margin-bottom: 0.5rem !important;
            font-weight: 600;
        }

        .form-control {
            width: 100% !important;
            height: auto !important;
            padding: 0.875rem !important;
            font-size: 16px !important;
            /* Prevent zoom on iOS */
            border-radius: 0.375rem !important;
        }

        textarea.form-control {
            min-height: 150px !important;
            line-height: 1.5;
        }

        .form-control-file {
            padding: 0.875rem !important;
            font-size: 14px;
        }

        /* Button enhancements */
        .d-flex.gap-3.flex-wrap {
            flex-direction: column !important;
            gap: 0.75rem !important;
            width: 100%;
        }

        .btn {
            width: 100% !important;
            justify-content: center;
            margin-bottom: 0 !important;
            padding: 0.875rem 1rem !important;
            font-size: 0.9rem !important;
        }

        /* Announcement items mobile optimization */
        .announcement-item {
            padding: 1.25rem 1rem !important;
            margin-bottom: 1rem !important;
            border-radius: 0.5rem !important;
        }

        .announcement-item::before {
            width: 3px !important;
        }

        .announcement-item h3 {
            font-size: 1.1rem !important;
            text-align: left;
            line-height: 1.4;
            margin-bottom: 0.875rem !important;
        }

        .announcement-meta {
            flex-direction: column !important;
            gap: 0.5rem !important;
            text-align: left;
            margin-bottom: 0.875rem !important;
        }

        .badge {
            padding: 0.5rem 0.75rem !important;
            font-size: 0.75rem !important;
            justify-content: flex-start;
            width: fit-content;
        }

        .announcement-content {
            font-size: 0.9rem !important;
            line-height: 1.6;
        }

        .announcement-content img {
            max-width: 100% !important;
            height: auto !important;
            margin-bottom: 1rem !important;
            border-radius: 0.375rem !important;
        }

        .announcement-actions {
            flex-direction: column !important;
            gap: 0.5rem !important;
            padding-top: 1rem !important;
            margin-top: 1rem !important;
        }

        .announcement-actions .btn {
            width: 100% !important;
            justify-content: center;
        }

        /* Alert mobile optimization */
        .alert {
            padding: 0.875rem !important;
            margin-bottom: 1rem !important;
            border-radius: 0.375rem !important;
            font-size: 0.9rem;
        }

        .alert .d-flex {
            align-items: flex-start;
        }

        /* Image preview mobile */
        .mt-2 img,
        .img-thumbnail {
            max-width: 100% !important;
            height: auto !important;
            display: block;
            margin: 0.5rem auto !important;
        }

        /* Empty state mobile */
        .text-center.py-5 {
            padding: 1.5rem 1rem !important;
        }

        .text-center.py-5 .fas.fa-bullhorn {
            font-size: 2.5rem !important;
            margin-bottom: 1rem;
        }

        .text-muted.fs-5 {
            font-size: 1rem !important;
            line-height: 1.4;
        }
    }

    /* Tablet: 768px to 1023px - ENHANCED */
    @media (min-width: 768px) and (max-width: 1023px) {
        .main-dashboard-content {
            padding: 1.25rem !important;
            margin-left: 15% !important;
            max-width: 85% !important;
        }

        .announcement-board-header {
            flex-direction: row !important;
            align-items: center !important;
            gap: 1.5rem !important;
            margin-bottom: 1.5rem !important;
            padding-bottom: 1.25rem !important;
        }

        .announcement-board-header h1.fs-3 {
            font-size: 1.6rem !important;
            flex: 1;
        }

        .btn-back {
            flex-shrink: 0;
            min-width: 160px;
        }

        .announcement-section {
            padding: 1.5rem !important;
            margin-bottom: 1.75rem !important;
            border-radius: 0.625rem !important;
        }

        .announcement-section h2 {
            font-size: 1.3rem !important;
            margin-bottom: 1.25rem !important;
        }

        /* Form tablet optimization */
        .form-group {
            margin-bottom: 1.5rem !important;
        }

        .form-control {
            width: 80% !important;
            padding: 0.875rem 1rem !important;
        }

        textarea.form-control {
            min-height: 180px !important;
        }

        /* Button tablet optimization */
        .d-flex.gap-3.flex-wrap {
            flex-direction: row !important;
            gap: 1rem !important;
        }

        .btn {
            flex: 1;
            min-width: 140px;
            max-width: 200px;
        }

        /* Announcement items tablet optimization */
        .announcement-item {
            padding: 1.5rem !important;
            margin-bottom: 1.25rem !important;
        }

        .announcement-item h3 {
            font-size: 1.2rem !important;
        }

        .announcement-meta {
            flex-direction: row !important;
            gap: 0.75rem !important;
            flex-wrap: wrap;
        }

        .badge {
            font-size: 0.8rem !important;
        }

        .announcement-content {
            font-size: 0.95rem !important;
        }

        .announcement-actions {
            flex-direction: row !important;
            gap: 0.75rem !important;
            flex-wrap: wrap;
        }

        .announcement-actions .btn {
            flex: 1;
            min-width: 120px;
            max-width: 150px;
        }

        /* Image tablet optimization */
        .announcement-content img {
            max-width: 80% !important;
            margin: 0 auto 1rem !important;
            display: block;
        }

        .img-thumbnail {
            max-width: 180px !important;
        }
    }

    /* Desktop: 1024px and above - Refined */
    @media (min-width: 1024px) {
        .main-dashboard-content {
            padding: 2rem 1.5rem !important;
            margin-left: 20% !important;
            max-width: 1200px !important;
        }

        .announcement-board-header h1.fs-3 {
            font-size: 1.8rem !important;
        }

        .announcement-section {
            padding: 2rem !important;
            margin-bottom: 2.5rem !important;
        }

        .announcement-section h2 {
            font-size: 1.5rem !important;
        }

        .form-control {
            width: 60% !important;
        }

        .announcement-item {
            padding: 2rem !important;
        }

        .announcement-item h3 {
            font-size: 1.375rem !important;
        }

        .announcement-actions {
            flex-direction: row !important;
            gap: 0.75rem !important;
        }

        .announcement-actions .btn {
            width: auto !important;
            flex: none;
        }
    }

    /* Enhanced responsive sidebar adjustments */
    @media (max-width: 1023px) {
        .sidebar-toggle {
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 1.1rem;
        }
    }

    /* Improved touch targets for mobile */
    @media (max-width: 767px) {
        .btn {
            min-height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-control {
            min-height: 48px;
        }

        textarea.form-control {
            min-height: 160px;
        }

        .announcement-item {
            min-height: auto;
            cursor: pointer;
        }

        /* Better touch feedback */
        .btn:active,
        .announcement-item:active {
            transform: scale(0.98);
            transition: transform 0.1s ease;
        }
    }

    /* Enhanced form responsiveness */
    @media (max-width: 767px) {
        .form-control-file {
            font-size: 14px;
        }

        .form-control::placeholder {
            font-size: 14px;
        }

        textarea.form-control::placeholder {
            font-size: 14px;
        }
    }

    /* Print styles for announcements */
    @media print {
        .main-dashboard-content {
            margin-left: 0 !important;
            max-width: 100% !important;
            padding: 0 !important;
        }

        .btn-secondary,
        .btn-primary,
        .btn-warning,
        .btn-danger,
        .sidebar-toggle,
        .form-control-file {
            display: none !important;
        }

        .announcement-item {
            break-inside: avoid;
            border: 1px solid #000 !important;
            margin-bottom: 1rem !important;
        }

        .announcement-item::before {
            display: none !important;
        }

        .announcement-actions {
            display: none !important;
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
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 1.1rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    @media (max-width: 1023px) {
        .sidebar-toggle {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }
    }

    /* Enhanced mobile typography */
    @media (max-width: 767px) {
        body {
            font-size: 14px;
        }

        .form-label {
            font-size: 0.9rem !important;
        }

        .text-muted {
            font-size: 0.85rem;
        }

        .badge {
            font-size: 0.75rem !important;
        }
    }

    /* Improved image handling */
    @media (max-width: 767px) {
        .announcement-content img {
            border-radius: 0.375rem !important;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .img-thumbnail {
            border-radius: 0.375rem !important;
            border: 1px solid #e8edf3;
        }
    }

    /* Enhanced loading states */
    @media (max-width: 767px) {
        .announcement-item {
            transition: all 0.3s ease;
        }

        .announcement-item:active {
            background-color: #f8f9fa;
        }
    }

    /* Better spacing for mobile forms */
    @media (max-width: 767px) {
        .announcement-form {
            gap: 1rem;
        }

        .form-group:last-child {
            margin-bottom: 0 !important;
        }
    }

    /* Tablet-specific form improvements */
    @media (min-width: 768px) and (max-width: 1023px) {
        .announcement-form {
            max-width: 100%;
        }

        .form-control {
            max-width: 500px;
        }
    }
</style>

<div class="main-content-wrapper">
    <div class="main-dashboard-content announcement-board-page">
        <div class="announcement-board-header">
            <h1 class="fs-3">Campus Announcement Board</h1>
            <a href="../admin/dashboard.php" class="btn btn-secondary btn-back">
                <i class="fas fa-arrow-left me-2"></i> Back to Home
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i
                        class="fas <?= $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> me-2"></i>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($user_role === 'admin'): ?>
            <div class="announcement-section create-post-section">
                <h2><i class="fas fa-bullhorn"></i>
                    <?= ($announcement_id_to_edit) ? 'Edit Announcement' : 'Create New Announcement' ?></h2>
                <form action="announcements.php" method="POST" class="announcement-form" enctype="multipart/form-data">
                    <?php if ($announcement_id_to_edit): ?>
                        <input type="hidden" name="announcement_id" value="<?= htmlspecialchars($announcement_id_to_edit) ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="announcement_title" class="form-label">Title</label>
                        <input type="text" id="announcement_title" name="announcement_title" class="form-control"
                            placeholder="e.g., Important Schedule Change"
                            value="<?= htmlspecialchars($announcement_title_form) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="announcement_content" class="form-label">Content</label>
                        <textarea id="announcement_content" name="announcement_content" class="form-control" rows="8"
                            placeholder="Write your announcement details here..."
                            required><?= htmlspecialchars($announcement_content_form) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="announcement_image" class="form-label">Upload Image (Optional)</label>
                        <input type="file" id="announcement_image" name="announcement_image" class="form-control-file"
                            accept="image/*">
                        <?php if ($current_image_path): ?>
                            <div class="mt-3">
                                <p class="text-muted mb-2">Current Image:</p>
                                <img src="../../<?= htmlspecialchars($current_image_path) ?>" alt="Current Announcement Image"
                                    class="img-thumbnail" style="max-width: 200px; height: auto;">
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex gap-3 flex-wrap">
                        <button type="submit" name="submit_announcement" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i><?= $form_action_text ?>
                        </button>
                        <?php if ($announcement_id_to_edit): ?>
                            <a href="announcements.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel Edit
                            </a>
                        <?php endif; ?>
                    </div>
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
                                <span class="badge badge-info">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?= date('F j, Y, g:i a', strtotime($announcement['published_at'])) ?>
                                </span>
                                <span class="badge badge-secondary">
                                    <i class="fas fa-user-tie"></i>
                                    Posted by: <?= htmlspecialchars($announcement['posted_by_name']) ?>
                                </span>
                            </div>
                            <h3><?= htmlspecialchars($announcement['title']) ?></h3>
                            <div class="announcement-content">
                                <?php if (!empty($announcement['image_path'])): ?>
                                    <img src="../../<?= htmlspecialchars($announcement['image_path']) ?>" alt="Announcement Image"
                                        class="img-fluid mb-4 rounded shadow-sm">
                                <?php endif; ?>
                                <p class="mb-0"><?= nl2br(htmlspecialchars($announcement['content'])) ?></p>
                            </div>
                            <?php if ($user_role === 'admin'): ?>
                                <div class="announcement-actions">
                                    <a href="announcements.php?action=edit&id=<?= htmlspecialchars($announcement['id']) ?>"
                                        class="btn btn-warning">
                                        <i class="fas fa-edit me-2"></i> Edit
                                    </a>
                                    <a href="announcements.php?action=delete&id=<?= htmlspecialchars($announcement['id']) ?>"
                                        class="btn btn-danger"
                                        onclick="return confirm('Are you sure you want to delete this announcement? This action cannot be undone.');">
                                        <i class="fas fa-trash-alt me-2"></i> Delete
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-bullhorn text-muted mb-3" style="font-size: 3rem;"></i>
                    <p class="text-muted fs-5">No announcements available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>