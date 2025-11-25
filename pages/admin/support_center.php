<?php
// CHRONONAV_WEBZD/pages/admin/support_center.php

require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php'; // Assuming functions.php exists for common functions

$user = $_SESSION['user'];
$user_role = $user['role'] ?? 'guest'; // Get user role
$user_id = $user['id'] ?? null;

// --- START DEBUGGING CODE (Optional: Keep for troubleshooting, remove for production) ---
error_log("DEBUG: admin/support_center.php - User session data: " . print_r($user, true));
error_log("DEBUG: admin/support_center.php - User ID being used: " . ($user_id ?? 'NULL'));
error_log("DEBUG: admin/support_center.php - User Role: " . $user_role);
// --- END DEBUGGING CODE ---

// Enforce admin access for this specific page
if ($user_role !== 'admin') { // CORRECTED: Should be 'admin', not 'user'
    $_SESSION['message'] = "Access denied. You do not have permission to access the admin support center.";
    $_SESSION['message_type'] = "danger";
    header("Location: ../user/dashboard.php"); // Redirect non-admins to their dashboard
    exit();
}

$page_title = "Admin Help & Support Center";
$current_page = "manage_support_tickets"; // Assuming "Manage Support Tickets" is the active item in sidenav for admin

$message = '';
$message_type = '';

// --- Handle Ticket Submission (Not typically for admin page, but keeping processing) ---
// This part is mostly for users submitting tickets. Admins primarily manage.
// If an admin needs to submit a ticket for themselves, this block is fine as is.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket'])) {
    $subject = trim($_POST['ticket_subject'] ?? '');
    $message_content = trim($_POST['ticket_message'] ?? '');

    if (empty($subject) || empty($message_content)) {
        $message = "Please fill in all fields for the ticket.";
        $message_type = 'danger';
    } else {
        $stmt = $conn->prepare("INSERT INTO tickets (user_id, subject, message, status) VALUES (?, ?, ?, 'open')");
        if ($stmt) {
            $stmt->bind_param("iss", $user_id, $subject, $message_content);
            if ($stmt->execute()) {
                $message = "Your ticket has been submitted successfully!";
                $message_type = 'success';
                unset($_POST['ticket_subject']);
                unset($_POST['ticket_message']);
            } else {
                $message = "Error submitting your ticket: " . $stmt->error;
                $message_type = 'danger';
            }
            $stmt->close();
        } else {
            $message = "Database error preparing ticket submission: " . $conn->error;
            $message_type = 'danger';
        }
    }
}


// --- Handle Admin Reply (Admin Only) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_ticket'])) {
    // Redundant check due to page-level access control, but harmless
    if ($user_role === 'admin') {
        $ticket_id = $_POST['ticket_id'] ?? null;
        $admin_reply_content = trim($_POST['admin_reply_content'] ?? '');
        $new_status = $_POST['new_status'] ?? 'in progress'; // Default to in progress

        if (empty($ticket_id) || empty($admin_reply_content)) {
            $message = "Admin reply and ticket ID are required.";
            $message_type = 'danger';
        } else {
            // Update the ticket with admin's reply and new status
            $stmt = $conn->prepare("UPDATE tickets SET admin_reply = ?, status = ?, updated_at = NOW() WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("ssi", $admin_reply_content, $new_status, $ticket_id);
                if ($stmt->execute()) {
                    $message = "Reply sent and ticket status updated successfully!";
                    $message_type = 'success';
                } else {
                    $message = "Error sending reply: " . $stmt->error;
                    $message_type = 'danger';
                }
                $stmt->close();
            } else {
                $message = "Database error preparing admin reply: " . $conn->error;
                $message_type = 'danger';
            }
        }
    } else {
        $message = "You are not authorized to reply to tickets.";
        $message_type = 'danger';
    }
}

// --- Fetch FAQs ---
$faqs = [];
$stmt_faq = $conn->prepare("SELECT question, answer FROM faqs ORDER BY id ASC");
if ($stmt_faq) {
    $stmt_faq->execute();
    $result_faq = $stmt_faq->get_result();
    while ($row = $result_faq->fetch_assoc()) {
        $faqs[] = $row;
    }
    $stmt_faq->close();
} else {
    $message = "Error fetching FAQs: " . $conn->error;
    $message_type = 'danger';
}

// --- Fetch Tickets (Admin sees all) ---
$tickets = [];
// Admin sees all tickets regardless of status, ordered by status (open first) then by creation date
$stmt_tickets = $conn->prepare("SELECT t.*, u.name as user_name, u.email as user_email FROM tickets t JOIN users u ON t.user_id = u.id ORDER BY FIELD(t.status, 'open', 'in progress', 'resolved', 'closed'), t.created_at DESC");
if ($stmt_tickets) {
    $stmt_tickets->execute();
    $result_tickets = $stmt_tickets->get_result();
    while ($row = $result_tickets->fetch_assoc()) {
        $tickets[] = $row;
    }
    $stmt_tickets->close();
} else {
    $message = "Error fetching tickets for admin: " . $conn->error;
    $message_type = 'danger';
}

// --- INCLUDE HEADER AND SIDENAV ---
require_once '../../templates/admin/header_admin.php'; // Correct header for admin pages
require_once '../../templates/admin/sidenav_admin.php'; // Correct sidenav for admin pages
?>

<style>
    body {
        font-family: "Space Grotesk", "Noto Sans", sans-serif;
        background-color: #f8fafb;
    }

    .main-dashboard-content-support.support-center-page {
        margin-left: 20%;
        padding: 0px 35px;
    }

    .main-dashboard-content-support-wrapper {
        font-family: "Space Grotesk", "Noto Sans", sans-serif;
        width: 80%;
    }

    .support-center-page {
        padding: 1rem 0;
    }

    .support-center-header {
        border-bottom: 1px solid #e8edf3;
        padding: 0.75rem 0;
        margin-bottom: 2rem;
    }

    .page-title {
        color: #0e151b;
        font-weight: 700;
        letter-spacing: -0.015em;
        margin-bottom: 0;
    }

    .section-title {
        color: #0e151b;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: -0.015em;
        margin-bottom: 1rem;
    }

    .btn-light-sm {
        background-color: #e8edf3;
        border: none;
        color: #0e151b;
        font-weight: 500;
        font-size: 0.875rem;
        height: 32px;
        padding: 0 1rem;
        border-radius: 0.5rem;
    }

    .support-content-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }

    @media (max-width: 1200px) {
        .support-content-grid {
            grid-template-columns: 1fr;
        }
    }

    .support-section {
        background-color: #f8fafb;
        border: 1px solid #d1dce6;
        border-radius: 0.5rem;
        padding: 1.5rem;
    }

    .card-title {
        color: #0e151b;
        font-weight: 700;
        font-size: 1.125rem;
        margin-bottom: 1rem;
    }

    /* Accordion Styles */
    .accordion-item {
        border: 1px solid #d1dce6;
        background-color: #f8fafb;
        border-radius: 0.5rem;
        margin-bottom: 0.75rem;
    }

    .accordion-button {
        background-color: transparent;
        border: none;
        color: #0e151b;
        font-size: 0.875rem;
        font-weight: 500;
        padding: 0.75rem 1rem;
    }

    .accordion-button:not(.collapsed) {
        background-color: transparent;
        color: #0e151b;
        box-shadow: none;
    }

    .accordion-button:focus {
        box-shadow: none;
        border-color: transparent;
    }

    .accordion-body {
        color: #507495;
        font-size: 0.875rem;
        padding: 0 1rem 1rem;
        background-color: #f8fafb;
    }

    .accordion-button::after {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20px' height='20px' fill='%230e151b' viewBox='0 0 256 256'%3E%3Cpath d='M213.66,101.66l-80,80a8,8,0,0,1-11.32,0l-80-80A8,8,0,0,1,53.66,90.34L128,164.69l74.34-74.35a8,8,0,0,1,11.32,11.32Z'%3E%3C/path%3E%3C/svg%3E");
        transform: rotate(0deg);
        transition: transform 0.2s ease-in-out;
    }

    .accordion-button:not(.collapsed)::after {
        transform: rotate(180deg);
    }

    /* Status Badges */
    .status-badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.35em 0.65em;
        border-radius: 50rem;
    }

    .status-open {
        background-color: #e74c3c;
        color: white;
    }

    .status-in-progress {
        background-color: #f39c12;
        color: white;
    }

    .status-resolved {
        background-color: #27ae60;
        color: white;
    }

    .status-closed {
        background-color: #95a5a6;
        color: white;
    }

    /* Form Styles */
    .form-label {
        color: #0e151b;
        font-weight: 500;
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
    }

    .form-control,
    .form-select {
        border: 1px solid #d1dce6;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        background-color: #f8fafb;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #1d7dd7;
        box-shadow: 0 0 0 0.2rem rgba(29, 125, 215, 0.25);
    }

    .btn-primary {
        background-color: #1d7dd7;
        border: none;
        color: #f8fafb;
        font-weight: 500;
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
    }

    .admin-reply {
        background-color: #e8edf3;
        border-radius: 0.5rem;
        padding: 1rem;
        margin: 1rem 0;
    }

    .admin-reply h6 {
        color: #0e151b;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .admin-reply-form {
        background-color: #f8fafb;
        border: 1px solid #d1dce6;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-top: 1rem;
    }

    /* Alert Styles */
    .alert {
        border: none;
        border-radius: 0.5rem;
        padding: 1rem 1.25rem;
        margin: 1rem 0;
        font-weight: 500;
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
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
        .main-dashboard-content-support.support-center-page {
            margin-left: 0 !important;
            padding: 0px 15px !important;
        }

        .main-dashboard-content-support-wrapper {
            width: 100% !important;
        }

        .support-center-page.px-5 {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }

        .page-title.fs-3 {
            font-size: 1.5rem !important;
            text-align: center;
            width: 100%;
            min-width: auto !important;
        }

        .section-title {
            font-size: 1.1rem !important;
            text-align: center;
        }

        .support-content-grid {
            grid-template-columns: 1fr !important;
            gap: 1rem !important;
            margin-bottom: 1rem !important;
        }

        .support-section {
            padding: 1rem !important;
            margin-bottom: 1rem !important;
        }

        .card-title {
            font-size: 1rem !important;
            text-align: center;
        }

        .accordion-button {
            padding: 0.5rem 0.75rem !important;
            font-size: 0.8rem !important;
        }

        .accordion-body {
            padding: 0 0.75rem 0.75rem !important;
            font-size: 0.8rem !important;
        }

        .btn-light-sm {
            width: 100% !important;
            justify-content: center;
            margin-top: 0.5rem;
        }

        .admin-reply-form {
            padding: 0.75rem !important;
        }

        .form-control,
        .form-select {
            font-size: 16px !important;
            /* Prevents zoom on iOS */
        }

        .alert {
            padding: 0.75rem 1rem !important;
            margin: 0.75rem 0 !important;
        }

        .d-flex.flex-wrap.justify-content-between.gap-3.align-items-center {
            flex-direction: column !important;
            text-align: center;
        }
    }

    /* Tablet: 768px to 1023px */
    @media (min-width: 768px) and (max-width: 1023px) {
        .main-dashboard-content-support.support-center-page {
            margin-left: 15% !important;
            padding: 0px 25px !important;
        }

        .main-dashboard-content-support-wrapper {
            width: 85% !important;
        }

        .support-center-page.px-5 {
            padding-left: 1.5rem !important;
            padding-right: 1.5rem !important;
        }

        .page-title.fs-3 {
            font-size: 1.75rem !important;
        }

        .section-title {
            font-size: 1.25rem !important;
        }

        .support-content-grid {
            grid-template-columns: 1fr !important;
            gap: 1.5rem !important;
        }

        .support-section {
            padding: 1.25rem !important;
        }

        .accordion-button {
            padding: 0.625rem 0.875rem !important;
            font-size: 0.85rem !important;
        }

        .accordion-body {
            padding: 0 0.875rem 0.875rem !important;
        }
    }

    /* Desktop: 1024px and above */
    @media (min-width: 1024px) {
        .main-dashboard-content-support.support-center-page {
            margin-left: 20% !important;
            padding: 0px 35px !important;
        }

        .main-dashboard-content-support-wrapper {
            width: 80% !important;
        }

        .support-center-page.px-5 {
            padding-left: 2rem !important;
            padding-right: 2rem !important;
        }

        .page-title.fs-3 {
            font-size: 2rem !important;
        }

        .section-title {
            font-size: 1.375rem !important;
        }

        .support-content-grid {
            grid-template-columns: 1fr 1fr !important;
            gap: 2rem !important;
        }

        .support-section {
            padding: 1.5rem !important;
        }

        .accordion-button {
            padding: 0.75rem 1rem !important;
            font-size: 0.875rem !important;
        }

        .accordion-body {
            padding: 0 1rem 1rem !important;
        }
    }

    /* Responsive sidebar adjustments */
    @media (max-width: 1023px) {
        .sidebar-toggle {
            border-radius: 1px;
        }
    }

    /* Ensure proper spacing on all devices */
    @media (max-width: 767px) {
        .support-center-header.my-3 {
            margin-top: 1rem !important;
            margin-bottom: 1rem !important;
        }

        .mb-3 {
            margin-bottom: 1rem !important;
        }

        .ms-3 {
            margin-left: 0.5rem !important;
        }

        .gap-3 {
            gap: 1rem !important;
        }
    }

    /* Improve modal-like accordion responsiveness */
    @media (max-width: 767px) {
        .accordion-item {
            margin-bottom: 0.5rem !important;
        }

        .status-badge {
            font-size: 0.7rem !important;
            padding: 0.25em 0.5em !important;
        }

        .admin-reply {
            padding: 0.75rem !important;
            margin: 0.75rem 0 !important;
        }

        .admin-reply h6 {
            font-size: 0.9rem !important;
        }

        .btn-primary {
            width: 100% !important;
            justify-content: center;
        }
    }

    /* Better touch targets for mobile */
    @media (max-width: 767px) {
        .accordion-button {
            min-height: 44px;
            display: flex;
            align-items: center;
        }

        .btn {
            min-height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-control,
        .form-select {
            min-height: 44px;
        }
    }

    /* Enhanced accordion layout for mobile */
    @media (max-width: 767px) {
        .accordion-button {
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
        }

        .ticket-item .accordion-button {
            padding-right: 2.5rem !important;
        }

        .status-badge {
            margin-top: 0.25rem;
            align-self: flex-start;
        }
    }

    /* Print styles for support center */
    @media print {
        .main-dashboard-content-support.support-center-page {
            margin-left: 0 !important;
            max-width: 100% !important;
            padding: 0 !important;
        }

        .btn-light-sm,
        .sidebar-toggle,
        .btn-primary {
            display: none !important;
        }

        .accordion-button::after {
            display: none !important;
        }

        .accordion-collapse {
            display: block !important;
        }

        .accordion-item {
            border: 1px solid #000 !important;
            margin-bottom: 1rem !important;
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

        .accordion-item,
        .admin-reply-form {
            transition: background-color 0.2s ease;
        }

        .accordion-item:active,
        .admin-reply-form:active {
            background-color: #e9ecef !important;
        }
    }

    /* Ensure proper text sizing on mobile */
    @media (max-width: 767px) {
        .form-label {
            font-size: 0.8rem !important;
        }

        .admin-reply small {
            font-size: 0.75rem;
        }

        .text-muted {
            font-size: 0.875rem;
            text-align: center;
        }
    }

    /* Improved grid layout for support sections */
    @media (max-width: 767px) {
        .support-content-grid .row {
            flex-direction: column;
        }

        .faqs-section,
        .admin-tickets-section {
            width: 100% !important;
        }
    }

    /* Ticket status badge responsive adjustments */
    @media (max-width: 767px) {
        .ticket-item .accordion-button {
            font-size: 0.75rem !important;
            line-height: 1.3;
        }

        .badge.ms-3.status-badge {
            margin-left: 0.25rem !important;
            margin-top: 0.25rem;
        }
    }
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/admin_css/support_center.css">

<div class="main-dashboard-content-support support-center-page px-5">
    <!-- Header Section -->
    <div class="support-center-header my-3">
        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-center">
            <h1 class="page-title fs-3" style="min-width: 288px;"><?= htmlspecialchars($page_title) ?></h1>
            <a href="../admin/dashboard.php" class="btn-light-sm text-decoration-none">
                <i class="fas fa-arrow-left m-2"></i>Back to Admin Dashboard
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="support-content-grid">
        <!-- FAQs Section -->
        <div class="support-section faqs-section">
            <h2 class="section-title mb-3">? View FAQs</h2>
            <?php if (!empty($faqs)): ?>
                <div class="accordion" id="faqAccordion">
                    <?php foreach ($faqs as $index => $faq): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button <?= $index === 0 ? '' : 'collapsed' ?>" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>"
                                    aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>"
                                    aria-controls="collapse<?= $index ?>">
                                    <?= htmlspecialchars($faq['question']) ?>
                                </button>
                            </h2>
                            <div id="collapse<?= $index ?>"
                                class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>"
                                data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p><?= nl2br(htmlspecialchars($faq['answer'])) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted">No FAQs available at the moment.</p>
            <?php endif; ?>
        </div>

        <!-- Tickets Section -->
        <div class="support-section admin-tickets-section">
            <h2 class="section-title mb-3">♻ Manage User Tickets</h2>
            <?php if (!empty($tickets)): ?>
                <div class="accordion" id="ticketAccordion">
                    <?php foreach ($tickets as $index => $ticket): ?>
                        <div
                            class="accordion-item ticket-item ticket-status-<?= htmlspecialchars(str_replace(' ', '-', $ticket['status'])) ?>">
                            <h2 class="accordion-header">
                                <button class="accordion-button <?= $index === 0 ? '' : 'collapsed' ?>" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#ticketCollapse<?= $ticket['id'] ?>"
                                    aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>"
                                    aria-controls="ticketCollapse<?= $ticket['id'] ?>">
                                    Ticket #<?= htmlspecialchars($ticket['id']) ?> (From:
                                    <?= htmlspecialchars($ticket['user_name']) ?> -
                                    <?= htmlspecialchars($ticket['user_email']) ?>)
                                    <span
                                        class="badge ms-3 status-badge status-<?= htmlspecialchars(str_replace(' ', '-', $ticket['status'])) ?>">
                                        <?= htmlspecialchars(ucfirst($ticket['status'])) ?>
                                    </span>
                                </button>
                            </h2>
                            <div id="ticketCollapse<?= $ticket['id'] ?>"
                                class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>"
                                data-bs-parent="#ticketAccordion">
                                <div class="accordion-body">
                                    <p><strong>Subject:</strong> <?= htmlspecialchars($ticket['subject']) ?></p>
                                    <p><strong>Submitted:</strong> <?= htmlspecialchars($ticket['created_at']) ?></p>
                                    <p><strong>User Message:</strong><br><?= nl2br(htmlspecialchars($ticket['message'])) ?></p>

                                    <?php if (!empty($ticket['admin_reply'])): ?>
                                        <div class="admin-reply">
                                            <h6>Admin Reply:</h6>
                                            <p><?= nl2br(htmlspecialchars($ticket['admin_reply'])) ?></p>
                                            <small>Replied: <?= htmlspecialchars($ticket['updated_at']) ?></small>
                                        </div>
                                    <?php endif; ?>

                                    <form action="support_center.php" method="POST" class="admin-reply-form">
                                        <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($ticket['id']) ?>">
                                        <div class="mb-3">
                                            <label for="admin_reply_content_<?= $ticket['id'] ?>" class="form-label">Admin
                                                Reply:</label>
                                            <textarea id="admin_reply_content_<?= $ticket['id'] ?>" name="admin_reply_content"
                                                class="form-control" rows="3" placeholder="Enter your reply here..."
                                                required><?= htmlspecialchars($ticket['admin_reply'] ?? '') ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="new_status_<?= $ticket['id'] ?>" class="form-label">Update
                                                Status:</label>
                                            <select id="new_status_<?= $ticket['id'] ?>" name="new_status" class="form-select">
                                                <option value="open" <?= ($ticket['status'] == 'open') ? 'selected' : '' ?>>Open
                                                </option>
                                                <option value="in progress" <?= ($ticket['status'] == 'in progress') ? 'selected' : '' ?>>In Progress</option>
                                                <option value="resolved" <?= ($ticket['status'] == 'resolved') ? 'selected' : '' ?>>Resolved</option>
                                                <option value="closed" <?= ($ticket['status'] == 'closed') ? 'selected' : '' ?>>
                                                    Closed</option>
                                            </select>
                                        </div>
                                        <button type="submit" name="reply_ticket" class="btn btn-primary">Send Reply & Update
                                            Status</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted">No support tickets have been submitted yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once '../../templates/footer.php'; ?>

<script src="../../assets/js/jquery.min.js"></script>
<script src="../../assets/js/script.js"></script>
</body>

</html>