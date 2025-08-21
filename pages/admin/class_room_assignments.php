<?php
// CHRONONAV_WEB_DOSS/pages/admin/class_room_assignments.php

require_once '../../middleware/auth_check.php'; // Ensures user is logged in and session is started
require_once '../../config/db_connect.php'; // Database connection
require_once '../../includes/functions.php'; // Assuming requireRole function is here
require_once '../../backend/admin/class_room_assignments_logic.php'; // Include the logic file

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


$page_title = "Class Offerings & Assignments";
$current_page = "class_room_assignments"; // For sidenav highlighting

// Messages will be set in the logic file and pulled from session
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message']);
unset($_SESSION['message_type']);

// Fetch necessary data for forms and tables from the logic file
$facultyUsers = getFacultyUsers($conn); // Get users with 'faculty' role
$allClassOfferings = getAllClassOfferings($conn); // Get all class offerings
$allRooms = getAllRooms($conn); // Get all rooms

// --- START HTML STRUCTURE ---
// Include the admin header which contains <head> and opening <body> tags
require_once '../../templates/admin/header_admin.php';
require_once '../../templates/admin/sidenav_admin.php';
?>

<link rel="stylesheet" href="../../assets/css/admin_css/class_room_assignments.css">

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
                <h3 class="card-title">Add New Class Offering</h3>
                <form action="../../backend/admin/class_room_assignments_logic.php" method="POST">
                    <input type="hidden" name="action" value="add_class_offering">

                    <div class="mb-3">
                        <label for="class_name" class="form-label">Class Name:</label>
                        <input type="text" class="form-control" id="class_name" name="class_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="class_code" class="form-label">Class Code:</label>
                        <input type="text" class="form-control" id="class_code" name="class_code" placeholder="e.g., IT201, CS305" required>
                        <small class="text-muted">This identifies the course itself. If you offer the same course multiple times, use the same code.</small>
                    </div>
                    <div class="mb-3">
                        <label for="semester" class="form-label">Semester:</label>
                        <input type="text" class="form-control" id="semester" name="semester" placeholder="e.g., Fall 2025, Spring 2026" required>
                    </div>

                    <div class="mb-3">
                        <label for="faculty_id" class="form-label">Assign Faculty:</label>
                        <select class="form-select" id="faculty_id" name="faculty_id" required>
                            <option value="">-- Select Faculty --</option>
                            <?php foreach ($facultyUsers as $faculty): ?>
                                <option value="<?= htmlspecialchars($faculty['id']) ?>">
                                    <?= htmlspecialchars($faculty['name']) ?> (<?= htmlspecialchars($faculty['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($facultyUsers)): ?>
                            <small class="text-danger">No faculty users found. Please add faculty users in User Management.</small>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="room_id" class="form-label">Assign Room:</label>
                        <select class="form-select" id="room_id" name="room_id" required>
                            <option value="">-- Select Room --</option>
                            <?php foreach ($allRooms as $room): ?>
                                <option value="<?= htmlspecialchars($room['id']) ?>">
                                    <?= htmlspecialchars($room['room_name']) ?> (Capacity: <?= htmlspecialchars($room['capacity']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($allRooms)): ?>
                            <small class="text-danger">No rooms found. You might need a separate page to add rooms first.</small>
                        <?php endif; ?>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="day_of_week" class="form-label">Day(s) of Week:</label>
                            <input type="text" class="form-control" id="day_of_week" name="day_of_week" placeholder="e.g., Monday, TTh, MWF" required>
                            <small class="text-muted">Enter days like 'Monday', 'Tuesday', or 'MWF', 'TTh' for multiple days. Be consistent.</small>
                        </div>
                        <div class="col-md-3">
                            <label for="start_time" class="form-label">Start Time:</label>
                            <input type="time" class="form-control" id="start_time" name="start_time" required>
                        </div>
                        <div class="col-md-3">
                            <label for="end_time" class="form-label">End Time:</label>
                            <input type="time" class="form-control" id="end_time" name="end_time" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Add Class Offering</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h3 class="card-title">Current Class Offerings</h3>
                <?php if (!empty($allClassOfferings)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Class Code</th>
                                    <th>Class Name</th>
                                    <th>Semester</th>
                                    <th>Faculty</th>
                                    <th>Room</th>
                                    <th>Schedule</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allClassOfferings as $offering): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($offering['class_id']) ?></td>
                                        <td><?= htmlspecialchars($offering['class_code']) ?></td>
                                        <td><?= htmlspecialchars($offering['class_name']) ?></td>
                                        <td><?= htmlspecialchars($offering['semester']) ?></td>
                                        <td><?= htmlspecialchars($offering['faculty_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($offering['room_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($offering['day_of_week']) ?> <?= htmlspecialchars(date('h:i A', strtotime($offering['start_time']))) ?> - <?= htmlspecialchars(date('h:i A', strtotime($offering['end_time']))) ?></td>
                                        <td class="btn-group-action">
                                            <button class="btn btn-sm btn-warning edit-offering-btn"
                                                data-bs-toggle="modal" data-bs-target="#editClassOfferingModal"
                                                data-id="<?= htmlspecialchars($offering['class_id']) ?>"
                                                data-class-name="<?= htmlspecialchars($offering['class_name']) ?>"
                                                data-class-code="<?= htmlspecialchars($offering['class_code']) ?>"
                                                data-semester="<?= htmlspecialchars($offering['semester']) ?>"
                                                data-faculty-id="<?= htmlspecialchars($offering['faculty_id']) ?>"
                                                data-room-id="<?= htmlspecialchars($offering['room_id']) ?>"
                                                data-day="<?= htmlspecialchars($offering['day_of_week']) ?>"
                                                data-start-time="<?= htmlspecialchars($offering['start_time']) ?>"
                                                data-end-time="<?= htmlspecialchars($offering['end_time']) ?>"
                                            >
                                                <i class="fas fa-edit"></i> Edit
                                            </button>

                                            <form action="../../backend/admin/class_room_assignments_logic.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this class offering? This cannot be undone.');">
                                                <input type="hidden" name="action" value="delete_class_offering">
                                                <input type="hidden" name="class_id" value="<?= htmlspecialchars($offering['class_id']) ?>">
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
                    <div class="alert alert-info text-center">No class offerings found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editClassOfferingModal" tabindex="-1" aria-labelledby="editClassOfferingModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editClassOfferingModalLabel">Edit Class Offering</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="../../backend/admin/class_room_assignments_logic.php" method="POST">
                        <input type="hidden" name="action" value="edit_class_offering">
                        <input type="hidden" id="editClassOfferingId" name="class_id">

                        <div class="mb-3">
                            <label for="editClassName" class="form-label">Class Name:</label>
                            <input type="text" class="form-control" id="editClassName" name="class_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editClassCode" class="form-label">Class Code:</label>
                            <input type="text" class="form-control" id="editClassCode" name="class_code" required>
                        </div>
                        <div class="mb-3">
                            <label for="editSemester" class="form-label">Semester:</label>
                            <input type="text" class="form-control" id="editSemester" name="semester" required>
                        </div>

                        <div class="mb-3">
                            <label for="editFacultyId" class="form-label">Assign Faculty:</label>
                            <select class="form-select" id="editFacultyId" name="faculty_id" required>
                                <option value="">-- Select Faculty --</option>
                                <?php foreach ($facultyUsers as $faculty): ?>
                                    <option value="<?= htmlspecialchars($faculty['id']) ?>">
                                        <?= htmlspecialchars($faculty['name']) ?> (<?= htmlspecialchars($faculty['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="editRoomId" class="form-label">Assign Room:</label>
                            <select class="form-select" id="editRoomId" name="room_id" required>
                                <option value="">-- Select Room --</option>
                                <?php foreach ($allRooms as $room): ?>
                                    <option value="<?= htmlspecialchars($room['id']) ?>">
                                        <?= htmlspecialchars($room['room_name']) ?> (Capacity: <?= htmlspecialchars($room['capacity']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editDayOfWeek" class="form-label">Day(s) of Week:</label>
                                <input type="text" class="form-control" id="editDayOfWeek" name="day_of_week" placeholder="e.g., Monday, TTh, MWF" required>
                            </div>
                            <div class="col-md-3">
                                <label for="editStartTime" class="form-label">Start Time:</label>
                                <input type="time" class="form-control" id="editStartTime" name="start_time" required>
                            </div>
                            <div class="col-md-3">
                                <label for="editEndTime" class="form-label">End Time:</label>
                                <input type="time" class="form-control" id="editEndTime" name="end_time" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
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
    // JavaScript to populate the Edit Class Offering Modal when it's shown
    var editClassOfferingModal = document.getElementById('editClassOfferingModal');
    editClassOfferingModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; // Button that triggered the modal

        // Extract info from data-* attributes
        var id = button.getAttribute('data-id');
        var className = button.getAttribute('data-class-name');
        var classCode = button.getAttribute('data-class-code');
        var semester = button.getAttribute('data-semester'); // Reintroduced semester
        var facultyId = button.getAttribute('data-faculty-id');
        var roomId = button.getAttribute('data-room-id');
        var day = button.getAttribute('data-day');
        var startTime = button.getAttribute('data-start-time');
        var endTime = button.getAttribute('data-end-time');

        // Get references to the modal elements
        var modalClassOfferingIdInput = editClassOfferingModal.querySelector('#editClassOfferingId');
        var modalClassNameInput = editClassOfferingModal.querySelector('#editClassName');
        var modalClassCodeInput = editClassOfferingModal.querySelector('#editClassCode');
        var modalSemesterInput = editClassOfferingModal.querySelector('#editSemester'); // Reintroduced semester
        var modalFacultySelect = editClassOfferingModal.querySelector('#editFacultyId');
        var modalRoomSelect = editClassOfferingModal.querySelector('#editRoomId');
        var modalDayInput = editClassOfferingModal.querySelector('#editDayOfWeek');
        var modalStartTimeInput = editClassOfferingModal.querySelector('#editStartTime');
        var modalEndTimeInput = editClassOfferingModal.querySelector('#editEndTime');

        // Update the modal's content
        modalClassOfferingIdInput.value = id;
        modalClassNameInput.value = className;
        modalClassCodeInput.value = classCode;
        modalSemesterInput.value = semester; // Reintroduced semester
        modalFacultySelect.value = facultyId;
        modalRoomSelect.value = roomId || '';
        modalDayInput.value = day;
        modalStartTimeInput.value = startTime;
        modalEndTimeInput.value = endTime;
    });
</script>

