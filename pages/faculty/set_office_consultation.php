<?php
// CHRONONAV_WEB_DOSS/pages/faculty/set_office_consultation.php

// Start the session at the very beginning of the script
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../backend/faculty/set_office_consultation_logic.php'; // Include the logic file
require_once '../../includes/functions.php'; // Assuming requireRole() and set_message() are here

// Restrict access to 'faculty' role only
requireRole(['faculty']);

// Fetch user data from session (already loaded by auth_check.php)
$user = $_SESSION['user'];

// --- START: Variables for Header and Sidenav ---
// These variables MUST be defined before including header_faculty.php
$page_title = "Set Office & Consultation Hours";
$current_page = "set_office_consultation"; // For sidenav highlighting

// Variables for the header template (display_username, display_user_role, profile_img_src)
$display_username = htmlspecialchars($user['name'] ?? 'Faculty');
$display_user_role = htmlspecialchars($user['role'] ?? 'Faculty');

// Attempt to get profile image path for the header
$profile_img_src = '../../uploads/profiles/default-avatar.png'; // Default fallback
if (!empty($user['profile_img']) && file_exists('../../' . $user['profile_img'])) {
    $profile_img_src = '../../' . $user['profile_img'];
}
// --- END: Variables for Header and Sidenav ---


// Messages will be set in the logic file and pulled from session
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message']);
unset($_SESSION['message_type']);

// Variables $facultyOfficeHoursRequests and $facultyConsultationHours are fetched by the logic file
// (set_office_consultation_logic.php already populates these)

?>

<?php
// --- Include the Faculty-specific Header ---
// This includes Bootstrap CSS, Font Awesome CSS, and top_navbar.css
require_once '../../templates/faculty/header_faculty.php';
?>

<?php
// --- Include the Faculty-specific Sidenav ---
require_once '../../templates/faculty/sidenav_faculty.php';
?>

<link rel="stylesheet" href="../../assets/css/faculty_css/set_office_consultations.css">


<div class="main-content-wrapper">
    <div class="main-dashboard-content">
        <div class="dashboard-header">
            <h2><?= $page_title ?></h2>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3>Request Office Hours</h3>
            <p class="text-muted">Submit a request for your preferred office hours. This requires admin approval.</p>
            <form action="../../backend/faculty/set_office_consultation_logic.php" method="POST">
                <input type="hidden" name="action" value="request_office_hours">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="proposed_day" class="form-label">Proposed Day(s):</label>
                        <input type="text" class="form-control" id="proposed_day" name="proposed_day" placeholder="e.g., Monday, TTh" required>
                    </div>
                    <div class="col-md-3">
                        <label for="proposed_start_time" class="form-label">Start Time:</label>
                        <input type="time" class="form-control" id="proposed_start_time" name="proposed_start_time" required>
                    </div>
                    <div class="col-md-3">
                        <label for="proposed_end_time" class="form-label">End Time:</label>
                        <input type="time" class="form-control" id="proposed_end_time" name="proposed_end_time" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="request_letter_message" class="form-label">Request Message for Admin:</label>
                    <textarea class="form-control" id="request_letter_message" name="request_letter_message" rows="4" placeholder="e.g., 'Dear Admin, I would like to request these office hours due to my class schedule on Tuesdays. Please let me know if this works.'"></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit Request</button>
            </form>
        </div>

        <div class="card">
            <h3>Your Office Hour Requests</h3>
            <?php if (!empty($facultyOfficeHoursRequests)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Proposed Schedule</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Admin Reply</th>
                                <th>Approved Schedule</th>
                                <th>Requested At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($facultyOfficeHoursRequests as $request): ?>
                                <tr>
                                    <td><?= htmlspecialchars($request['proposed_day']) ?> <?= htmlspecialchars(date('h:i A', strtotime($request['proposed_start_time']))) ?> - <?= htmlspecialchars(date('h:i A', strtotime($request['proposed_end_time']))) ?></td>
                                    <td><small><?= nl2br(htmlspecialchars($request['request_letter_message'])) ?></small></td>
                                    <td><span class="status-<?= htmlspecialchars(strtolower($request['status'])) ?>"><?= ucfirst(htmlspecialchars($request['status'])) ?></span></td>
                                    <td><small><?= nl2br(htmlspecialchars($request['admin_reply_message'] ?: 'N/A')) ?></small></td>
                                    <td><?= $request['approved_day'] ? htmlspecialchars($request['approved_day']) . ' ' . htmlspecialchars(date('h:i A', strtotime($request['approved_start_time']))) . ' - ' . htmlspecialchars(date('h:i A', strtotime($request['approved_end_time']))) : 'N/A' ?></td>
                                    <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($request['requested_at']))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">You have no pending or past office hour requests.</div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Your Available Consultation Hours for Students</h3>
            <p class="text-muted">These are the hours students can approach you for consultations without prior request, once you enable them. These do not require admin approval.</p>
            <form action="../../backend/faculty/set_office_consultation_logic.php" method="POST" class="mb-4">
                <input type="hidden" name="action" value="add_consultation_slot">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="consultation_day_of_week" class="form-label">Day(s):</label>
                        <input type="text" class="form-control" id="consultation_day_of_week" name="consultation_day_of_week" placeholder="e.g., Monday, Fri" required>
                    </div>
                    <div class="col-md-3">
                        <label for="consultation_start_time" class="form-label">Start Time:</label>
                        <input type="time" class="form-control" id="consultation_start_time" name="consultation_start_time" required>
                    </div>
                    <div class="col-md-3">
                        <label for="consultation_end_time" class="form-label">End Time:</label>
                        <input type="time" class="form-control" id="consultation_end_time" name="consultation_end_time" required>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-success"><i class="fas fa-plus-circle"></i> Add Slot</button>
                    </div>
                </div>
            </form>

            <?php if (!empty($facultyConsultationHours)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Day(s)</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($facultyConsultationHours as $slot): ?>
                                <tr>
                                    <td><?= htmlspecialchars($slot['day_of_week']) ?></td>
                                    <td><?= htmlspecialchars(date('h:i A', strtotime($slot['start_time']))) ?> - <?= htmlspecialchars(date('h:i A', strtotime($slot['end_time']))) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $slot['is_active'] ? 'success' : 'secondary' ?>">
                                            <?= $slot['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td class="btn-group-action">
                                        <button class="btn btn-sm btn-warning edit-consultation-btn"
                                            data-bs-toggle="modal" data-bs-target="#editConsultationModal"
                                            data-id="<?= htmlspecialchars($slot['id']) ?>"
                                            data-day="<?= htmlspecialchars($slot['day_of_week']) ?>"
                                            data-start-time="<?= htmlspecialchars($slot['start_time']) ?>"
                                            data-end-time="<?= htmlspecialchars($slot['end_time']) ?>"
                                            data-is-active="<?= htmlspecialchars($slot['is_active']) ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form action="../../backend/faculty/set_office_consultation_logic.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this consultation slot?');">
                                            <input type="hidden" name="action" value="delete_consultation_slot">
                                            <input type="hidden" name="slot_id" value="<?= htmlspecialchars($slot['id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">No consultation hours set yet. Add one above!</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>

<div class="modal fade" id="editConsultationModal" tabindex="-1" aria-labelledby="editConsultationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editConsultationModalLabel">Edit Consultation Slot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="../../backend/faculty/set_office_consultation_logic.php" method="POST">
                    <input type="hidden" name="action" value="edit_consultation_slot">
                    <input type="hidden" id="editConsultationId" name="slot_id">

                    <div class="mb-3">
                        <label for="editConsultationDayOfWeek" class="form-label">Day(s) of Week:</label>
                        <input type="text" class="form-control" id="editConsultationDayOfWeek" name="edit_consultation_day_of_week" required>
                    </div>
                    <div class="mb-3">
                        <label for="editConsultationStartTime" class="form-label">Start Time:</label>
                        <input type="time" class="form-control" id="editConsultationStartTime" name="edit_consultation_start_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="editConsultationEndTime" class="form-label">End Time:</label>
                        <input type="time" class="form-control" id="editConsultationEndTime" name="edit_consultation_end_time" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="editConsultationIsActive" name="edit_consultation_is_active" value="1">
                        <label class="form-check-label" for="editConsultationIsActive">
                            Mark as Active (Visible to Students)
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // JavaScript to populate the Edit Consultation Modal when it's shown
    var editConsultationModal = document.getElementById('editConsultationModal');
    editConsultationModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; // Button that triggered the modal

        // Extract info from data-* attributes
        var id = button.getAttribute('data-id');
        var day = button.getAttribute('data-day');
        var startTime = button.getAttribute('data-start-time');
        var endTime = button.getAttribute('data-end-time');
        var isActive = button.getAttribute('data-is-active');

        // Get references to the modal elements
        var modalIdInput = editConsultationModal.querySelector('#editConsultationId');
        var modalDayInput = editConsultationModal.querySelector('#editConsultationDayOfWeek');
        var modalStartTimeInput = editConsultationModal.querySelector('#editConsultationStartTime');
        var modalEndTimeInput = editConsultationModal.querySelector('#editConsultationEndTime');
        var modalIsActiveCheckbox = editConsultationModal.querySelector('#editConsultationIsActive');

        // Update the modal's content
        modalIdInput.value = id;
        modalDayInput.value = day;
        modalStartTimeInput.value = startTime;
        modalEndTimeInput.value = endTime;
        modalIsActiveCheckbox.checked = (isActive === '1'); // Set checkbox based on value '1'
    });
</script>