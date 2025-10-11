<?php
// CHRONONAV_WEB_DOSS/pages/faculty/set_office_consultation.php

// Start the session at the very beginning of the script
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../backend/faculty/set_office_consultation_logic.php';
require_once '../../includes/functions.php';

// Restrict access to 'faculty' role only
requireRole(['faculty']);

// Fetch user data from session (already loaded by auth_check.php)
$user = $_SESSION['user'];

// --- START: Variables for Header and Sidenav ---
$page_title = "Set Office & Consultation Hours";
$current_page = "set_office_consultation";

$display_username = htmlspecialchars($user['name'] ?? 'Faculty');
$display_user_role = htmlspecialchars($user['role'] ?? 'Faculty');

$profile_img_src = '../../uploads/profiles/default-avatar.png';
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
?>

<?php
// --- Include the Faculty-specific Header ---
require_once '../../templates/faculty/header_faculty.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'ChronoNav - Office Hours' ?></title>

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
            background-color: #ffffff;
            min-height: 100vh;
        }

        .layout-container {
            min-height: 100vh;
        }

        .layout-content-container {
            flex: 1;
            margin-left: 20%;
        }

        .page-title {
            color: #0e151b;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: -0.015em;
        }

        .section-title {
            color: #0e151b;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.015em;
        }

        .form-label {
            color: #0e151b;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-control,
        .form-control:focus {
            background-color: #f8fafb;
            border-color: #d1dce6;
            color: #0e151b;
            height: 56px;
            padding: 15px;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #d1dce6;
        }

        .form-control::placeholder {
            color: #507495;
        }

        .input-group-text {
            background-color: #f8fafb;
            border-color: #d1dce6;
            color: #507495;
            border-left: 0;
        }

        .input-group .form-control {
            border-right: 0;
        }

        textarea.form-control {
            min-height: 144px;
            resize: none;
        }

        .btn-primary {
            background-color: #1d7dd7;
            border-color: #1d7dd7;
            color: #f8fafb;
            font-weight: 700;
            letter-spacing: 0.015em;
            padding: 8px 16px;
            height: 40px;
        }

        .btn-primary:hover {
            background-color: #1a6fc0;
            border-color: #1a6fc0;
        }

        .btn-success {
            background-color: #198754;
            border-color: #198754;
            color: #f8fafb;
            font-weight: 700;
            letter-spacing: 0.015em;
            padding: 8px 16px;
            height: 40px;
        }

        .info-text {
            color: #0e151b;
        }

        .empty-state-text {
            color: #507495;
            font-size: 14px;
        }

        .card {
            background-color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .alert {
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .table {
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
        }

        .table th {
            background-color: #f8fafb;
            color: #0e151b;
            font-weight: 600;
            border-bottom: 1px solid #d1dce6;
        }

        .table td {
            border-bottom: 1px solid #f1f1f1;
            color: #0e151b;
        }

        .badge {
            font-weight: 500;
            padding: 0.5rem 1rem;
        }

        .btn-sm {
            height: 32px;
            padding: 4px 12px;
            font-size: 0.875rem;
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
    </style>
</head>

<body>
    <?php require_once '../../templates/faculty/sidenav_faculty.php'; ?>

    <div class="layout-container d-flex flex-column">
        <div class="container-fluid flex-grow-1 py-3">
            <div class="row justify-content-center">
                <!-- Main content -->
                <div class="col-12">
                    <div class="layout-content-container">
                        <!-- Page header -->
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-3">
                            <h1 class="page-title m-0">Set Office & Consultation Hours</h1>
                        </div>

                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show"
                                role="alert">
                                <?= htmlspecialchars($message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Office Hours Request Section -->
                        <div class="card">
                            <h2 class="section-title px-3 pb-3 pt-0">Request Office Hours (Requires Admin Approval)</h2>
                            <p class="info-text px-3 pb-3">
                                Submit a request for your office hours. Once approved by the administrator, these hours
                                will be reflected in your schedule.
                            </p>

                            <form action="../../backend/faculty/set_office_consultation_logic.php" method="POST">
                                <input type="hidden" name="action" value="request_office_hours">

                                <div class="row px-3 py-3">
                                    <div class="col-12 col-md-6">
                                        <div class="mb-4">
                                            <label class="form-label">Proposed Day(s)</label>
                                            <input type="text" class="form-control" name="proposed_day"
                                                placeholder="e.g., Mondays and Wednesdays" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row px-3 py-3">
                                    <div class="col-12 col-md-6">
                                        <div class="mb-4">
                                            <label class="form-label">Start Time</label>
                                            <div class="input-group">
                                                <input type="time" class="form-control" name="proposed_start_time"
                                                    required>
                                                <span class="input-group-text">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px"
                                                        fill="currentColor" viewBox="0 0 256 256">
                                                        <path
                                                            d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm64-88a8,8,0,0,1-8,8H128a8,8,0,0,1-8-8V72a8,8,0,0,1,16,0v48h48A8,8,0,0,1,192,128Z">
                                                        </path>
                                                    </svg>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row px-3 py-3">
                                    <div class="col-12 col-md-6">
                                        <div class="mb-4">
                                            <label class="form-label">End Time</label>
                                            <div class="input-group">
                                                <input type="time" class="form-control" name="proposed_end_time"
                                                    required>
                                                <span class="input-group-text">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px"
                                                        fill="currentColor" viewBox="0 0 256 256">
                                                        <path
                                                            d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm64-88a8,8,0,0,1-8,8H128a8,8,0,0,1-8-8V72a8,8,0,0,1,16,0v48h48A8,8,0,0,1,192,128Z">
                                                        </path>
                                                    </svg>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row px-3 py-3">
                                    <div class="col-12 col-md-6">
                                        <div class="mb-4">
                                            <label class="form-label">Request Message</label>
                                            <textarea class="form-control" name="request_letter_message"
                                                placeholder="Optional: Add any specific details or notes for your request."
                                                rows="4"></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="px-3 py-3">
                                    <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px"
                                            fill="currentColor" viewBox="0 0 256 256">
                                            <path
                                                d="M227.32,28.68a16,16,0,0,0-15.66-4.08l-.15,0L19.57,82.84a16,16,0,0,0-2.42,29.84l85.62,40.55,40.55,85.62A15.86,15.86,0,0,0,157.74,248q.69,0,1.38-.06a15.88,15.88,0,0,0,14-11.51l58.2-191.94c0-.05,0-.1,0-.15A16,16,0,0,0,227.32,28.68ZM157.83,231.85l-.05.14L118.42,148.9l47.24-47.25a8,8,0,0,0-11.31-11.31L107.1,137.58,24,98.22l.14,0L216,40Z">
                                            </path>
                                        </svg>
                                        Submit Request
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Office Hour Requests Section -->
                        <div class="card">
                            <h2 class="section-title px-3 pb-3 pt-0">Your Office Hour Requests</h2>
                            <?php if (!empty($facultyOfficeHoursRequests)): ?>
                                <div class="table-responsive">
                                    <table class="table">
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
                                                    <td><?= htmlspecialchars($request['proposed_day']) ?>
                                                        <?= htmlspecialchars(date('h:i A', strtotime($request['proposed_start_time']))) ?>
                                                        -
                                                        <?= htmlspecialchars(date('h:i A', strtotime($request['proposed_end_time']))) ?>
                                                    </td>
                                                    <td><small><?= nl2br(htmlspecialchars($request['request_letter_message'])) ?></small>
                                                    </td>
                                                    <td><span
                                                            class="badge bg-<?= strtolower($request['status']) === 'approved' ? 'success' : (strtolower($request['status']) === 'pending' ? 'warning' : 'danger') ?>"><?= ucfirst(htmlspecialchars($request['status'])) ?></span>
                                                    </td>
                                                    <td><small><?= nl2br(htmlspecialchars($request['admin_reply_message'] ?: 'N/A')) ?></small>
                                                    </td>
                                                    <td><?= $request['approved_day'] ? htmlspecialchars($request['approved_day']) . ' ' . htmlspecialchars(date('h:i A', strtotime($request['approved_start_time']))) . ' - ' . htmlspecialchars(date('h:i A', strtotime($request['approved_end_time']))) : 'N/A' ?>
                                                    </td>
                                                    <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($request['requested_at']))) ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="empty-state-text px-3">You have no pending or past office hour requests.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Consultation Hours Section -->
                        <div class="card">
                            <h2 class="section-title px-3 pb-3 pt-0">Your Available Consultation Hours for Students</h2>
                            <p class="info-text px-3 pb-3">
                                Set up consultation hours for students to book appointments directly. These hours will
                                be immediately available for students to schedule.
                            </p>

                            <form action="../../backend/faculty/set_office_consultation_logic.php" method="POST">
                                <input type="hidden" name="action" value="add_consultation_slot">

                                <div class="row px-3 py-3">
                                    <div class="col-12 col-md-6">
                                        <div class="mb-4">
                                            <label class="form-label">Day(s)</label>
                                            <input type="text" class="form-control" name="consultation_day_of_week"
                                                placeholder="e.g., Tuesdays and Thursdays" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row px-3 py-3">
                                    <div class="col-12 col-md-6">
                                        <div class="mb-4">
                                            <label class="form-label">Start Time</label>
                                            <div class="input-group">
                                                <input type="time" class="form-control" name="consultation_start_time"
                                                    required>
                                                <span class="input-group-text">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px"
                                                        fill="currentColor" viewBox="0 0 256 256">
                                                        <path
                                                            d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm64-88a8,8,0,0,1-8,8H128a8,8,0,0,1-8-8V72a8,8,0,0,1,16,0v48h48A8,8,0,0,1,192,128Z">
                                                        </path>
                                                    </svg>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row px-3 py-3">
                                    <div class="col-12 col-md-6">
                                        <div class="mb-4">
                                            <label class="form-label">End Time</label>
                                            <div class="input-group">
                                                <input type="time" class="form-control" name="consultation_end_time"
                                                    required>
                                                <span class="input-group-text">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px"
                                                        fill="currentColor" viewBox="0 0 256 256">
                                                        <path
                                                            d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm64-88a8,8,0,0,1-8,8H128a8,8,0,0,1-8-8V72a8,8,0,0,1,16,0v48h48A8,8,0,0,1,192,128Z">
                                                        </path>
                                                    </svg>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="px-3 py-3">
                                    <button type="submit" class="btn btn-success d-flex align-items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px"
                                            fill="currentColor" viewBox="0 0 256 256">
                                            <path
                                                d="M224,128a8,8,0,0,1-8,8H136v80a8,8,0,0,1-16,0V136H40a8,8,0,0,1,0-16h80V40a8,8,0,0,1,16,0v80h80A8,8,0,0,1,224,128Z">
                                            </path>
                                        </svg>
                                        Add Slot
                                    </button>
                                </div>
                            </form>

                            <?php if (!empty($facultyConsultationHours)): ?>
                                <div class="table-responsive">
                                    <table class="table">
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
                                                    <td><?= htmlspecialchars(date('h:i A', strtotime($slot['start_time']))) ?> -
                                                        <?= htmlspecialchars(date('h:i A', strtotime($slot['end_time']))) ?>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge bg-<?= $slot['is_active'] ? 'success' : 'secondary' ?>">
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
                                                        <form action="../../backend/faculty/set_office_consultation_logic.php"
                                                            method="POST" style="display:inline;"
                                                            onsubmit="return confirm('Are you sure you want to delete this consultation slot?');">
                                                            <input type="hidden" name="action" value="delete_consultation_slot">
                                                            <input type="hidden" name="slot_id"
                                                                value="<?= htmlspecialchars($slot['id']) ?>">
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
                                <p class="empty-state-text px-3">No consultation hours set yet. Add one above!</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Consultation Modal -->
    <div class="modal fade" id="editConsultationModal" tabindex="-1" aria-labelledby="editConsultationModalLabel"
        aria-hidden="true">
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
                            <input type="text" class="form-control" id="editConsultationDayOfWeek"
                                name="edit_consultation_day_of_week" required>
                        </div>
                        <div class="mb-3">
                            <label for="editConsultationStartTime" class="form-label">Start Time:</label>
                            <input type="time" class="form-control" id="editConsultationStartTime"
                                name="edit_consultation_start_time" required>
                        </div>
                        <div class="mb-3">
                            <label for="editConsultationEndTime" class="form-label">End Time:</label>
                            <input type="time" class="form-control" id="editConsultationEndTime"
                                name="edit_consultation_end_time" required>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="editConsultationIsActive"
                                name="edit_consultation_is_active" value="1">
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

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // JavaScript to populate the Edit Consultation Modal when it's shown
        var editConsultationModal = document.getElementById('editConsultationModal');
        editConsultationModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;

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
            modalIsActiveCheckbox.checked = (isActive === '1');
        });
    </script>

    <?php require_once '../../templates/footer.php'; ?>
</body>

<?php include('../../includes/semantics/footer.php'); ?>

</html>