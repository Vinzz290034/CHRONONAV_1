<?php
// CHRONONAV_WEBZD/pages/user/support_center.php

require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php'; // Assuming functions.php exists for common functions

// Enforce user access for this page
requireRole(['user']); // Only 'user' role is allowed here. If 'admin' also needs access, adjust requireRole in functions.php or here.

$user = $_SESSION['user'];
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
        error_log("Security Alert: User ID {$user_id} in session not found in database for support_center (user).");
        session_destroy();
        header('Location: ../../auth/login.php?error=user_not_found');
        exit();
    }
    $stmt_user_data->close();
} else {
    error_log("Database query preparation failed for support_center (user): " . $conn->error);
    // Optionally redirect or show a user-friendly error
}

// Prepare variables for header display
$display_username = htmlspecialchars($user['name'] ?? 'Guest');
$display_user_role = htmlspecialchars(ucfirst($user['role'] ?? 'User'));

// Determine the correct profile image source path for the header
$display_profile_img = htmlspecialchars($user['profile_img'] ?? 'uploads/profiles/default-avatar.png');
$profile_img_src = (strpos($display_profile_img, 'uploads/') === 0) ? '../../' . $display_profile_img : $display_profile_img;


$page_title = "Help & Support Center";
$current_page = "feedback"; // Or whatever sidebar item corresponds to user support

$message = '';
$message_type = '';

// Initialize variables for form (in case of validation errors)
$subject = '';
$message_content = '';


// --- Handle Ticket Submission (User Submission) ---
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
                $message = "Your ticket has been submitted successfully! We will get back to you shortly.";
                $message_type = 'success';
                // Clear form fields on successful submission
                $subject = '';
                $message_content = '';
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

// --- Fetch User's FAQs ---
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
    // Error handling for FAQs fetch (consider logging this, but not crucial to show user)
    error_log("Error fetching FAQs: " . $conn->error);
}


// --- Fetch User's Tickets ---
$user_tickets = [];
if ($user_id) {
    $stmt_user_tickets = $conn->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC");
    if ($stmt_user_tickets) {
        $stmt_user_tickets->bind_param("i", $user_id);
        $stmt_user_tickets->execute();
        $result_user_tickets = $stmt_user_tickets->get_result();
        while ($row = $result_user_tickets->fetch_assoc()) {
            $user_tickets[] = $row;
        }
        $stmt_user_tickets->close();
    } else {
        $message = "Error fetching your tickets: " . $conn->error;
        $message_type = 'danger';
    }
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

<link rel="stylesheet" href="../../assets/css/user_css/user_support_center.css">


<div class="main-dashboard-content user-support-center-page">
    <div class="dashboard-header">
        <h1><?= htmlspecialchars($page_title) ?></h1>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="user-support-container">
        <div class="support-section card p-4 mb-4">
            <h2 class="card-title mb-4"><i class="fas fa-edit me-2"></i> Submit a New Support Ticket</h2>
            <form action="support_center.php" method="POST">
                <div class="mb-3">
                    <label for="ticket_subject" class="form-label">Subject</label>
                    <input type="text" class="form-control" id="ticket_subject" name="ticket_subject" value="<?= htmlspecialchars($subject) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="ticket_message" class="form-label">Your Message</label>
                    <textarea class="form-control" id="ticket_message" name="ticket_message" rows="5" placeholder="Describe your issue or question here..." required><?= htmlspecialchars($message_content) ?></textarea>
                </div>
                <button type="submit" name="submit_ticket" class="btn btn-primary mt-3">Submit Ticket</button>
            </form>
        </div>

        <div class="support-section card p-4 mb-4">
            <h2 class="card-title mb-4"><i class="fas fa-history me-2"></i> Your Support Tickets History</h2>
            <?php if (!empty($user_tickets)): ?>
                <div class="accordion accordion-flush" id="userTicketAccordion">
                    <?php foreach ($user_tickets as $ticket): ?>
                        <div class="accordion-item ticket-item ticket-status-<?= htmlspecialchars(str_replace(' ', '-', $ticket['status'])) ?>">
                            <h2 class="accordion-header" id="userTicketHeading<?= $ticket['id'] ?>">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#userTicketCollapse<?= $ticket['id'] ?>" aria-expanded="false" aria-controls="userTicketCollapse<?= $ticket['id'] ?>">
                                    Ticket #<?= htmlspecialchars($ticket['id']) ?>: <?= htmlspecialchars($ticket['subject']) ?>
                                    <span class="badge ms-3 status-badge status-<?= htmlspecialchars(str_replace(' ', '-', $ticket['status'])) ?>"><?= htmlspecialchars(ucfirst($ticket['status'])) ?></span>
                                </button>
                            </h2>
                            <div id="userTicketCollapse<?= $ticket['id'] ?>" class="accordion-collapse collapse" aria-labelledby="userTicketHeading<?= $ticket['id'] ?>" data-bs-parent="#userTicketAccordion">
                                <div class="accordion-body">
                                    <p><strong>Submitted:</strong> <?= htmlspecialchars($ticket['created_at']) ?></p>
                                    <p><strong>Your Message:</strong><br><?= nl2br(htmlspecialchars($ticket['message'])) ?></p>

                                    <?php if (!empty($ticket['admin_reply'])): ?>
                                        <div class="admin-reply bg-light p-3 mt-3 rounded">
                                            <h6>Admin Reply:</h6>
                                            <p><?= nl2br(htmlspecialchars($ticket['admin_reply'])) ?></p>
                                            <small>Replied: <?= htmlspecialchars($ticket['updated_at']) ?></small>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted mt-3">No admin reply yet.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>You have not submitted any support tickets yet.</p>
            <?php endif; ?>
        </div>

        <div class="support-section faqs-section card p-4">
            <h2 class="card-title mb-4"><i class="fas fa-question-circle me-2"></i> Frequently Asked Questions</h2>
            <?php if (!empty($faqs)): ?>
                <div class="accordion accordion-flush" id="userFaqAccordion">
                    <?php foreach ($faqs as $index => $faq): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="userFaqHeading<?= $index ?>">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#userFaqCollapse<?= $index ?>" aria-expanded="false" aria-controls="userFaqCollapse<?= $index ?>">
                                    <?= htmlspecialchars($faq['question']) ?>
                                </button>
                            </h2>
                            <div id="userFaqCollapse<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="userFaqHeading<?= $index ?>" data-bs-parent="#userFaqAccordion">
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
    </div>
</div>

<?php
// Include the footer
include_once '../../templates/footer.php';
?>