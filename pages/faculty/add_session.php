<?php

//This is has a connected to my_classes.php
// CHRONONAV_WEB_UNO/pages/faculty/add_session.php
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php'; // For requireRole()

requireRole(['faculty']);

$user = $_SESSION['user'];
$page_title = "Add Class Session";
$current_page = "schedule"; // For active sidebar link

$class_id = $_GET['class_id'] ?? 0;
$class_info = null;
$message = '';
$message_type = '';

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Fetch class details to display (and verify faculty owns this class)
if ($class_id > 0) {
    $stmt = $conn->prepare("
        SELECT class_id, class_name, class_code, semester, academic_year, room_id,
               day_of_week, start_time, end_time
        FROM classes
        WHERE class_id = ? AND faculty_id = ?
    ");
    // Handle academic_year fallback if needed for this query as well
    if (!$stmt) {
        error_log("Failed to prepare class info query with academic_year: " . $conn->error);
        $stmt = $conn->prepare("
            SELECT class_id, class_name, class_code, semester, room_id,
                   day_of_week, start_time, end_time
            FROM classes
            WHERE class_id = ? AND faculty_id = ?
        ");
        if (!$stmt) {
            $_SESSION['message'] = "Critical Database Error: Could not prepare class info query.";
            $_SESSION['message_type'] = "danger";
            header("Location: my_classes.php");
            exit();
        }
    }


    $stmt->bind_param("ii", $class_id, $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $class_info = $result->fetch_assoc();
    } else {
        $_SESSION['message'] = "Class not found or you are not assigned to this class.";
        $_SESSION['message_type'] = "danger";
        header("Location: my_classes.php");
        exit();
    }
    $stmt->close();
} else {
    $_SESSION['message'] = "No class ID provided.";
    $_SESSION['message_type'] = "danger";
    header("Location: my_classes.php");
    exit();
}

// Fetch available rooms for the dropdown
$rooms = [];
$stmt_rooms = $conn->prepare("SELECT id, room_name FROM rooms ORDER BY room_name ASC");
if ($stmt_rooms) {
    $stmt_rooms->execute();
    $result_rooms = $stmt_rooms->get_result();
    while ($row = $result_rooms->fetch_assoc()) {
        $rooms[] = $row;
    }
    $stmt_rooms->close();
} else {
    error_log("Error fetching rooms: " . $conn->error);
}


require_once '../../templates/faculty/header_faculty.php';
require_once '../../templates/faculty/sidenav_faculty.php';
?>

<link rel="stylesheet" href="../../assets/css/styles.css">
<style>
    /* Specific styles for this form if needed */
</style>

<div class="main-content-wrapper">
    <div class="main-dashboard-content">
        <div class="dashboard-header">
            <h2><?= $page_title ?></h2>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if ($class_info): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Add Session for: <strong><?= htmlspecialchars($class_info['class_name']) ?> (<?= htmlspecialchars($class_info['class_code']) ?>)</strong></h5>
                    <p class="mb-0 text-muted">Scheduled: <?= htmlspecialchars($class_info['day_of_week'] ?? 'N/A') ?>
                        <?= htmlspecialchars(date('h:i A', strtotime($class_info['start_time'])) ?? 'N/A') ?> -
                        <?= htmlspecialchars(date('h:i A', strtotime($class_info['end_time'])) ?? 'N/A') ?></p>
                </div>
                <div class="card-body">
                    <form action="../../actions/faculty/add_session_action.php" method="POST">
                        <input type="hidden" name="class_id" value="<?= htmlspecialchars($class_info['class_id']) ?>">

                        <div class="form-group">
                            <label for="session_date">Session Date:</label>
                            <input type="date" class="form-control" id="session_date" name="session_date" required value="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="form-group">
                            <label for="actual_start_time">Actual Start Time:</label>
                            <input type="time" class="form-control" id="actual_start_time" name="actual_start_time" value="<?= htmlspecialchars($class_info['start_time'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="actual_end_time">Actual End Time:</label>
                            <input type="time" class="form-control" id="actual_end_time" name="actual_end_time" value="<?= htmlspecialchars($class_info['end_time'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="room_id">Room:</label>
                            <select class="form-control" id="room_id" name="room_id">
                                <option value="">Select Room (Optional)</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?= htmlspecialchars($room['id']) ?>"
                                        <?= (isset($class_info['room_id']) && $class_info['room_id'] == $room['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($room['room_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="notes">Session Notes (Optional):</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Create Session</button>
                        <a href="my_classes.php" class="btn btn-secondary ml-2"><i class="fas fa-arrow-left"></i> Back to My Classes</a>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">Error: Class information could not be loaded.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>

<script src="../../assets/js/jquery.min.js"></script>

<script src="../../assets/js/script.js"></script>