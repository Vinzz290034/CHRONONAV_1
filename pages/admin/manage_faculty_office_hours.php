<?php
// CHRONONAV_WEB_UNO/pages/admin/manage_faculty_office_hours.php

require_once __DIR__ . '/../../middleware/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Restrict access to only 'admin' role for this management page.
requireRole(['admin']);

$adminUserId = $_SESSION['user']['id'];
$message = '';
$messageType = '';

// $conn is available globally

// --- Handle Form Submission (Add/Edit/Delete Office Hours for a selected faculty) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedFacultyId = filter_input(INPUT_POST, 'selected_faculty_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? ''; // 'add', 'edit', 'delete'

    if (!$selectedFacultyId) {
        $message = "No faculty member selected or invalid faculty ID.";
        $messageType = "danger";
    } else {
        try {
            switch ($action) {
                case 'add':
                case 'edit':
                    $dayOfWeek = filter_input(INPUT_POST, 'day_of_week', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    $startTime = filter_input(INPUT_POST, 'start_time', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    $endTime = filter_input(INPUT_POST, 'end_time', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    $ohId = filter_input(INPUT_POST, 'oh_id', FILTER_VALIDATE_INT);

                    if (empty($dayOfWeek) || empty($startTime) || empty($endTime) || empty($location)) {
                        $message = "All fields (Day, Time, Location) are required for adding/editing.";
                        $messageType = "danger";
                    } else {
                        if ($ohId) {
                            // Update existing office hours for the selected faculty
                            $stmt = $conn->prepare("UPDATE office_hours SET day_of_week = ?, start_time = ?, end_time = ?, location = ?, updated_at = CURRENT_TIMESTAMP WHERE oh_id = ? AND faculty_id = ?");
                            $stmt->bind_param("sssisi", $dayOfWeek, $startTime, $endTime, $location, $ohId, $selectedFacultyId);
                            $stmt->execute();
                            if ($stmt->affected_rows > 0) {
                                $message = "Office hours for selected faculty updated successfully!";
                                $messageType = "success";
                            } else {
                                $message = "No changes made or office hours not found for selected faculty.";
                                $messageType = "info";
                            }
                        } else {
                            // Insert new office hours for the selected faculty
                            $stmt = $conn->prepare("INSERT INTO office_hours (faculty_id, day_of_week, start_time, end_time, location) VALUES (?, ?, ?, ?, ?)");
                            $stmt->bind_param("issss", $selectedFacultyId, $dayOfWeek, $startTime, $endTime, $location);
                            $stmt->execute();
                            if ($stmt->affected_rows > 0) {
                                $message = "Office hours for selected faculty added successfully!";
                                $messageType = "success";
                            } else {
                                $message = "Failed to add office hours for selected faculty.";
                                $messageType = "danger";
                            }
                        }
                        $stmt->close();
                    }
                    break;

                case 'delete':
                    $ohId = filter_input(INPUT_POST, 'oh_id', FILTER_VALIDATE_INT);
                    if ($ohId) {
                        $stmt = $conn->prepare("DELETE FROM office_hours WHERE oh_id = ? AND faculty_id = ?");
                        $stmt->bind_param("ii", $ohId, $selectedFacultyId);
                        $stmt->execute();
                        if ($stmt->affected_rows > 0) {
                            $message = "Office hours deleted successfully for selected faculty.";
                            $messageType = "success";
                        } else {
                            $message = "Office hours not found or could not be deleted for selected faculty.";
                            $messageType = "danger";
                        }
                        $stmt->close();
                    } else {
                        $message = "Invalid office hour ID for deletion.";
                        $messageType = "danger";
                    }
                    break;
                default:
                    $message = "Invalid action specified.";
                    $messageType = "danger";
                    break;
            }
        } catch (mysqli_sql_exception $e) {
            error_log("Database error in admin/manage_faculty_office_hours.php: " . $e->getMessage());
            if ($e->getCode() == 1062) {
                $message = "Duplicate office hours entry for this faculty member. Please check the existing entries.";
            } else {
                $message = "An unexpected database error occurred. Please try again.";
            }
            $messageType = "danger";
        }
    }
}

// --- Fetch all faculty members for the dropdown ---
$facultyMembers = [];
try {
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE role = 'faculty' ORDER BY name");
    $stmt->execute();
    $result = $stmt->get_result();
    $facultyMembers = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    error_log("Database error fetching faculty members: " . $e->getMessage());
    $message = "Could not load faculty list.";
    $messageType = "danger";
}

// --- Fetch office hours for the currently selected faculty ---
$selectedFacultyId = filter_input(INPUT_GET, 'faculty_id', FILTER_VALIDATE_INT); // From GET if navigating directly
if (!$selectedFacultyId && isset($_POST['selected_faculty_id'])) {
    $selectedFacultyId = filter_input(INPUT_POST, 'selected_faculty_id', FILTER_VALIDATE_INT); // From POST after submission
}
$selectedFacultyOfficeHours = [];
$selectedFacultyName = '';

if ($selectedFacultyId) {
    try {
        // Fetch selected faculty's name
        $stmt = $conn->prepare("SELECT name FROM users WHERE id = ? AND role = 'faculty'");
        $stmt->bind_param("i", $selectedFacultyId);
        $stmt->execute();
        $facultyResult = $stmt->get_result();
        if ($facultyRow = $facultyResult->fetch_assoc()) {
            $selectedFacultyName = htmlspecialchars($facultyRow['name']);
        } else {
            $selectedFacultyId = null; // Invalid faculty ID, clear selection
            $message = "Selected faculty not found.";
            $messageType = "danger";
        }
        $stmt->close();

        if ($selectedFacultyId) {
            // Fetch office hours for the selected faculty
            $stmt = $conn->prepare("SELECT oh_id, day_of_week, start_time, end_time, location FROM office_hours WHERE faculty_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time");
            $stmt->bind_param("i", $selectedFacultyId);
            $stmt->execute();
            $result = $stmt->get_result();
            $selectedFacultyOfficeHours = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Database error fetching selected faculty's office hours: " . $e->getMessage());
        $message = "Could not load office hours for the selected faculty.";
        $messageType = "danger";
    }
}


include __DIR__ . '/../../templates/header.php';
?>

<div class="container mt-4">
    <h2>Manage Faculty Office Hours</h2>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="mb-4">
        <label for="facultySelect" class="form-label">Select Faculty Member:</label>
        <select class="form-select" id="facultySelect" onchange="location = this.value;">
            <option value="">-- Choose Faculty --</option>
            <?php foreach ($facultyMembers as $faculty): ?>
                <option value="?faculty_id=<?php echo htmlspecialchars($faculty['id']); ?>"
                    <?php echo ($selectedFacultyId == $faculty['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($faculty['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if ($selectedFacultyId): ?>
        <h3 class="mt-4">Office Hours for: <span id="currentFacultyName"><?php echo $selectedFacultyName; ?></span></h3>

        <div class="card p-3 mb-4">
            <h4>Add/Edit Office Hour Slot</h4>
            <form method="POST" action="">
                <input type="hidden" name="selected_faculty_id" value="<?php echo htmlspecialchars($selectedFacultyId); ?>">
                <input type="hidden" id="oh_id_form" name="oh_id" value="">
                <input type="hidden" id="action_form" name="action" value="add"> <div class="mb-3">
                    <label for="day_of_week" class="form-label">Day of Week</label>
                    <select class="form-control" id="day_of_week" name="day_of_week" required>
                        <option value="">Select Day</option>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                        <option value="Sunday">Sunday</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="start_time" class="form-label">Start Time</label>
                    <input type="time" class="form-control" id="start_time" name="start_time" required>
                </div>
                <div class="mb-3">
                    <label for="end_time" class="form-label">End Time</label>
                    <input type="time" class="form-control" id="end_time" name="end_time" required>
                </div>
                <div class="mb-3">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" class="form-control" id="location" name="location" placeholder="e.g., Faculty Office A101, Zoom Link" required>
                </div>
                <button type="submit" class="btn btn-primary" id="submitButton">Add Office Hours</button>
                <button type="button" class="btn btn-secondary" onclick="resetForm()">Clear Form</button>
            </form>
        </div>

        <h3 class="mt-5">Current Office Hours for <?php echo $selectedFacultyName; ?></h3>
        <?php if (empty($selectedFacultyOfficeHours)): ?>
            <div class="alert alert-info">No office hours set for this faculty member yet.</div>
        <?php else: ?>
            <table class="table table-bordered table-striped mt-3">
                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Location</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($selectedFacultyOfficeHours as $oh): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($oh['day_of_week']); ?></td>
                            <td><?php echo htmlspecialchars(date("h:i A", strtotime($oh['start_time']))) . ' - ' . htmlspecialchars(date("h:i A", strtotime($oh['end_time']))); ?></td>
                            <td><?php echo htmlspecialchars($oh['location']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning me-2" onclick="populateFormForEdit(
                                    <?php echo $oh['oh_id']; ?>,
                                    '<?php echo htmlspecialchars($oh['day_of_week'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($oh['start_time'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($oh['end_time'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars(addslashes($oh['location']), ENT_QUOTES); ?>'
                                )">Edit</button>
                                <form action="" method="POST" style="display:inline-block;">
                                    <input type="hidden" name="selected_faculty_id" value="<?php echo htmlspecialchars($selectedFacultyId); ?>">
                                    <input type="hidden" name="oh_id" value="<?php echo htmlspecialchars($oh['oh_id']); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete these office hours for <?php echo $selectedFacultyName; ?>?');">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info mt-4">Please select a faculty member from the dropdown above to manage their office hours.</div>
    <?php endif; ?>
</div>

<?php
include __DIR__ . '/../../templates/footer.php';
?>

<script>
    /**
     * Populates the form fields with existing office hour data for editing.
     * @param {number} ohId - The ID of the office hour record.
     * @param {string} day - The day of the week.
     * @param {string} start - The start time (HH:MM:SS format).
     * @param {string} end - The end time (HH:MM:SS format).
     * @param {string} location - The location of the office hours.
     */
    function populateFormForEdit(ohId, day, start, end, location) {
        document.getElementById('oh_id_form').value = ohId;
        document.getElementById('day_of_week').value = day;
        document.getElementById('start_time').value = start.substring(0, 5); // Trim seconds for time input
        document.getElementById('end_time').value = end.substring(0, 5); // Trim seconds for time input
        document.getElementById('location').value = location;
        document.getElementById('submitButton').innerText = 'Update Office Hours';
        document.getElementById('action_form').value = 'edit'; // Set action to edit
    }

    /**
     * Resets the form fields to their initial state, ready for adding new office hours.
     */
    function resetForm() {
        document.getElementById('oh_id_form').value = '';
        document.getElementById('day_of_week').value = '';
        document.getElementById('start_time').value = '';
        document.getElementById('end_time').value = '';
        document.getElementById('location').value = '';
        document.getElementById('submitButton').innerText = 'Add Office Hours';
        document.getElementById('action_form').value = 'add'; // Set action back to add
    }
</script>