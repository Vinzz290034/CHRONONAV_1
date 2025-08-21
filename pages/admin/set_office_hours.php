<?php
// CHRONONAV_WEB_DOSS/pages/admin/set_office_hours.php

// This page allows an admin user to set and manage their OWN office hours,
// assuming the admin also functions in a capacity that requires them to have
// registered office hours (e.g., they are also a faculty member).

// Include the auth_check middleware first to ensure the user is logged in and session is set.
require_once __DIR__ . '/../../middleware/auth_check.php';
// Include the custom functions, specifically for the requireRole function.
require_once __DIR__ . '/../../includes/functions.php';

// Restrict access to only 'admin' roles for this page.
// An admin can access this to set their own office hours.
requireRole(['admin']);

// The current admin user's ID is retrieved from the session.
$currentAdminId = $_SESSION['user']['id'];
$message = ''; // Variable to store success or error messages for the user.
$messageType = ''; // 'success' or 'danger' to control Bootstrap alert styling.

// $conn is the database connection object, made globally available by db_connect.php (included via auth_check.php).

// --- Handle Form Submission (Add/Edit Office Hours) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve form input data.
    $dayOfWeek = filter_input(INPUT_POST, 'day_of_week', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $startTime = filter_input(INPUT_POST, 'start_time', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $endTime = filter_input(INPUT_POST, 'end_time', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $ohId = filter_input(INPUT_POST, 'oh_id', FILTER_VALIDATE_INT); // Validate as integer for edit operations.

    // Basic server-side validation to ensure all required fields are present.
    if (empty($dayOfWeek) || empty($startTime) || empty($endTime) || empty($location)) {
        $message = "All fields are required to set office hours.";
        $messageType = "danger";
    } else {
        try {
            if ($ohId) {
                // --- Update Existing Office Hours ---
                $stmt = $conn->prepare("UPDATE office_hours SET day_of_week = ?, start_time = ?, end_time = ?, location = ?, updated_at = CURRENT_TIMESTAMP WHERE oh_id = ? AND faculty_id = ?");
                // 'sssisi' specifies the types of parameters: string, string, string, integer, integer.
                // We use faculty_id in the WHERE clause as the 'office_hours' table uses 'faculty_id' to link to the 'users' table.
                $stmt->bind_param("sssisi", $dayOfWeek, $startTime, $endTime, $location, $ohId, $currentAdminId);
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $message = "Your office hours updated successfully!";
                    $messageType = "success";
                } else {
                    $message = "No changes were made to your office hours, or the record could not be found/updated.";
                    $messageType = "info";
                }
            } else {
                // --- Insert New Office Hours ---
                $stmt = $conn->prepare("INSERT INTO office_hours (faculty_id, day_of_week, start_time, end_time, location) VALUES (?, ?, ?, ?, ?)");
                // 'issss' specifies the types of parameters: integer, string, string, string, string.
                // We insert using the current admin's ID as their faculty_id.
                $stmt->bind_param("issss", $currentAdminId, $dayOfWeek, $startTime, $endTime, $location);
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $message = "Your office hours added successfully!";
                    $messageType = "success";
                } else {
                    $message = "Failed to add your office hours. Please try again.";
                    $messageType = "danger";
                }
            }
            $stmt->close(); // Close the statement after execution.
        } catch (mysqli_sql_exception $e) {
            // Log the detailed error message for debugging (check your web server's error logs).
            error_log("Database error in admin/set_office_hours.php: " . $e->getMessage());

            // Check for specific MySQL error code for duplicate entry (e.g., if UNIQUE KEY constraint is hit).
            if ($e->getCode() == 1062) {
                $message = "You already have office hours set for this specific time and day. Please choose a different slot or edit the existing one.";
                $messageType = "danger";
            } else {
                $message = "An unexpected error occurred while saving your office hours. Please try again later.";
                $messageType = "danger";
            }
        }
    }
}

// --- Fetch Current Office Hours for Display ---
$officeHours = []; // Initialize an empty array to hold fetched office hours.
try {
    // Fetch all office hours for the current admin user (based on their ID being in faculty_id).
    // The FIELD() function is a MySQL-specific extension to order by a custom list of values.
    $stmt = $conn->prepare("SELECT oh_id, day_of_week, start_time, end_time, location FROM office_hours WHERE faculty_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time");
    $stmt->bind_param("i", $currentAdminId); // 'i' for integer parameter.
    $stmt->execute();
    $result = $stmt->get_result();
    $officeHours = $result->fetch_all(MYSQLI_ASSOC); // Fetch all results as an associative array.
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    // Log the error if fetching existing office hours fails.
    error_log("Database error fetching existing office hours in admin/set_office_hours.php: " . $e->getMessage());
    $message = "Error loading your current office hours. Please try refreshing the page.";
    $messageType = "danger";
}

// Include the common header template. Adjust path as necessary.
include __DIR__ . '/../../templates/header.php';
?>

<div class="container mt-4">
    <h2>Set My Office Hours (Admin)</h2>

    <?php if ($message): // Display messages if any ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" id="oh_id" name="oh_id" value=""> <div class="mb-3">
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
            <input type="text" class="form-control" id="location" name="location" placeholder="e.g., Office A101, Zoom Link, Online" required>
        </div>
        <button type="submit" class="btn btn-primary" id="submitButton">Add Office Hours</button>
        <button type="button" class="btn btn-secondary" onclick="resetForm()">Clear Form</button>
    </form>

    <h3 class="mt-5">My Current Office Hours</h3>
    <?php if (empty($officeHours)): ?>
        <div class="alert alert-info">No office hours set yet. Use the form above to add some!</div>
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
                <?php foreach ($officeHours as $oh): ?>
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
                                '<?php echo htmlspecialchars(addslashes($oh['location']), ENT_QUOTES); ?>' // Use addslashes for JS string literal safety
                            )">Edit</button>
                            <form action="delete_office_hours.php" method="POST" style="display:inline-block;">
                                <input type="hidden" name="oh_id" value="<?php echo htmlspecialchars($oh['oh_id']); ?>">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete these office hours?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
// Include the common footer template. Adjust path as necessary.
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
    document.getElementById('oh_id').value = ohId;
    document.getElementById('day_of_week').value = day;
    document.getElementById('start_time').value = start.substring(0, 5); // Trim seconds for time input
    document.getElementById('end_time').value = end.substring(0, 5); // Trim seconds for time input
    document.getElementById('location').value = location;
    document.getElementById('submitButton').innerText = 'Update Office Hours'; // Change button text
}

/**
 * Resets the form fields to their initial state, ready for adding new office hours.
 */
function resetForm() {
    document.getElementById('oh_id').value = ''; // Clear hidden ID field
    document.getElementById('day_of_week').value = ''; // Reset select to default option
    document.getElementById('start_time').value = '';
    document.getElementById('end_time').value = '';
    document.getElementById('location').value = '';
    document.getElementById('submitButton').innerText = 'Add Office Hours'; // Revert button text
}
</script>