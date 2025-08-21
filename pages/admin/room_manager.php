<?php
// CHRONONAV_WEB_DOSS/pages/admin/room_manager.php

require_once '../../middleware/auth_check.php'; // Ensures user is logged in and session is started
require_once '../../config/db_connect.php'; // Database connection
require_once '../../includes/functions.php'; // Assuming requireRole function is here

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

$page_title = "Building Room Manager";
$current_page = "room_manager"; // This should match a key in your sidenav for active state

$message = '';
$message_type = '';

// --- Handle Add/Update Room ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_room'])) {
    $room_id = $_POST['room_id'] ?? null;
    $room_name = trim($_POST['room_name'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 0);
    $room_type = $_POST['room_type'] ?? 'Classroom';
    $equipment = trim($_POST['equipment'] ?? '');
    $location_description = trim($_POST['location_description'] ?? '');

    if (empty($room_name)) {
        $message = "Room Name is required.";
        $message_type = 'danger';
    } else {
        if ($room_id) { // Update existing room
            $stmt = $conn->prepare("UPDATE rooms SET room_name = ?, capacity = ?, room_type = ?, equipment = ?, location_description = ?, updated_at = NOW() WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("sisssi", $room_name, $capacity, $room_type, $equipment, $location_description, $room_id);
                if ($stmt->execute()) {
                    $message = "Room updated successfully!";
                    $message_type = 'success';
                } else {
                    $message = "Error updating room: " . $stmt->error;
                    $message_type = 'danger';
                }
                $stmt->close();
            }
        } else { // Add new room
            $stmt = $conn->prepare("INSERT INTO rooms (room_name, capacity, room_type, equipment, location_description) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sisss", $room_name, $capacity, $room_type, $equipment, $location_description);
                if ($stmt->execute()) {
                    $message = "Room added successfully!";
                    $message_type = 'success';
                } else {
                    $message = "Error adding room: " . $stmt->error;
                    // Check for duplicate entry error
                    if ($conn->errno == 1062) { // MySQL error code for duplicate entry
                        $message = "Error: A room with this name already exists.";
                    }
                    $message_type = 'danger';
                }
                $stmt->close();
            }
        }
    }
}

// --- Handle Delete Room ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_room'])) {
    $room_id_to_delete = $_POST['delete_room_id'] ?? null;
    if ($room_id_to_delete) {
        $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $room_id_to_delete);
            if ($stmt->execute()) {
                $message = "Room deleted successfully!";
                $message_type = 'success';
            } else {
                $message = "Error deleting room: " . $stmt->error;
                $message_type = 'danger';
            }
            $stmt->close();
        }
    }
}

// --- Handle Linking Room to Schedule ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['link_schedule'])) {
    $schedule_id_to_link = $_POST['schedule_id_to_link'] ?? null;
    $selected_room_id = $_POST['selected_room_id'] ?? null;

    if ($schedule_id_to_link && ($selected_room_id !== null)) { // Allow selected_room_id to be 0 or NULL to unlink
        $stmt = $conn->prepare("UPDATE schedules SET room_id = ? WHERE schedule_id = ?");
        if ($stmt) {
            // If $selected_room_id is "0" or empty, treat it as NULL for the database
            // This assumes the room_id column in the schedules table is nullable.
            $bind_room_id = ($selected_room_id == "0" || empty($selected_room_id)) ? NULL : (int)$selected_room_id;
            
            
            // A safer way for nullable INT in mysqli where 0 indicates unlinked:
            $stmt->bind_param("ii", $bind_room_id, $schedule_id_to_link); 

            if ($stmt->execute()) {
                $message = "Schedule linked/updated with room successfully!";
                $message_type = 'success';
            } else {
                $message = "Error linking schedule to room: " . $stmt->error;
                $message_type = 'danger';
            }
            $stmt->close();
        }
    } else {
        $message = "Invalid data for linking schedule.";
        $message_type = 'danger';
    }
}


// --- Fetch all Rooms ---
$rooms = [];
$stmt_rooms = $conn->prepare("SELECT * FROM rooms ORDER BY room_name ASC");
$stmt_rooms->execute();
$result_rooms = $stmt_rooms->get_result();
while ($row = $result_rooms->fetch_assoc()) {
    $rooms[] = $row;
}
$stmt_rooms->close();

// --- Fetch all Schedules (and their current room_id) ---
$schedules = [];
// Joining with rooms to get room_name for display
$stmt_schedules = $conn->prepare("SELECT s.*, r.room_name FROM schedules s LEFT JOIN rooms r ON s.room_id = r.id ORDER BY s.day_of_week, s.start_time");
$stmt_schedules->execute();
$result_schedules = $stmt_schedules->get_result();
while ($row = $result_schedules->fetch_assoc()) {
    $schedules[] = $row;
}
$stmt_schedules->close();

// --- START HTML STRUCTURE ---
// Include the admin header which contains <head> and opening <body> tags
require_once '../../templates/admin/header_admin.php';
require_once '../../templates/admin/sidenav_admin.php'; // Sidenav is included here
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<link rel="stylesheet" href="../../assets/css/admin_css/building_room_manager.css">

<div class="main-content-wrapper">
    <div class="main-dashboard-content room-manager-page">
        <div class="room-manager-header">
            <h1>Building Room Manager</h1>
            </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="room-section room-form-section card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="card-title"><i class="fas fa-plus-circle"></i> Add/Update Room</h2>
                <form action="room_manager.php" method="POST" class="room-form">
                    <input type="hidden" name="room_id" id="room_id">
                    <div class="row">
                        <div class="form-group col-md-6 mb-3">
                            <label for="room_name">Room Name <span class="required">*</span>:</label>
                            <input type="text" id="room_name" name="room_name" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6 mb-3">
                            <label for="capacity">Capacity:</label>
                            <input type="number" id="capacity" name="capacity" class="form-control" min="1">
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6 mb-3">
                            <label for="room_type">Room Type:</label>
                            <select id="room_type" name="room_type" class="form-select">
                                <option value="Classroom">Classroom</option>
                                <option value="Laboratory">Laboratory</option>
                                <option value="Lecture Hall">Lecture Hall</option>
                                <option value="Office">Office</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6 mb-3">
                            <label for="equipment">Equipment (comma-separated):</label>
                            <input type="text" id="equipment" name="equipment" class="form-control" placeholder="e.g., Projector, Whiteboard, AC">
                        </div>
                    </div>
                    <div class="form-group mb-4">
                        <label for="location_description">Location Description:</label>
                        <textarea id="location_description" name="location_description" class="form-control" rows="3" placeholder="e.g., 3rd Floor, Main Building"></textarea>
                    </div>
                    <button type="submit" name="submit_room" class="btn btn-primary"><i class="fas fa-save"></i> Save Room</button>
                    <button type="button" class="btn btn-secondary ms-2" onclick="clearRoomForm()"><i class="fas fa-eraser"></i> Clear Form</button>
                </form>
            </div>
        </div>

        <div class="room-section room-list-section mt-4 card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="card-title"><i class="fas fa-list-ul"></i> Existing Rooms</h2>
                <?php if (!empty($rooms)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped room-list-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Room Name</th>
                                    <th>Capacity</th>
                                    <th>Type</th>
                                    <th>Equipment</th>
                                    <th>Location</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rooms as $room): ?>
                                <tr>
                                    <td><?= htmlspecialchars($room['id']) ?></td>
                                    <td><?= htmlspecialchars($room['room_name']) ?></td>
                                    <td><?= htmlspecialchars($room['capacity'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($room['room_type']) ?></td>
                                    <td><?= htmlspecialchars($room['equipment'] ?? 'None') ?></td>
                                    <td><?= htmlspecialchars($room['location_description'] ?? 'N/A') ?></td>
                                    <td class="d-flex gap-2">
                                        <button class="btn btn-sm btn-info edit-room-btn"
                                                data-id="<?= htmlspecialchars($room['id']) ?>"
                                                data-name="<?= htmlspecialchars($room['room_name']) ?>"
                                                data-capacity="<?= htmlspecialchars($room['capacity'] ?? '') ?>"
                                                data-type="<?= htmlspecialchars($room['room_type']) ?>"
                                                data-equipment="<?= htmlspecialchars($room['equipment'] ?? '') ?>"
                                                data-location="<?= htmlspecialchars($room['location_description'] ?? '') ?>">
                                                <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form action="room_manager.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this room? This will unlink it from schedules.');">
                                            <input type="hidden" name="delete_room_id" value="<?= htmlspecialchars($room['id']) ?>">
                                            <button type="submit" name="delete_room" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i> Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">No rooms defined yet. Add a new room above.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="room-section link-schedules-section mt-4 card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="card-title"><i class="fas fa-link"></i> Link Rooms to Schedules</h2>
                <?php if (!empty($schedules)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped schedule-link-table">
                            <thead>
                                <tr>
                                    <th>Schedule ID</th>
                                    <th>Title</th>
                                    <th>Day</th>
                                    <th>Time</th>
                                    <th>Current Room</th>
                                    <th>Link/Change Room</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules as $schedule): ?>
                                <tr>
                                    <td><?= htmlspecialchars($schedule['schedule_id']) ?></td>
                                    <td><?= htmlspecialchars($schedule['title']) ?></td>
                                    <td><?= htmlspecialchars($schedule['day_of_week']) ?></td>
                                    <td><?= htmlspecialchars(date('h:i A', strtotime($schedule['start_time']))) ?> - <?= htmlspecialchars(date('h:i A', strtotime($schedule['end_time']))) ?></td>
                                    <td>
                                        <?php if (!empty($schedule['room_name'])): ?>
                                            <span class="badge bg-primary"><?= htmlspecialchars($schedule['room_name']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form action="room_manager.php" method="POST" class="d-flex align-items-center">
                                            <input type="hidden" name="schedule_id_to_link" value="<?= htmlspecialchars($schedule['schedule_id']) ?>">
                                            <select name="selected_room_id" class="form-select form-select-sm me-2">
                                                <option value="0">-- Unassign Room --</option>
                                                <?php foreach ($rooms as $room): ?>
                                                    <option value="<?= htmlspecialchars($room['id']) ?>"
                                                            <?= ($schedule['room_id'] == $room['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($room['room_name']) ?> (Cap: <?= htmlspecialchars($room['capacity']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="link_schedule" class="btn btn-sm btn-success"><i class="fas fa-link"></i> Set</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">No schedules found to link rooms to.</div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script src="../../assets/js/jquery.min.js"></script>
<script src="../../assets/js/script.js"></script>
<script>
    // JavaScript for Edit Room button to populate form
    document.querySelectorAll('.edit-room-btn').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('room_id').value = this.dataset.id;
            document.getElementById('room_name').value = this.dataset.name;
            document.getElementById('capacity').value = this.dataset.capacity;
            document.getElementById('room_type').value = this.dataset.type;
            document.getElementById('equipment').value = this.dataset.equipment;
            document.getElementById('location_description').value = this.dataset.location;
            // Scroll to the form
            document.querySelector('.room-form-section').scrollIntoView({ behavior: 'smooth' });
        });
    });

    // JavaScript to clear the form
    function clearRoomForm() {
        document.getElementById('room_id').value = '';
        document.getElementById('room_name').value = '';
        document.getElementById('capacity').value = '';
        document.getElementById('room_type').value = 'Classroom'; // Reset to default
        document.getElementById('room_type').dispatchEvent(new Event('change')); // Trigger change if needed for styling
        document.getElementById('equipment').value = '';
        document.getElementById('location_description').value = '';
    }
</script>

<?php
// Include the common footer which closes <body> and <html> and includes common JS
include_once '../../templates/footer.php';
?>
