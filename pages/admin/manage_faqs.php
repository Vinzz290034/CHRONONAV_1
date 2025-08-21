<?php
// CHRONONAV_WEBZD/pages/admin/manage_faqs.php

// Start session if it hasn't been started by auth_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php'; // Corrected file path
require_once '../../includes/audit_log.php'; // NEW: Include audit log file

$user = $_SESSION['user'];
$user_role = $user['role'] ?? 'guest';
$user_id = $user['id'] ?? null;

// Enforce admin access for this page
if ($user_role !== 'admin') {
    $_SESSION['message'] = "Access denied. You do not have permission to manage FAQs.";
    $_SESSION['message_type'] = "danger";
    header("Location: ../user/dashboard.php"); // Redirect non-admins
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
                // Log the action
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
    
    // Fetch the original FAQ details for logging purposes
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
                 // Log the action with details of the change
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

    // Fetch the question to be deleted for the audit log
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
                // Log the action
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
require_once '../../templates/admin/sidenav_admin.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<link rel="stylesheet" href="../../assets/css/admin_css/manage_faqs.css">

<div class="main-content-wrapper">
    <div class="main-dashboard-content manage-faqs-page">
        <div class="manage-faqs-header">
            <h1><?= htmlspecialchars($page_title) ?></h1>
            <a href="../admin/support_center.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Support Center
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="faq-form-section card p-4 mb-4 shadow-sm">
            <h2><i class="fas fa-plus-circle"></i> Add New FAQ</h2>
            <form action="manage_faqs.php" method="POST">
                <div class="mb-3">
                    <label for="question" class="form-label">Question:</label>
                    <input type="text" id="question" name="question" class="form-control" value="<?= htmlspecialchars($_POST['question'] ?? '') ?>" placeholder="Enter FAQ question" required>
                </div>
                <div class="mb-3">
                    <label for="answer" class="form-label">Answer:</label>
                    <textarea id="answer" name="answer" class="form-control" rows="5" placeholder="Enter FAQ answer" required><?= htmlspecialchars($_POST['answer'] ?? '') ?></textarea>
                </div>
                <button type="submit" name="add_faq" class="btn btn-primary"><i class="fas fa-plus"></i> Add FAQ</button>
            </form>
        </div>

        <div class="faq-list-section card p-4 shadow-sm">
            <h2><i class="fas fa-list-alt"></i> Existing FAQs</h2>
            <?php if (!empty($faqs)): ?>
                <div class="accordion" id="faqAccordion">
                    <?php foreach ($faqs as $faq): ?>
                        <div class="faq-item card mb-2">
                            <div class="card-header" id="heading<?= $faq['id'] ?>">
                                <h2 class="mb-0">
                                    <button class="btn btn-link btn-block text-start d-flex justify-content-between align-items-center w-100 collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $faq['id'] ?>" aria-expanded="false" aria-controls="collapse<?= $faq['id'] ?>">
                                        <?= htmlspecialchars($faq['question']) ?>
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                </h2>
                            </div>

                            <div id="collapse<?= $faq['id'] ?>" class="collapse" aria-labelledby="heading<?= $faq['id'] ?>" data-bs-parent="#faqAccordion">
                                <div class="card-body">
                                    <p><?= nl2br(htmlspecialchars($faq['answer'])) ?></p>
                                    <small class="text-muted">Created: <?= htmlspecialchars($faq['created_at']) ?></small><br>
                                    <?php
                                    if (!empty($faq['updated_at']) && (strtotime($faq['updated_at']) > strtotime($faq['created_at']) || (isset($faq['created_at']) && $faq['created_at'] === $faq['updated_at']))):
                                    ?>
                                        <small class="text-muted">Last Updated: <?= htmlspecialchars($faq['updated_at']) ?></small>
                                    <?php endif; ?>

                                    <div class="faq-actions mt-3">
                                        <button type="button" class="btn btn-sm btn-warning btn-edit" data-bs-toggle="modal" data-bs-target="#editFaqModal" data-id="<?= htmlspecialchars($faq['id']) ?>" data-question="<?= htmlspecialchars($faq['question']) ?>" data-answer="<?= htmlspecialchars($faq['answer']) ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form action="manage_faqs.php" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this FAQ?');">
                                            <input type="hidden" name="faq_id" value="<?= htmlspecialchars($faq['id']) ?>">
                                            <button type="submit" name="delete_faq" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i> Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No FAQs have been added yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

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
                        <textarea class="form-control" id="edit_answer" name="edit_answer" rows="5" required></textarea>
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


<?php include_once '../../templates/footer.php'; ?>

<script src="../../assets/js/jquery.min.js"></script>
<script src="../../assets/js/script.js"></script>



<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle chevron icon on accordion collapse
        var accordionButtons = document.querySelectorAll('.accordion .btn-link');
        accordionButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                var icon = this.querySelector('i');
                var targetCollapseId = this.getAttribute('data-bs-target');
                var targetCollapse = document.querySelector(targetCollapseId);

                // Check if the target is currently shown
                var isCurrentlyExpanded = targetCollapse.classList.contains('show');

                document.querySelectorAll('.accordion .collapse.show').forEach(function(openCollapse) {
                    if (openCollapse !== targetCollapse) {
                        new bootstrap.Collapse(openCollapse, { toggle: false }).hide();
                        openCollapse.closest('.faq-item').querySelector('.btn-link i').classList.remove('fa-chevron-up');
                        openCollapse.closest('.faq-item').querySelector('.btn-link i').classList.add('fa-chevron-down');
                    }
                });

                if (isCurrentlyExpanded) {
                    icon.classList.remove('fa-chevron-up');
                    icon.classList.add('fa-chevron-down');
                } else {
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-up');
                }
            });
        });

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
