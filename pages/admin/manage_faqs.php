<?php
// CHRONONAV_WEBZD/pages/admin/manage_faqs.php

// Start session if it hasn't been started by auth_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';
require_once '../../includes/audit_log.php';

$user = $_SESSION['user'];
$user_role = $user['role'] ?? 'guest';
$user_id = $user['id'] ?? null;

// Enforce admin access for this page
if ($user_role !== 'admin') {
    $_SESSION['message'] = "Access denied. You do not have permission to manage FAQs.";
    $_SESSION['message_type'] = "danger";
    header("Location: ../user/dashboard.php");
    exit();
}

$page_title = "Manage FAQs";
$current_page = "manage_faqs";

$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// --- Handle FAQ Actions (Add, Update, Delete) ---

// Add new FAQ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_faq'])) {
    $question = trim($_POST['question'] ?? '');
    $answer = trim($_POST['answer'] ?? '');

    if (empty($question) || empty($answer)) {
        $_SESSION['message'] = "Question and Answer fields cannot be empty.";
        $_SESSION['message_type'] = 'danger';
    } else {
        $stmt = $conn->prepare("INSERT INTO faqs (question, answer, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
        if ($stmt) {
            $stmt->bind_param("ss", $question, $answer);
            if ($stmt->execute()) {
                $details = "Added new FAQ: '{$question}'";
                log_audit_action($conn, $user_id, 'FAQ Added', $details);
                $_SESSION['message'] = "FAQ added successfully!";
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = "Error adding FAQ: " . $stmt->error;
                $_SESSION['message_type'] = 'danger';
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = "Database error preparing FAQ addition: " . $conn->error;
            $_SESSION['message_type'] = 'danger';
        }
    }
    header("Location: manage_faqs.php");
    exit();
}

// Update existing FAQ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_faq'])) {
    $faq_id = $_POST['faq_id'] ?? null;
    $question = trim($_POST['edit_question'] ?? '');
    $answer = trim($_POST['edit_answer'] ?? '');

    $original_faq = [];
    $stmt_orig = $conn->prepare("SELECT question, answer FROM faqs WHERE id = ?");
    if ($stmt_orig) {
        $stmt_orig->bind_param("i", $faq_id);
        $stmt_orig->execute();
        $result_orig = $stmt_orig->get_result();
        if ($result_orig->num_rows > 0) {
            $original_faq = $result_orig->fetch_assoc();
        }
        $stmt_orig->close();
    }

    if (empty($faq_id) || !is_numeric($faq_id) || empty($question) || empty($answer)) {
        $_SESSION['message'] = "Invalid input or missing fields for updating an FAQ.";
        $_SESSION['message_type'] = 'danger';
    } else {
        $sql = "UPDATE faqs SET question = ?, answer = ?, updated_at = NOW() WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssi", $question, $answer, $faq_id);
            if ($stmt->execute()) {
                $details = "Updated FAQ ID {$faq_id}.";
                $changes = [];
                if ($original_faq['question'] !== $question) {
                    $changes[] = "Question changed from '{$original_faq['question']}' to '{$question}'";
                }
                if ($original_faq['answer'] !== $answer) {
                    $changes[] = "Answer changed from '{$original_faq['answer']}' to '{$answer}'";
                }
                if (!empty($changes)) {
                    $details .= " Changes: " . implode('; ', $changes);
                }

                log_audit_action($conn, $user_id, 'FAQ Updated', $details);
                $_SESSION['message'] = "FAQ updated successfully!";
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = "Error updating FAQ: " . $stmt->error;
                $_SESSION['message_type'] = 'danger';
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = "Database error preparing FAQ update: " . $conn->error;
            $_SESSION['message_type'] = 'danger';
        }
    }
    header("Location: manage_faqs.php");
    exit();
}

// Delete FAQ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_faq'])) {
    $faq_id = $_POST['faq_id'] ?? null;
    $question_to_delete = '';

    $stmt_fetch_q = $conn->prepare("SELECT question FROM faqs WHERE id = ?");
    if ($stmt_fetch_q) {
        $stmt_fetch_q->bind_param("i", $faq_id);
        $stmt_fetch_q->execute();
        $result_q = $stmt_fetch_q->get_result();
        if ($row_q = $result_q->fetch_assoc()) {
            $question_to_delete = $row_q['question'];
        }
        $stmt_fetch_q->close();
    }

    if (empty($faq_id) || !is_numeric($faq_id)) {
        $_SESSION['message'] = "Invalid FAQ ID for deletion.";
        $_SESSION['message_type'] = 'danger';
    } else {
        $stmt = $conn->prepare("DELETE FROM faqs WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $faq_id);
            if ($stmt->execute()) {
                $details = "Deleted FAQ ID {$faq_id}: '{$question_to_delete}'";
                log_audit_action($conn, $user_id, 'FAQ Deleted', $details);
                $_SESSION['message'] = "FAQ deleted successfully!";
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = "Error deleting FAQ: " . $stmt->error;
                $_SESSION['message_type'] = 'danger';
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = "Database error preparing FAQ deletion: " . $conn->error;
            $_SESSION['message_type'] = 'danger';
        }
    }
    header("Location: manage_faqs.php");
    exit();
}

// --- Fetch all FAQs for display ---
$faqs = [];
$stmt_faqs = $conn->prepare("SELECT id, question, answer, created_at, updated_at FROM faqs ORDER BY id ASC");
if ($stmt_faqs) {
    $stmt_faqs->execute();
    $result_faqs = $stmt_faqs->get_result();
    while ($row = $result_faqs->fetch_assoc()) {
        $faqs[] = $row;
    }
    $stmt_faqs->close();
} else {
    $_SESSION['message'] = "Error fetching FAQs: " . $conn->error;
    $_SESSION['message_type'] = 'danger';
}

require_once '../../templates/admin/header_admin.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'ChronoNav - Manage FAQs' ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link rel="stylesheet" as="style" onload="this.rel='stylesheet'"
        href="https://fonts.googleapis.com/css2?display=swap&family=Inter:wght@400;500;700;900&family=Noto+Sans:wght@400;500;700;900">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon"
        href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png">

    <style>
        body {
            font-family: "Space Grotesk", "Noto Sans", sans-serif;
            background-color: #fff;
            min-height: 100vh;
        }

        .layout-container {
            min-height: 100vh;
        }

        .sched.main-content-wrapper {
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

        .btn-secondary {
            background-color: #e8edf3;
            border-color: #e8edf3;
            color: #0e151b;
            font-weight: 600;
            letter-spacing: 0.015em;
            padding: 0.5rem 1rem;
        }

        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #000000;
            font-weight: 600;
            letter-spacing: 0.015em;
            padding: 0.5rem 1rem;
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            color: #f8fafb;
            font-weight: 600;
            letter-spacing: 0.015em;
            padding: 0.5rem 1rem;
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

        .form-control {
            background-color: #f8fafb;
            border-color: #d1dce6;
            color: #0e151b;
            padding: 0.75rem;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #d1dce6;
        }

        .form-label {
            color: #0e151b;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .faq-actions {
            white-space: nowrap;
        }

        .faq-actions .btn {
            margin-right: 0.5rem;
        }

        .faq-actions .btn:last-child {
            margin-right: 0;
        }

        /* Remove Bootstrap's default accordion arrow */
        .accordion-button::after {
            display: none !important;
        }

        .accordion-button {
            background-color: #f8fafb;
            color: #0e151b;
            font-weight: 500;
            border: none;
            padding: 1rem 1.25rem;
        }

        .accordion-button:not(.collapsed) {
            background-color: #f8fafb;
            color: #0e151b;
            box-shadow: none;
        }

        .accordion-button:focus {
            box-shadow: none;
            border-color: #d1dce6;
        }

        .accordion-body {
            background-color: #ffffff;
            border-top: 1px solid #d1dce6;
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
            .sched.main-content-wrapper {
                margin-left: 0;
            }

            .main-dashboard-content {
                padding: 1rem;
            }

            .faq-actions {
                white-space: normal;
            }

            .faq-actions .btn {
                margin-bottom: 0.5rem;
                display: block;
                width: 100%;
            }

            .manage-faqs-header {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>

<body>
    <?php require_once '../../templates/admin/sidenav_admin.php'; ?>

    <div class="sched main-content-wrapper">
        <div class="main-dashboard-content p-4">
            <div class="dashboard-header d-flex justify-content-between align-items-center">
                <h2 class="fs-3 fw-bold"><?= $page_title ?></h2>
                <a href="../admin/support_center.php" class="btn btn-secondary d-flex align-items-center gap-2 fs-6">
                    <i class="fas fa-arrow-left"></i> Back to Support Center
                </a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Add New FAQ Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0 d-flex align-items-center gap-2">
                        <i class="fas fa-plus-circle"></i> Add New FAQ
                    </h5>
                </div>
                <div class="card-body">
                    <form action="manage_faqs.php" method="POST">
                        <div class="mb-3">
                            <label for="question" class="form-label">Question:</label>
                            <input type="text" id="question" name="question" class="form-control"
                                value="<?= htmlspecialchars($_POST['question'] ?? '') ?>"
                                placeholder="Enter FAQ question" required>
                        </div>
                        <div class="mb-3">
                            <label for="answer" class="form-label">Answer:</label>
                            <textarea id="answer" name="answer" class="form-control" rows="5"
                                placeholder="Enter FAQ answer"
                                required><?= htmlspecialchars($_POST['answer'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" name="add_faq" class="btn btn-primary d-flex align-items-center gap-2">
                            <i class="fas fa-plus"></i> Add FAQ
                        </button>
                    </form>
                </div>
            </div>

            <!-- Existing FAQs Section -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0 d-flex align-items-center gap-2">
                        <i class="fas fa-list-alt"></i> Existing FAQs
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($faqs)): ?>
                        <div class="accordion" id="faqAccordion">
                            <?php foreach ($faqs as $faq): ?>
                                <div class="accordion-item border-0 mb-2">
                                    <div class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse<?= $faq['id'] ?>" aria-expanded="false"
                                            aria-controls="collapse<?= $faq['id'] ?>">
                                            <div class="d-flex justify-content-between align-items-center w-100">
                                                <?= htmlspecialchars($faq['question']) ?>
                                                <i class="fas fa-chevron-down"></i>
                                            </div>
                                        </button>
                                    </div>
                                    <div id="collapse<?= $faq['id'] ?>" class="accordion-collapse collapse"
                                        data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            <p><?= nl2br(htmlspecialchars($faq['answer'])) ?></p>
                                            <small class="text-muted">Created:
                                                <?= htmlspecialchars($faq['created_at']) ?></small><br>
                                            <?php if (!empty($faq['updated_at']) && (strtotime($faq['updated_at']) > strtotime($faq['created_at']) || (isset($faq['created_at']) && $faq['created_at'] === $faq['updated_at']))): ?>
                                                <small class="text-muted">Last Updated:
                                                    <?= htmlspecialchars($faq['updated_at']) ?></small>
                                            <?php endif; ?>

                                            <div class="faq-actions mt-3">
                                                <button type="button" class="btn btn-sm btn-warning btn-edit"
                                                    data-bs-toggle="modal" data-bs-target="#editFaqModal"
                                                    data-id="<?= htmlspecialchars($faq['id']) ?>"
                                                    data-question="<?= htmlspecialchars($faq['question']) ?>"
                                                    data-answer="<?= htmlspecialchars($faq['answer']) ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <form action="manage_faqs.php" method="POST" class="d-inline-block"
                                                    onsubmit="return confirm('Are you sure you want to delete this FAQ?');">
                                                    <input type="hidden" name="faq_id"
                                                        value="<?= htmlspecialchars($faq['id']) ?>">
                                                    <button type="submit" name="delete_faq" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash-alt"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">No FAQs have been added yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit FAQ Modal -->
    <div class="modal fade" id="editFaqModal" tabindex="-1" aria-labelledby="editFaqModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editFaqModalLabel">Edit FAQ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="manage_faqs.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="faq_id" id="edit_faq_id">
                        <div class="mb-3">
                            <label for="edit_question" class="form-label">Question:</label>
                            <input type="text" class="form-control" id="edit_question" name="edit_question" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_answer" class="form-label">Answer:</label>
                            <textarea class="form-control" id="edit_answer" name="edit_answer" rows="5"
                                required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_faq" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // FAQ Accordion functionality
        document.addEventListener('DOMContentLoaded', function () {
            const faqAccordion = document.getElementById('faqAccordion');

            if (faqAccordion) {
                // Handle accordion show event
                faqAccordion.addEventListener('show.bs.collapse', function (event) {
                    const targetId = event.target.id;
                    const button = document.querySelector(`[data-bs-target="#${targetId}"]`);
                    const icon = button.querySelector('i');
                    if (icon) {
                        icon.classList.remove('fa-chevron-down');
                        icon.classList.add('fa-chevron-up');
                    }
                });

                // Handle accordion hide event
                faqAccordion.addEventListener('hide.bs.collapse', function (event) {
                    const targetId = event.target.id;
                    const button = document.querySelector(`[data-bs-target="#${targetId}"]`);
                    const icon = button.querySelector('i');
                    if (icon) {
                        icon.classList.remove('fa-chevron-up');
                        icon.classList.add('fa-chevron-down');
                    }
                });
            }

            // Populate edit modal with FAQ data
            var editFaqModal = document.getElementById('editFaqModal');
            editFaqModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var faqId = button.getAttribute('data-id');
                var faqQuestion = button.getAttribute('data-question');
                var faqAnswer = button.getAttribute('data-answer');

                var modalIdInput = editFaqModal.querySelector('#edit_faq_id');
                var modalQuestionInput = editFaqModal.querySelector('#edit_question');
                var modalAnswerInput = editFaqModal.querySelector('#edit_answer');

                modalIdInput.value = faqId;
                modalQuestionInput.value = faqQuestion;
                modalAnswerInput.value = faqAnswer;
            });
        });
    </script>

    <?php require_once '../../templates/footer.php'; ?>
</body>

</html>