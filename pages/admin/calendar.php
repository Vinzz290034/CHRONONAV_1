<?php
// pages/admin/calendar.php
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

requireRole(['admin']);

$user = $_SESSION['user'];
$current_user_id = $user['id'];

// --- Fetch fresh user data for display in header and profile sections ---
$stmt_user_data = $conn->prepare("SELECT name, email, profile_img, role FROM users WHERE id = ?");
if ($stmt_user_data) {
    $stmt_user_data->bind_param("i", $current_user_id);
    $stmt_user_data->execute();
    $result_user_data = $stmt_user_data->get_result();
    if ($result_user_data->num_rows > 0) {
        $user_from_db = $result_user_data->fetch_assoc();
        $_SESSION['user'] = array_merge($_SESSION['user'], $user_from_db);
        $user = $_SESSION['user'];
    } else {
        error_log("Security Alert: User ID {$current_user_id} in session not found in database for calendar (user).");
        session_destroy();
        header('Location: ../../auth/login.php?error=user_not_found');
        exit();
    }
    $stmt_user_data->close();
} else {
    error_log("Database query preparation failed for calendar (user): " . $conn->error);
}

$display_username = htmlspecialchars($user['name'] ?? 'Guest');
$display_user_role = htmlspecialchars(ucfirst($user['role'] ?? 'Admin'));
$display_profile_img = htmlspecialchars($user['profile_img'] ?? 'uploads/profiles/default-avatar.png');
$profile_img_src = (strpos($display_profile_img, 'uploads/') === 0) ? '../../' . $display_profile_img : $display_profile_img;

$page_title = "My Academic Calendar";
$current_page = "admin_schedule";

$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// --- Handle Actions: Delete User's Personal Event ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_event'])) {
    $user_event_id = $_POST['user_event_id'] ?? null;

    if (empty($user_event_id)) {
        $_SESSION['message'] = "Event ID is required to delete.";
        $_SESSION['message_type'] = 'danger';
    } else {
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

// --- Fetch ALL Public Events from calendar_events table ---
$public_events = [];
$stmt_public_events = $conn->prepare("SELECT id, event_name, description, start_date, end_date, location, event_type FROM calendar_events ORDER BY start_date ASC");
if ($stmt_public_events) {
    $stmt_public_events->execute();
    $result_public_events = $stmt_public_events->get_result();
    while ($row = $result_public_events->fetch_assoc()) {
        $row['source_type'] = 'public'; // Add a source type to identify public events
        $public_events[] = $row;
    }
    $stmt_public_events->close();
} else {
    error_log("Database error fetching public events: " . $conn->error);
}

// --- Fetch User's Personal and Saved Admin Calendar Events ---
$user_events = [];
$stmt_user_events = $conn->prepare("SELECT id, event_name, description, start_date, end_date, location, event_type FROM user_calendar_events WHERE user_id = ? ORDER BY start_date ASC");
if ($stmt_user_events) {
    $stmt_user_events->bind_param("i", $current_user_id);
    $stmt_user_events->execute();
    $result_user_events = $stmt_user_events->get_result();
    while ($row = $result_user_events->fetch_assoc()) {
        $row['source_type'] = 'personal'; // Add a source type to identify personal/saved events
        $user_events[] = $row;
    }
    $stmt_user_events->close();
} else {
    error_log("Database error fetching user's personal events: " . $conn->error);
}

// Now, combine and sort all events
$events = array_merge($public_events, $user_events);
usort($events, function ($a, $b) {
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
}
uksort($grouped_events, function ($a, $b) {
    return strtotime($a . ' 1') - strtotime($b . ' 1');
});
?>

<?php require_once '../../templates/admin/header_admin.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'ChronoNav - Admin Calendar' ?></title>

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
            background-color: #fff;
            min-height: 100vh;
        }

        .layout-container {
            min-height: 100vh;
        }

        .sched.main-content-wrapper {
            margin-left: 20%;
            transition: margin-left 0.3s ease;
        }

        .main-dashboard-content {
            font-family: "Space Grotesk", "Noto Sans", sans-serif;
            max-width: 100%;
            height: 100vh;
        }

        .dashboard-header h2 {
            color: #0e151b;
            font-size: 28px;
            margin-bottom: 1.5rem;
        }

        .card {
            background-color: #ffffff;
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid #d1dce6;
            padding: 1.5rem;
        }

        .card-header h5 {
            color: #0e151b;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.015em;
            margin: 0;
        }

        .card-body {
            padding: 1.5rem;
        }

        .table {
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 0;
        }

        .table th {
            background-color: #f8fafb;
            color: #0e151b;
            font-weight: 600;
            border-bottom: 1px solid #d1dce6;
            padding: 1rem;
        }

        .table td {
            border-bottom: 1px solid #f1f1f1;
            color: #0e151b;
            padding: 1rem;
            vertical-align: middle;
        }

        .table-hover tbody tr:hover {
            background-color: #f8fafb;
        }

        .btn-primary {
            background-color: #1d7dd7;
            border-color: #1d7dd7;
            color: #f8fafb;
            font-weight: 600;
            letter-spacing: 0.015em;
            padding: 0.5rem 1rem;
        }

        .btn-primary:hover {
            background-color: #1a6fc0;
            border-color: #1a6fc0;
        }

        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #000000;
            font-weight: 600;
            letter-spacing: 0.015em;
            padding: 0.5rem 1rem;
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            color: #f8fafb;
            font-weight: 600;
            letter-spacing: 0.015em;
            padding: 0.5rem 1rem;
        }

        .btn-sm {
            height: 32px;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        .alert {
            border: none;
            border-radius: 8px;
            padding: 1rem 1.25rem;
            margin-bottom: 1rem;
        }

        .alert-info {
            background-color: #cff4fc;
            color: #055160;
        }

        .alert-success {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .alert-warning {
            background-color: #fff3cd;
            color: #664d03;
        }

        .event-actions {
            white-space: nowrap;
        }

        .event-actions .btn {
            margin-right: 0.5rem;
        }

        .event-actions .btn:last-child {
            margin-right: 0;
        }

        .calendar-event-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 1rem;
            border-bottom: 1px solid #f1f1f1;
        }

        .event-details {
            flex: 1;
        }

        .event-details h6 {
            color: #0e151b;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .event-type-badge {
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge.Academic {
            background-color: #1d7dd7;
        }

        .badge.Holiday {
            background-color: #198754;
        }

        .badge.Workshop {
            background-color: #6f42c1;
        }

        .badge.Personal {
            background-color: #fd7e14;
        }

        .badge.Other {
            background-color: #6c757d;
        }

        .form-control {
            background-color: #f8fafb;
            border-color: #d1dce6;
            color: #0e151b;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #d1dce6;
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

        @media (max-width: 768px) {
            .sched.main-content-wrapper {
                margin-left: 0;
            }

            .main-dashboard-content {
                padding: 1rem;
            }

            .calendar-event-item {
                flex-direction: column;
            }

            .event-actions {
                white-space: normal;
                margin-top: 1rem;
            }

            .event-actions .btn {
                margin-bottom: 0.5rem;
                display: block;
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <?php require_once '../../templates/admin/sidenav_admin.php'; ?>

    <div class="sched main-content-wrapper">
        <div class="main-dashboard-content p-4">
            <div class="dashboard-header d-flex justify-content-between align-items-center">
                <h2 class="fs-3 fw-bold"><?= $page_title ?></h2>
                <button type="button" class="btn btn-primary d-flex align-items-center gap-2 mb-3"
                    data-bs-toggle="modal" data-bs-target="#addEventModal">
                    <i class="fas fa-plus"></i> Add Public Event
                </button>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="calendar-events-list">
                <?php if (empty($grouped_events)): ?>
                    <div class="alert alert-info">There are no academic events scheduled yet.</div>
                <?php else: ?>
                    <?php foreach ($grouped_events as $month_year => $month_events): ?>
                        <div class="card shadow-sm mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><?= $month_year ?></h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($month_events as $event): ?>
                                        <div class="list-group-item calendar-event-item"
                                            data-event-id="<?= htmlspecialchars($event['id']) ?>"
                                            data-event-name="<?= htmlspecialchars($event['event_name']) ?>"
                                            data-description="<?= htmlspecialchars($event['description']) ?>"
                                            data-start-date="<?= htmlspecialchars($event['start_date']) ?>"
                                            data-end-date="<?= htmlspecialchars($event['end_date']) ?>"
                                            data-location="<?= htmlspecialchars($event['location']) ?>"
                                            data-event-type="<?= htmlspecialchars($event['event_type']) ?>"
                                            data-source-type="<?= htmlspecialchars($event['source_type']) ?>">
                                            <div class="event-details">
                                                <h6>
                                                    <?= htmlspecialchars($event['event_name']) ?>
                                                    <span
                                                        class="badge event-type-badge <?= htmlspecialchars($event['event_type']) ?>">
                                                        <?= htmlspecialchars($event['event_type']) ?>
                                                    </span>
                                                </h6>
                                                <p class="mb-1 text-muted">
                                                    <i class="far fa-calendar-alt"></i>
                                                    <?= date('M d, Y, H:i A', strtotime($event['start_date'])) ?>
                                                    <?php if (date('Y-m-d', strtotime($event['start_date'])) !== date('Y-m-d', strtotime($event['end_date']))): ?>
                                                        - <?= date('M d, Y, H:i A', strtotime($event['end_date'])) ?>
                                                    <?php endif; ?>
                                                </p>
                                                <?php if (!empty($event['description'])): ?>
                                                    <p class="mb-1"><?= nl2br(htmlspecialchars($event['description'])) ?></p>
                                                <?php endif; ?>
                                                <?php if (!empty($event['location'])): ?>
                                                    <p class="mb-0"><i class="fas fa-map-marker-alt"></i>
                                                        <?= htmlspecialchars($event['location']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="event-actions">
                                                <?php if ($event['source_type'] === 'public'): ?>
                                                    <button type="button" class="btn btn-sm btn-warning edit-event-btn"
                                                        data-bs-toggle="modal" data-bs-target="#editEventModal"
                                                        data-id="<?= htmlspecialchars($event['id']) ?>" data-source="public">
                                                        <i class="fas fa-edit"></i> Edit Public Event
                                                    </button>
                                                    <form action="delete_public_event.php" method="POST" class="d-inline"
                                                        onsubmit="return confirm('Are you sure you want to permanently delete this public event?');">
                                                        <input type="hidden" name="event_id"
                                                            value="<?= htmlspecialchars($event['id']) ?>">
                                                        <button type="submit" name="delete_event" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash-alt"></i> Delete Public Event
                                                        </button>
                                                    </form>
                                                <?php elseif ($event['source_type'] === 'personal'): ?>
                                                    <form action="calendar.php" method="POST" class="d-inline"
                                                        onsubmit="return confirm('Are you sure you want to remove this event from your calendar?');">
                                                        <input type="hidden" name="user_event_id"
                                                            value="<?= htmlspecialchars($event['id']) ?>">
                                                        <button type="submit" name="delete_event" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash-alt"></i> Remove
                                                        </button>
                                                    </form>
                                                    <button type="button" class="btn btn-sm btn-warning edit-event-btn"
                                                        data-bs-toggle="modal" data-bs-target="#editEventModal"
                                                        data-id="<?= htmlspecialchars($event['id']) ?>" data-source="personal">
                                                        <i class="fas fa-edit"></i> Edit My Event
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Event Modal -->
    <div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addEventModalLabel">Add New Public Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addEventForm" action="../../includes/add_event_handler.php" method="POST">
                        <input type="hidden" name="source_type" value="public">
                        <div class="mb-3">
                            <label for="addEventName" class="form-label">Event Name</label>
                            <input type="text" class="form-control" id="addEventName" name="event_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="addDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="addDescription" name="description"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="addStartDate" class="form-label">Start Date & Time</label>
                            <input type="datetime-local" class="form-control" id="addStartDate" name="start_date"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="addEndDate" class="form-label">End Date & Time</label>
                            <input type="datetime-local" class="form-control" id="addEndDate" name="end_date">
                        </div>
                        <div class="mb-3">
                            <label for="addLocation" class="form-label">Location</label>
                            <input type="text" class="form-control" id="addLocation" name="location">
                        </div>
                        <div class="mb-3">
                            <label for="addEventType" class="form-label">Event Type</label>
                            <select class="form-control" id="addEventType" name="event_type" required>
                                <option value="Academic">Academic</option>
                                <option value="Holiday">Holiday</option>
                                <option value="Workshop">Workshop</option>
                                <option value="Personal">Personal</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="addEventForm" class="btn btn-primary">Add Event</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Event Modal -->
    <div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editEventModalLabel">Edit Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editEventForm" action="../../includes/edit_event_handler.php" method="POST">
                        <input type="hidden" id="editEventId" name="event_id">
                        <input type="hidden" id="editSourceType" name="source_type">
                        <div class="mb-3">
                            <label for="editEventName" class="form-label">Event Name</label>
                            <input type="text" class="form-control" id="editEventName" name="event_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editDescription" name="description"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="editStartDate" class="form-label">Start Date & Time</label>
                            <input type="datetime-local" class="form-control" id="editStartDate" name="start_date"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="editEndDate" class="form-label">End Date & Time</label>
                            <input type="datetime-local" class="form-control" id="editEndDate" name="end_date">
                        </div>
                        <div class="mb-3">
                            <label for="editLocation" class="form-label">Location</label>
                            <input type="text" class="form-control" id="editLocation" name="location">
                        </div>
                        <div class="mb-3">
                            <label for="editEventType" class="form-label">Event Type</label>
                            <select class="form-control" id="editEventType" name="event_type" required>
                                <option value="Academic">Academic</option>
                                <option value="Holiday">Holiday</option>
                                <option value="Workshop">Workshop</option>
                                <option value="Personal">Personal</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="editEventForm" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Handle Edit Modal
            const editEventModal = document.getElementById('editEventModal');
            editEventModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const eventItem = button.closest('.calendar-event-item');

                const eventId = eventItem.dataset.eventId;
                const eventName = eventItem.dataset.eventName;
                const description = eventItem.dataset.description;
                const startDate = eventItem.dataset.startDate;
                const endDate = eventItem.dataset.endDate;
                const location = eventItem.dataset.location;
                const eventType = eventItem.dataset.eventType;
                const sourceType = eventItem.dataset.sourceType;

                const modalTitle = editEventModal.querySelector('.modal-title');
                const eventIdInput = editEventModal.querySelector('#editEventId');
                const eventNameInput = editEventModal.querySelector('#editEventName');
                const descriptionTextarea = editEventModal.querySelector('#editDescription');
                const startDateInput = editEventModal.querySelector('#editStartDate');
                const endDateInput = editEventModal.querySelector('#editEndDate');
                const locationInput = editEventModal.querySelector('#editLocation');
                const eventTypeSelect = editEventModal.querySelector('#editEventType');
                const sourceTypeInput = editEventModal.querySelector('#editSourceType');

                modalTitle.textContent = 'Edit ' + (sourceType === 'public' ? 'Public' : 'My') + ' Event';
                eventIdInput.value = eventId;
                sourceTypeInput.value = sourceType;
                eventNameInput.value = eventName;
                descriptionTextarea.value = description;
                startDateInput.value = startDate;
                endDateInput.value = endDate;
                locationInput.value = location;
                eventTypeSelect.value = eventType;
            });
        });
    </script>

    <?php require_once '../../templates/footer.php'; ?>
</body>

</html>