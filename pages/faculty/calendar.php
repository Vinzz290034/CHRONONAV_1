<?php
// CHRONONAV_WEB_UNO/pages/faculty/calendar.php
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Ensure only faculty can access this page
requireRole(['faculty']);

$user = $_SESSION['user'];
$current_user_id = $user['id'];

// Set page-specific variables for the header and sidenav
$page_title = "My Academic Calendar (Faculty)";
$current_page = "schedule";

$display_username = htmlspecialchars($user['name'] ?? 'Faculty');
$display_user_role = htmlspecialchars($user['role'] ?? 'Faculty');

// Attempt to get profile image path for the header
$profile_img_src = '../../uploads/profiles/default-avatar.png';
if (!empty($user['profile_img']) && file_exists('../../' . $user['profile_img'])) {
    $profile_img_src = '../../' . $user['profile_img'];
}

$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// --- Handle Actions: Delete User's Personal Event or Unsave Admin Event ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_event'])) {
    $user_event_id = $_POST['user_event_id'] ?? null;

    if (empty($user_event_id)) {
        $_SESSION['message'] = "Event ID is required to delete.";
        $_SESSION['message_type'] = 'danger';
    } else {
        // Delete from user_calendar_events table, ensuring the user owns it
        $stmt = $conn->prepare("DELETE FROM user_calendar_events WHERE id = ? AND user_id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $user_event_id, $current_user_id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $_SESSION['message'] = "Event removed from your calendar successfully!";
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = "Error removing event: Event not found or you don't have permission.";
                    $_SESSION['message_type'] = 'warning';
                }
            } else {
                $_SESSION['message'] = "Error removing event: " . $stmt->error;
                $_SESSION['message_type'] = 'danger';
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = "Database error preparing event removal: " . $conn->error;
            $_SESSION['message_type'] = 'danger';
        }
    }
    header("Location: calendar.php");
    exit();
}

// --- Fetch User's Personal and Saved Admin Calendar Events ---
$events = [];

// 1. Fetch public events from the main calendar_events table that are *not* already saved by the user
$stmt_public_events = $conn->prepare("
    SELECT ce.id, ce.event_name, ce.description, ce.start_date, ce.end_date, ce.location, ce.event_type, 'public' as source_type, u.name AS posted_by_name
    FROM calendar_events ce
    LEFT JOIN user_calendar_events uce ON ce.id = uce.calendar_event_id AND uce.user_id = ?
    LEFT JOIN users u ON ce.user_id = u.id
    WHERE uce.id IS NULL
    ORDER BY ce.start_date ASC
");
$stmt_public_events->bind_param("i", $current_user_id);
$stmt_public_events->execute();
$result_public_events = $stmt_public_events->get_result();
while ($row = $result_public_events->fetch_assoc()) {
    $events[] = $row;
}
$stmt_public_events->close();


// 2. Fetch events from the user_calendar_events table (personal events and saved admin events)
$stmt_user_events = $conn->prepare("
    SELECT 
        uce.id, 
        uce.event_name, 
        uce.description, 
        uce.start_date, 
        uce.end_date, 
        uce.location, 
        uce.event_type, 
        'personal' as source_type,
        IFNULL(u_orig.name, u_creator.name) AS posted_by_name,
        CASE WHEN uce.calendar_event_id IS NULL THEN TRUE ELSE FALSE END AS is_personal_event
    FROM user_calendar_events uce
    LEFT JOIN calendar_events ce_orig ON uce.calendar_event_id = ce_orig.id
    LEFT JOIN users u_orig ON ce_orig.user_id = u_orig.id
    LEFT JOIN users u_creator ON uce.user_id = u_creator.id
    WHERE uce.user_id = ?
    ORDER BY uce.start_date ASC
");
$stmt_user_events->bind_param("i", $current_user_id);
$stmt_user_events->execute();
$result_user_events = $stmt_user_events->get_result();
while ($row = $result_user_events->fetch_assoc()) {
    $events[] = $row;
}
$stmt_user_events->close();

// Sort all events by date after combining
usort($events, function($a, $b) {
    return strtotime($a['start_date']) - strtotime($b['start_date']);
});

// Group events by month/year for display
$grouped_events = [];
foreach ($events as $event) {
    $month_year = date('F Y', strtotime($event['start_date']));
    if (!isset($grouped_events[$month_year])) {
        $grouped_events[$month_year] = [];
    }
    $grouped_events[$month_year][] = $event;
    usort($grouped_events[$month_year], function($a, $b) {
        return strtotime($a['start_date']) - strtotime($b['start_date']);
    });
}
uksort($grouped_events, function($a, $b) {
    return strtotime($a . ' 1') - strtotime($b . ' 1');
});

$event_types = ['Personal', 'Other', 'Study Group', 'Appointment'];

?>

<?php
require_once '../../templates/faculty/header_faculty.php';
?>

<?php
require_once '../../templates/faculty/sidenav_faculty.php';
?>

<link rel="stylesheet" href="../../assets/css/faculty_css/calendar.css">

<div class="main-content-wrapper">
    <div class="main-dashboard-content">
        <div class="dashboard-header">
            <h2><?= $page_title ?></h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
                <i class="fas fa-plus"></i> Add My Event
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="calendar-events-list">
            <?php if (empty($grouped_events)): ?>
                <div class="alert alert-info">You have no academic events scheduled yet.</div>
            <?php else: ?>
                <?php foreach ($grouped_events as $month_year => $month_events): ?>
                    <div class="card mb-4 calendar-month-card">
                        <div class="card-header">
                            <h5 class="mb-0"><?= $month_year ?></h5>
                        </div>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($month_events as $event): ?>
                                <li class="list-group-item calendar-event-item">
                                    <div class="event-details">
                                        <h6><?= htmlspecialchars($event['event_name']) ?> <span class="badge event-type-badge <?= htmlspecialchars($event['event_type']) ?>"><?= htmlspecialchars($event['event_type']) ?></span></h6>
                                        <p class="mb-1 text-muted">
                                            <i class="far fa-calendar-alt"></i>
                                            <?= date('M d, Y, h:i A', strtotime($event['start_date'])) ?>
                                            <?php if (date('Y-m-d', strtotime($event['start_date'])) !== date('Y-m-d', strtotime($event['end_date']))): ?>
                                                - <?= date('M d, Y, h:i A', strtotime($event['end_date'])) ?>
                                            <?php endif; ?>
                                        </p>
                                        <?php if (!empty($event['description'])): ?>
                                            <p class="mb-0"><?= nl2br(htmlspecialchars($event['description'])) ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($event['location'])): ?>
                                            <p class="mb-0"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['location']) ?></p>
                                        <?php endif; ?>

                                        <?php if (isset($event['posted_by_name']) && $event['source_type'] === 'public'): ?>
                                            <p class="posted-by-info"><i class="fas fa-user-tie"></i> Posted By: <?= htmlspecialchars($event['posted_by_name']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="event-actions">
                                        <?php if ($event['source_type'] === 'personal'): ?>
                                            <form action="calendar.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this event from your calendar?');">
                                                <input type="hidden" name="user_event_id" value="<?= htmlspecialchars($event['id']) ?>">
                                                <button type="submit" name="delete_event" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i> Remove</button>
                                            </form>
                                            <?php if (isset($event['is_personal_event']) && $event['is_personal_event']): ?>
                                                <button type="button" class="btn btn-sm btn-warning edit-event-btn" data-bs-toggle="modal" data-bs-target="#editEventModal"
                                                    data-event-id="<?= htmlspecialchars($event['id']) ?>"
                                                    data-event-name="<?= htmlspecialchars($event['event_name']) ?>"
                                                    data-event-description="<?= htmlspecialchars($event['description']) ?>"
                                                    data-start-date="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($event['start_date']))) ?>"
                                                    data-end-date="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($event['end_date']))) ?>"
                                                    data-event-location="<?= htmlspecialchars($event['location']) ?>"
                                                    data-event-type="<?= htmlspecialchars($event['event_type']) ?>">
                                                    <i class="fas fa-edit"></i> Edit My Event
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <form action="save_admin_event.php" method="POST" class="d-inline">
                                                <input type="hidden" name="calendar_event_id" value="<?= htmlspecialchars($event['id']) ?>">
                                                <button type="submit" name="save_event" class="btn btn-sm btn-success"><i class="fas fa-plus"></i> Add to My Calendar</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../../includes/add_user_event_handler.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="addEventModalLabel">Add New Academic Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="event_name" class="form-label">Event Name</label>
                        <input type="text" class="form-control" id="event_name" name="event_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="event_description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="event_description" name="event_description" rows="3"></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Start Date & Time</label>
                            <input type="datetime-local" class="form-control" id="start_date" name="start_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="end_date" class="form-label">End Date & Time</label>
                            <input type="datetime-local" class="form-control" id="end_date" name="end_date">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="event_location" class="form-label">Location (Optional)</label>
                        <input type="text" class="form-control" id="event_location" name="event_location">
                    </div>
                    <div class="mb-3">
                        <label for="event_type" class="form-label">Event Type</label>
                        <select class="form-select" id="event_type" name="event_type" required>
                            <?php foreach ($event_types as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Event</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../../includes/edit_user_event_handler.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="editEventModalLabel">Edit My Academic Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="event_id" id="edit_event_id">
                    <div class="mb-3">
                        <label for="edit_event_name" class="form-label">Event Name</label>
                        <input type="text" class="form-control" id="edit_event_name" name="event_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_event_description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="edit_event_description" name="event_description" rows="3"></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_start_date" class="form-label">Start Date & Time</label>
                            <input type="datetime-local" class="form-control" id="edit_start_date" name="start_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_end_date" class="form-label">End Date & Time</label>
                            <input type="datetime-local" class="form-control" id="edit_end_date" name="end_date">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_event_location" class="form-label">Location (Optional)</label>
                        <input type="text" class="form-control" id="edit_event_location" name="event_location">
                    </div>
                    <div class="mb-3">
                        <label for="edit_event_type" class="form-label">Event Type</label>
                        <select class="form-select" id="edit_event_type" name="event_type" required>
                            <?php foreach ($event_types as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Event</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Event listener for populating the edit modal
        const editEventModal = document.getElementById('editEventModal');
        editEventModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const eventId = button.getAttribute('data-event-id');
            const eventName = button.getAttribute('data-event-name');
            const eventDescription = button.getAttribute('data-event-description');
            const startDate = button.getAttribute('data-start-date');
            const endDate = button.getAttribute('data-end-date');
            const eventLocation = button.getAttribute('data-event-location');
            const eventType = button.getAttribute('data-event-type');

            // Populate the form fields
            const form = editEventModal.querySelector('form');
            form.querySelector('#edit_event_id').value = eventId;
            form.querySelector('#edit_event_name').value = eventName;
            form.querySelector('#edit_event_description').value = eventDescription;
            form.querySelector('#edit_start_date').value = startDate;
            form.querySelector('#edit_end_date').value = endDate;
            form.querySelector('#edit_event_location').value = eventLocation;
            form.querySelector('#edit_event_type').value = eventType;
        });
    });
</script>

<?php require_once '../../templates/footer.php'; ?>