<?php
// CHRONONAV_WEB_UNO/pages/faculty/set_office_hours.php

require_once __DIR__ . '/../../middleware/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole(['faculty']); // Only faculty can set their own hours

$currentFacultyId = $_SESSION['user']['id'];
$message = '';
$messageType = ''; // 'success' or 'danger'

// $conn is available globally from db_connect.php via auth_check.php

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dayOfWeek = $_POST['day_of_week'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    $location = $_POST['location'] ?? '';
    $ohId = $_POST['oh_id'] ?? null; // For editing existing hours

    // Basic validation (add more robust validation as needed, e.g., time format)
    if (empty($dayOfWeek) || empty($startTime) || empty($endTime) || empty($location)) {
        $message = "All fields are required.";
        $messageType = "danger";
    } else {
        try {
            if ($ohId) {
                // Update existing office hours
                $stmt = $conn->prepare("UPDATE office_hours SET day_of_week = ?, start_time = ?, end_time = ?, location = ? WHERE oh_id = ? AND faculty_id = ?");
                $stmt->bind_param("sssisi", $dayOfWeek, $startTime, $endTime, $location, $ohId, $currentFacultyId);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    $message = "Office hours updated successfully!";
                    $messageType = "success";
                } else {
                    $message = "No changes made or office hours not found/authorized.";
                    $messageType = "info";
                }
            } else {
                // Insert new office hours
                $stmt = $conn->prepare("INSERT INTO office_hours (faculty_id, day_of_week, start_time, end_time, location) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $currentFacultyId, $dayOfWeek, $startTime, $endTime, $location);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    $message = "Office hours added successfully!";
                    $messageType = "success";
                } else {
                    $message = "Failed to add office hours.";
                    $messageType = "danger";
                }
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            error_log("Database error setting office hours in set_office_hours.php: " . $e->getMessage());
            // Check for duplicate entry error specifically if unique constraint exists
            if ($e->getCode() == 1062) { // MySQL error code for duplicate entry
                $message = "These office hours already exist for you on this day.";
                $messageType = "danger";
            } else {
                $message = "Error saving office hours. Please try again.";
                $messageType = "danger";
            }
        }
    }
}

// Fetch current office hours for display
$officeHours = [];
try {
    // Note: The FIELD() function for ordering by specific days is MySQL-specific.
    // If you use another database, you might need a different ordering approach.
    $stmt = $conn->prepare("SELECT oh_id, day_of_week, start_time, end_time, location FROM office_hours WHERE faculty_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time");
    $stmt->bind_param("i", $currentFacultyId);
    $stmt->execute();
    $result = $stmt->get_result();
    $officeHours = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    error_log("Database error fetching existing office hours in set_office_hours.php: " . $e->getMessage());
    $message = "Error loading existing office hours.";
    $messageType = "danger";
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="container mt-4">
    <h2>Set My Office Hours</h2>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" id="oh_id" name="oh_id"> <div class="mb-3">
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

    <h3 class="mt-5">My Current Office Hours</h3>
    <?php if (empty($officeHours)): ?>
        <div class="alert alert-info">No office hours set yet.</div>
    <?php else: ?>
        <table class="table table-bordered table-striped">
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
                            <button class="btn btn-sm btn-warning" onclick="populateFormForEdit(
                                <?php echo $oh['oh_id']; ?>,
                                '<?php echo htmlspecialchars($oh['day_of_week'], ENT_QUOTES); ?>',
                                '<?php echo htmlspecialchars($oh['start_time'], ENT_QUOTES); ?>',
                                '<?php echo htmlspecialchars($oh['end_time'], ENT_QUOTES); ?>',
                                '<?php echo htmlspecialchars(addslashes($oh['location']), ENT_QUOTES); ?>' // Escaped for JS string
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
include __DIR__ . '/../../templates/footer.php';
?>
<script>
function populateFormForEdit(ohId, day, start, end, location) {
    document.getElementById('oh_id').value = ohId;
    document.getElementById('day_of_week').value = day;
    document.getElementById('start_time').value = start;
    document.getElementById('end_time').value = end;
    document.getElementById('location').value = location;
    document.getElementById('submitButton').innerText = 'Update Office Hours';
}

function resetForm() {
    document.getElementById('oh_id').value = '';
    document.getElementById('day_of_week').value = '';
    document.getElementById('start_time').value = '';
    document.getElementById('end_time').value = '';
    document.getElementById('location').value = '';
    document.getElementById('submitButton').innerText = 'Add Office Hours';
}
</script>