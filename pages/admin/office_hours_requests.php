<?php
// CHRONONAV_WEB_DOSS/pages/admin/office_hours_requests.php

require_once '../../middleware/auth_check.php'; // Ensures user is logged in and session is started
require_once '../../config/db_connect.php'; // Database connection
require_once '../../includes/functions.php'; // Assuming requireRole function is here
require_once '../../backend/admin/office_hours_requests_logic.php'; // Include the logic file

// Ensure the user is logged in and has the 'admin' role for this admin page
requireRole(['admin']);

// Fetch user data for header display (name, role, profile_img)
// This is done here so the variables are available before including the header.
$user_id = $_SESSION['user']['id'];
$stmt = $conn->prepare("SELECT name, role, profile_img FROM users WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $header_user_data = $result->fetch_assoc();
        $display_username = htmlspecialchars($header_user_data['name'] ?? 'Admin User');
        $display_user_role = htmlspecialchars(ucfirst($header_user_data['role'] ?? 'Admin'));
        // Construct profile_img_src for the header, relative to the templates/admin directory
        $profile_img_src = (strpos($header_user_data['profile_img'], 'uploads/') === 0) ? '../../' . $header_user_data['profile_img'] : '../../uploads/profiles/default-avatar.png';
    } else {
        // Fallback if user data for header somehow isn't found (shouldn't happen with auth_check)
        $display_username = 'Admin User';
        $display_user_role = 'Admin';
        $profile_img_src = '../../uploads/profiles/default-avatar.png';
    }
    $stmt->close();
} else {
    $display_username = 'Admin User';
    $display_user_role = 'Admin';
    $profile_img_src = '../../uploads/profiles/default-avatar.png';
}


$page_title = "Office Hours Requests";
$current_page = "office_hours_requests"; // For sidenav highlighting

// Messages will be set in the logic file and pulled from session
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message']);
unset($_SESSION['message_type']);

// officeHoursRequests variable is fetched by office_hours_requests_logic.php

// --- START HTML STRUCTURE ---
// Include the admin header which contains <head> and opening <body> tags
require_once '../../templates/admin/header_admin.php';
require_once '../../templates/admin/sidenav_admin.php'; // Sidenav is included here
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/admin_css/office_hours_requests.css">
<div class="main-content-wrapper">
    <div class="container-fluid py-4">
        <h2 class="mb-4"><?= $page_title ?></h2>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h3 class="card-title">Pending & Resolved Requests</h3>
                <?php if (!empty($officeHoursRequests)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Faculty</th>
                                    <th>Proposed Schedule</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Admin Reply</th>
                                    <th>Requested At</th>
                                    <th>Responded At</th>
                                    <th>Approved Schedule</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($officeHoursRequests as $request): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($request['id']) ?></td>
                                        <td><?= htmlspecialchars($request['faculty_name']) ?> (<?= htmlspecialchars($request['faculty_email']) ?>)</td>
                                        <td><?= htmlspecialchars($request['proposed_day']) ?> <?= htmlspecialchars(date('h:i A', strtotime($request['proposed_start_time']))) ?> - <?= htmlspecialchars(date('h:i A', strtotime($request['proposed_end_time']))) ?></td>
                                        <td><small><?= nl2br(htmlspecialchars($request['request_letter_message'])) ?></small></td>
                                        <td><span class="status-<?= htmlspecialchars($request['status']) ?>"><?= ucfirst(htmlspecialchars($request['status'])) ?></span></td>
                                        <td><small><?= nl2br(htmlspecialchars($request['admin_reply_message'] ?: 'N/A')) ?></small></td>
                                        <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($request['requested_at']))) ?></td>
                                        <td><?= $request['responded_at'] ? htmlspecialchars(date('M d, Y h:i A', strtotime($request['responded_at']))) : 'N/A' ?></td>
                                        <td><?= $request['approved_day'] ? htmlspecialchars($request['approved_day']) . ' ' . htmlspecialchars(date('h:i A', strtotime($request['approved_start_time']))) . ' - ' . htmlspecialchars(date('h:i A', strtotime($request['approved_end_time']))) : 'N/A' ?></td>
                                        <td class="btn-group-action">
                                            <?php if ($request['status'] === 'pending' || $request['status'] === 'revised'): ?>
                                                <button class="btn btn-sm btn-success approve-btn"
                                                    data-bs-toggle="modal" data-bs-target="#approveRejectModal"
                                                    data-request-id="<?= htmlspecialchars($request['id']) ?>"
                                                    data-faculty-name="<?= htmlspecialchars($request['faculty_name']) ?>"
                                                    data-proposed-day="<?= htmlspecialchars($request['proposed_day']) ?>"
                                                    data-proposed-start="<?= htmlspecialchars($request['proposed_start_time']) ?>"
                                                    data-proposed-end="<?= htmlspecialchars($request['proposed_end_time']) ?>"
                                                    data-mode="approve">
                                                    <i class="fas fa-check-circle"></i> Approve
                                                </button>
                                                <button class="btn btn-sm btn-info revise-btn"
                                                    data-bs-toggle="modal" data-bs-target="#approveRejectModal"
                                                    data-request-id="<?= htmlspecialchars($request['id']) ?>"
                                                    data-faculty-name="<?= htmlspecialchars($request['faculty_name']) ?>"
                                                    data-proposed-day="<?= htmlspecialchars($request['proposed_day']) ?>"
                                                    data-proposed-start="<?= htmlspecialchars($request['proposed_start_time']) ?>"
                                                    data-proposed-end="<?= htmlspecialchars($request['proposed_end_time']) ?>"
                                                    data-mode="revise">
                                                    <i class="fas fa-redo-alt"></i> Revise
                                                </button>
                                                <button class="btn btn-sm btn-danger reject-btn"
                                                    data-bs-toggle="modal" data-bs-target="#approveRejectModal"
                                                    data-request-id="<?= htmlspecialchars($request['id']) ?>"
                                                    data-faculty-name="<?= htmlspecialchars($request['faculty_name']) ?>"
                                                    data-mode="reject">
                                                    <i class="fas fa-times-circle"></i> Reject
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">No actions</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">No office hour requests found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="approveRejectModal" tabindex="-1" aria-labelledby="approveRejectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="approveRejectModalLabel">Process Office Hour Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="../../backend/admin/office_hours_requests_logic.php" method="POST" id="processRequestForm">
                        <input type="hidden" name="action" id="modalAction">
                        <input type="hidden" name="request_id" id="modalRequestId">

                        <p>Request from: <strong id="modalFacultyName"></strong></p>
                        <p id="proposedScheduleDisplay">Proposed Schedule: <strong id="modalProposedSchedule"></strong></p>

                        <div id="approvedRevisedFields" style="display:none;">
                            <h6>Approved/Revised Schedule:</h6>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="approvedDay" class="form-label">Day(s) of Week:</label>
                                    <input type="text" class="form-control" id="approvedDay" name="approved_day" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="approvedStartTime" class="form-label">Start Time:</label>
                                    <input type="time" class="form-control" id="approvedStartTime" name="approved_start_time" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="approvedEndTime" class="form-label">End Time:</label>
                                    <input type="time" class="form-control" id="approvedEndTime" name="approved_end_time" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="adminReply" class="form-label">Your Reply/Message to Faculty:</label>
                            <textarea class="form-control" id="adminReply" name="admin_reply" rows="3" required></textarea>
                        </div>

                        <div class="modal-footer justify-content-between px-0 pb-0 pt-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary" id="modalSubmitBtn">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include the common footer which closes <body> and <html> and includes common JS
include '../../templates/footer.php';
?>

<script>
    var approveRejectModal = document.getElementById('approveRejectModal');
    approveRejectModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; // Button that triggered the modal
        var requestId = button.getAttribute('data-request-id');
        var facultyName = button.getAttribute('data-faculty-name');
        var mode = button.getAttribute('data-mode'); // 'approve', 'reject', 'revise'

        // Populate common fields
        var modalTitle = approveRejectModal.querySelector('.modal-title');
        var modalActionInput = approveRejectModal.querySelector('#modalAction');
        var modalRequestIdInput = approveRejectModal.querySelector('#modalRequestId');
        var modalFacultyName = approveRejectModal.querySelector('#modalFacultyName');
        var modalProposedSchedule = approveRejectModal.querySelector('#modalProposedSchedule');
        var adminReplyTextarea = approveRejectModal.querySelector('#adminReply');
        var approvedRevisedFields = approveRejectModal.querySelector('#approvedRevisedFields');
        var approvedDayInput = approveRejectModal.querySelector('#approvedDay');
        var approvedStartTimeInput = approveRejectModal.querySelector('#approvedStartTime');
        var approvedEndTimeInput = approveRejectModal.querySelector('#approvedEndTime');
        var modalSubmitBtn = approveRejectModal.querySelector('#modalSubmitBtn');

        modalRequestIdInput.value = requestId;
        modalFacultyName.textContent = facultyName;
        modalActionInput.value = mode + '_request'; // e.g., 'approve_request'

        // Reset fields
        adminReplyTextarea.value = '';
        approvedRevisedFields.style.display = 'none';
        approvedDayInput.removeAttribute('required');
        approvedStartTimeInput.removeAttribute('required');
        approvedEndTimeInput.removeAttribute('required');

        if (mode === 'approve' || mode === 'revise') {
            var proposedDay = button.getAttribute('data-proposed-day');
            var proposedStart = button.getAttribute('data-proposed-start');
            var proposedEnd = button.getAttribute('data-proposed-end');

            // Format time for display
            function formatTime(timeStr) {
                const [hours, minutes] = timeStr.split(':');
                const date = new Date();
                date.setHours(parseInt(hours), parseInt(minutes));
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
            }

            modalProposedSchedule.textContent = `${proposedDay} ${formatTime(proposedStart)} - ${formatTime(proposedEnd)}`;
            approvedRevisedFields.style.display = 'block';
            approvedDayInput.setAttribute('required', 'required');
            approvedStartTimeInput.setAttribute('required', 'required');
            approvedEndTimeInput.setAttribute('required', 'required');

            // Pre-fill approved fields with proposed values for convenience
            approvedDayInput.value = proposedDay;
            approvedStartTimeInput.value = proposedStart;
            approvedEndTimeInput.value = proposedEnd;
        } else {
            modalProposedSchedule.textContent = "N/A"; // Or hide this line
        }

        // Customize based on mode
        if (mode === 'approve') {
            modalTitle.textContent = 'Approve Office Hour Request';
            adminReplyTextarea.placeholder = 'e.g., "Your office hours have been approved as requested."';
            adminReplyTextarea.value = 'Your office hours have been approved as requested.'; // Default reply
            modalSubmitBtn.textContent = 'Approve Request';
            modalSubmitBtn.className = 'btn btn-success';
        } else if (mode === 'reject') {
            modalTitle.textContent = 'Reject Office Hour Request';
            adminReplyTextarea.placeholder = 'e.g., "Your request has been rejected due to schedule conflict. Please resubmit."';
            adminReplyTextarea.value = ''; // Clear default for rejection
            modalSubmitBtn.textContent = 'Reject Request';
            modalSubmitBtn.className = 'btn btn-danger';
        } else if (mode === 'revise') {
            modalTitle.textContent = 'Revise Office Hour Request';
            adminReplyTextarea.placeholder = 'e.g., "We can approve these hours if you shift them by 30 mins earlier."';
            adminReplyTextarea.value = ''; // Clear default for revision
            modalSubmitBtn.textContent = 'Send Revision';
            modalSubmitBtn.className = 'btn btn-info';
        }
    });
</script>