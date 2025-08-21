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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/admin_css/support_center.css"> 

<div class="main-dashboard-content support-center-page">
    <div class="dashboard-header support-center-header">
        <h1><?= htmlspecialchars($page_title) ?></h1>
        <a href="../admin/dashboard.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Back to Admin Dashboard
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="support-content-grid">
        <div class="support-section faqs-section card p-4">
            <h2 class="card-title mb-4"><i class="fas fa-question-circle me-2"></i> View FAQs</h2>
            <?php if (!empty($faqs)): ?>
                <div class="accordion accordion-flush" id="faqAccordion">
                    <?php foreach ($faqs as $index => $faq): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?= $index ?>">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="false" aria-controls="collapse<?= $index ?>">
                                    <?= htmlspecialchars($faq['question']) ?>
                                </button>
                            </h2>
                            <div id="collapse<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $index ?>" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p><?= nl2br(htmlspecialchars($faq['answer'])) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No FAQs available at the moment.</p>
            <?php endif; ?>
        </div>

        <div class="support-section admin-tickets-section card p-4">
            <h2 class="card-title mb-4"><i class="fas fa-reply-all me-2"></i> Manage User Tickets</h2>
            <?php if (!empty($tickets)): ?>
                <div class="accordion accordion-flush" id="ticketAccordion">
                    <?php foreach ($tickets as $ticket): ?>
                        <div class="accordion-item ticket-item ticket-status-<?= htmlspecialchars(str_replace(' ', '-', $ticket['status'])) ?>">
                            <h2 class="accordion-header" id="ticketHeading<?= $ticket['id'] ?>">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ticketCollapse<?= $ticket['id'] ?>" aria-expanded="false" aria-controls="ticketCollapse<?= $ticket['id'] ?>">
                                    Ticket #<?= htmlspecialchars($ticket['id']) ?> (From: <?= htmlspecialchars($ticket['user_name']) ?> - <?= htmlspecialchars($ticket['user_email']) ?>)
                                    <span class="badge ms-3 status-badge status-<?= htmlspecialchars(str_replace(' ', '-', $ticket['status'])) ?>"><?= htmlspecialchars(ucfirst($ticket['status'])) ?></span>
                                </button>
                            </h2>
                            <div id="ticketCollapse<?= $ticket['id'] ?>" class="accordion-collapse collapse" aria-labelledby="ticketHeading<?= $ticket['id'] ?>" data-bs-parent="#ticketAccordion">
                                <div class="accordion-body">
                                    <p><strong>Subject:</strong> <?= htmlspecialchars($ticket['subject']) ?></p>
                                    <p><strong>Submitted:</strong> <?= htmlspecialchars($ticket['created_at']) ?></p>
                                    <p><strong>User Message:</strong><br><?= nl2br(htmlspecialchars($ticket['message'])) ?></p>

                                    <?php if (!empty($ticket['admin_reply'])): ?>
                                        <div class="admin-reply bg-light p-3 mt-3 rounded">
                                            <h6>Admin Reply:</h6>
                                            <p><?= nl2br(htmlspecialchars($ticket['admin_reply'])) ?></p>
                                            <small>Replied: <?= htmlspecialchars($ticket['updated_at']) ?></small>
                                        </div>
                                    <?php endif; ?>

                                    <form action="support_center.php" method="POST" class="admin-reply-form mt-4">
                                        <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($ticket['id']) ?>">
                                        <div class="mb-3">
                                            <label for="admin_reply_content_<?= $ticket['id'] ?>" class="form-label">Admin Reply:</label>
                                            <textarea id="admin_reply_content_<?= $ticket['id'] ?>" name="admin_reply_content" class="form-control" rows="3" placeholder="Enter your reply here..." required><?= htmlspecialchars($ticket['admin_reply'] ?? '') ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="new_status_<?= $ticket['id'] ?>" class="form-label">Update Status:</label>
                                            <select id="new_status_<?= $ticket['id'] ?>" name="new_status" class="form-select">
                                                <option value="open" <?= ($ticket['status'] == 'open') ? 'selected' : '' ?>>Open</option>
                                                <option value="in progress" <?= ($ticket['status'] == 'in progress') ? 'selected' : '' ?>>In Progress</option>
                                                <option value="resolved" <?= ($ticket['status'] == 'resolved') ? 'selected' : '' ?>>Resolved</option>
                                                <option value="closed" <?= ($ticket['status'] == 'closed') ? 'selected' : '' ?>>Closed</option>
                                            </select>
                                        </div>
                                        <button type="submit" name="reply_ticket" class="btn btn-primary btn-sm">Send Reply & Update Status</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No support tickets have been submitted yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once '../../templates/footer.php'; ?>

<script src="../../assets/js/jquery.min.js"></script>

<script src="../../assets/js/script.js"></script>
</body>
</html>