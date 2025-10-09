<?php
// pages/user/schedule.php
session_start();
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';

// Check if the user is a regular user or faculty
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] !== 'user' && $_SESSION['user']['role'] !== 'faculty')) {
    header("Location: ../../auth/logout.php");
    exit();
}

$user = $_SESSION['user'];

// Page specific variables
$page_title = "My Schedule";
$current_page = "schedule";

// --- Handle Date Selection ---
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$selected_timestamp = strtotime($selected_date);

// Your existing PHP code for fetching schedules and reminders...
$daily_schedules = [];
$daily_reminders = [];

$day_name = date('l', $selected_timestamp);

$stmt_schedules = $conn->prepare("SELECT s.title, s.description, s.start_time, s.end_time, r.room_name
                                       FROM schedules s
                                       LEFT JOIN rooms r ON s.room_id = r.id
                                       WHERE s.user_id = ? AND s.day_of_week = ? ORDER BY s.start_time");
if ($stmt_schedules) {
    $stmt_schedules->bind_param("is", $user['id'], $day_name);
    $stmt_schedules->execute();
    $result_schedules = $stmt_schedules->get_result();
    while ($row = $result_schedules->fetch_assoc()) {
        $daily_schedules[] = $row;
    }
    $stmt_schedules->close();
}

$stmt_reminders = $conn->prepare("SELECT title, description, due_date, due_time, is_completed FROM reminders WHERE user_id = ? AND due_date = ? ORDER BY due_time");
if ($stmt_reminders) {
    $stmt_reminders->bind_param("is", $user['id'], $selected_date);
    $stmt_reminders->execute();
    $result_reminders = $stmt_reminders->get_result();
    while ($row = $result_reminders->fetch_assoc()) {
        $due_datetime = $row['due_date'] . ' ' . $row['due_time'];
        if ($row['is_completed'] == 0 && ($due_datetime > date('Y-m-d H:i:s') || empty($row['due_time']))) {
            $daily_reminders[] = $row;
        }
    }
    $stmt_reminders->close();
}

$all_daily_events = [];

foreach ($daily_schedules as $sched) {
    $all_daily_events[] = [
        'type' => 'schedule',
        'title' => $sched['title'],
        'description' => $sched['description'],
        'time' => $sched['start_time'],
        'end_time' => $sched['end_time'],
        'location' => $sched['room_name'] ?? 'N/A'
    ];
}

foreach ($daily_reminders as $rem) {
    $all_daily_events[] = [
        'type' => 'reminder',
        'title' => $rem['title'],
        'description' => $rem['description'],
        'time' => $rem['due_time'],
        'due_date' => $rem['due_date'],
        'is_completed' => $rem['is_completed']
    ];
}

usort($all_daily_events, function ($a, $b) {
    $timeA = $a['time'] ? strtotime($a['time']) : 0;
    $timeB = $b['time'] ? strtotime($b['time']) : 0;
    return $timeA - $timeB;
});

$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

$header_path = '../../templates/user/header_user.php';
if (isset($user['role'])) {
    if ($user['role'] === 'admin') {
        $header_path = '../../templates/admin/header_admin.php';
    } elseif ($user['role'] === 'faculty') {
        $header_path = '../../templates/faculty/header_faculty.php';
    }
}
require_once $header_path;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'ChronoNav - Schedule' ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link rel="stylesheet" as="style" onload="this.rel='stylesheet'"
        href="https://fonts.googleapis.com/css2?display=swap&family=Noto+Sans:wght@400;500;700;900&family=Space+Grotesk:wght@400;500;700">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon"
        href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png">

    <style>
        body {
            font-family: "Space Grotesk", "Noto Sans", sans-serif;
            background-color: #fff;
        }

        /* Exact styles from the first code */
        .layout-content-container {
            max-width: 80%;
            flex: 1;
            margin: 0 auto;
            margin-left: 20%;
        }

        .nav-tabs-custom {
            border-bottom: 1px solid #cedce8;
            gap: 2rem;
        }

        .nav-tabs-custom .nav-link {
            border: none;
            color: #49749c;
            font-weight: bold;
            font-size: 0.875rem;
            padding: 1rem 0 0.8125rem;
            border-bottom: 3px solid transparent;
        }

        .nav-tabs-custom .nav-link.active {
            color: #0d151c;
            border-bottom: 3px solid #2E78C6;
        }

        .class-item {
            min-height: 72px;
            background-color: #f8fafc;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .class-icon {
            width: 48px;
            height: 48px;
            background-color: #e7edf4;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .floating-btn {
            background-color: #565e64;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            position: fixed;
            bottom: 1.25rem;
            right: 1.25rem;
            color: white;
            text-decoration: none;
        }

        .floating-btn.fw-bold {
            background-color: #2E78C6;
        }

        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* FullCalendar Custom Styling - exact from first code */
        .fc {
            font-family: "Space Grotesk", "Noto Sans", sans-serif;
        }

        .fc-toolbar {
            flex-wrap: wrap;
            gap: 1rem;
        }

        .fc-toolbar-title {
            font-size: 1.25rem;
            font-weight: bold;
            color: #0d151c;
        }

        .fc-button {
            background-color: transparent;
            border: 1px solid #cedce8;
            color: #0d151c;
            font-weight: 500;
            padding: 0.5rem 1rem;
        }

        .fc-button:hover {
            background-color: #f0f2f5;
        }

        .fc-button-primary:not(:disabled).fc-button-active {
            background-color: #2E78C6;
            border-color: #2E78C6;
        }

        .fc-col-header-cell {
            background-color: transparent;
            padding: 0.75rem 0;
        }

        .fc-col-header-cell-cushion {
            color: #0d151c;
            font-weight: bold;
            font-size: 0.8125rem;
            text-decoration: none;
        }

        .fc-daygrid-day-number {
            color: #0d151c;
            font-weight: 500;
            text-decoration: none;
            padding: 0.5rem;
        }

        .fc-daygrid-day.fc-day-today {
            background-color: rgba(11, 128, 238, 0.1);
        }

        .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
            color: white;
            background-color: #2E78C6;
            border-radius: 50%;
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0.25rem;
        }

        .fc-event {
            background-color: #2E78C6;
            border: none;
            border-radius: 4px;
            font-size: 0.75rem;
            padding: 2px 4px;
        }

        .fc-event-title {
            font-weight: 500;
        }

        .fc-daygrid-event-dot {
            border-color: #2E78C6;
        }

        @media (min-width: 768px) {
            .floating-btn {
                position: static;
                width: auto;
                height: auto;
                border-radius: 9999px;
                padding: 0.875rem 1.5rem;
                gap: 1rem;
            }
        }

        /* Additional styles for your PHP functionality */
        .main-dashboard-content {
            padding: 2rem 1rem;
        }

        .alert {
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }

        [type=button]:not(:disabled),
        [type=reset]:not(:disabled),
        [type=submit]:not(:disabled),
        button:not(:disabled) {
            cursor: pointer;
            background-color: #f0f2f5;
            color: #111418;
            font-weight: bold;
            border: none;
            border-radius: 0.75rem;
        }

        /* Calendar Customization */
        button.fc-prev-button.fc-button.fc-button-primary,
        button.fc-next-button.fc-button.fc-button-primary {
            background-color: #fff;
        }

        button.fc-prev-button.fc-button.fc-button-primary:hover,
        button.fc-next-button.fc-button.fc-button-primary:hover {
            background-color: #737373;
        }

        span.fc-icon.fc-icon-chevron-left:hover {
            color: #fff;
        }

        button.fc-today-button.fc-button.fc-button-primary {
            background-color: transparent;
            border-color: transparent;
            color: #212528;
            font-weight: bold;
        }

        .fc-direction-ltr .fc-button-group>.fc-button:not(:last-child) {
            border-bottom-right-radius: 0px;
            border-top-right-radius: 0px;
            background-color: transparent;
            color: #212529;
        }

        .fc-direction-ltr .fc-button-group>.fc-button:hover {
            color: #212529;
        }

        .fc-direction-ltr .fc-button-group>.fc-button:not(:first-child) {
            border-bottom-left-radius: 0px;
            border-top-left-radius: 0px;
            margin-left: -1px;
            background-color: transparent;
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 12px;
            height: 12px;
        }

        ::-webkit-scrollbar-track {
            background: #ffffff;
            /* white track */
        }

        ::-webkit-scrollbar-thumb {
            background-color: #737373;
            /* gray thumb */
            border-radius: 6px;
            border: 3px solid #ffffff;
            /* padding effect with white border */
        }

        ::-webkit-scrollbar-thumb:hover {
            background-color: #2e78c6;
            /* blue on hover */
        }
    </style>
</head>

<body>
    <?php require_once '../../templates/user/sidenav_user.php'; ?>

    <div class="layout-content-container d-flex flex-column mb-5 p-3 px-5 justify-content-end">
        <!-- Header -->
        <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
            <p class="text-dark fw-bold fs-3 mb-0" style="min-width: 288px;">Schedule</p>
        </div>

        <!-- Calendar Section -->
        <div class="mb-4">
            <div id="calendar" class="bg-white rounded shadow-sm p-3"></div>
        </div>

        <!-- Upcoming Classes Section -->
        <h3 class="text-dark fw-bold fs-5 px-0 pb-2 pt-4">Upcoming Classes - <?= date('F d, Y', $selected_timestamp) ?>
        </h3>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <button type="button" class="btn btn-primary px-4 py-2" data-bs-toggle="modal"
                data-bs-target="#addReminderModal" data-date="<?= $selected_date ?>">
                <i class="fas fa-plus"></i> Add Reminder
            </button>
            <button class="btn btn-primary px-4 py-2" onclick="window.print()">
                <i class="fas fa-print"></i> Print Schedule
            </button>
        </div>

        <?php if (empty($all_daily_events)): ?>
            <div class="alert alert-info text-center">
                No events or reminders scheduled for this day.
            </div>
        <?php else: ?>
            <!-- Dynamic Classes from your PHP data -->
            <?php foreach ($all_daily_events as $event): ?>
                <div class="class-item d-flex align-items-center gap-3">
                    <div class="class-icon text-dark">
                        <?php if ($event['type'] === 'schedule'): ?>
                            <i class="fas fa-book-open"></i>
                        <?php elseif ($event['type'] === 'reminder'): ?>
                            <i class="fas fa-bell"></i>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex flex-column justify-content-center">
                        <p class="text-dark fw-medium mb-1 text-truncate"><?= htmlspecialchars($event['title']) ?></p>
                        <p class="text-muted small mb-0 text-truncate-2">
                            <?php if ($event['type'] === 'schedule'): ?>
                                <?= htmlspecialchars(date('h:i A', strtotime($event['time']))) ?> -
                                <?= htmlspecialchars(date('h:i A', strtotime($event['end_time']))) ?>
                                <?php if (!empty($event['location'])): ?>
                                    · <?= htmlspecialchars($event['location']) ?>
                                <?php endif; ?>
                            <?php elseif ($event['type'] === 'reminder'): ?>
                                Due: <?= htmlspecialchars(date('h:i A', strtotime($event['time']))) ?>
                                <?php if (!empty($event['description'])): ?>
                                    · <?= nl2br(htmlspecialchars($event['description'])) ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Floating Action Button -->
        <div class="d-flex justify-content-end overflow-hidden p-0 pt-3">
            <button class="floating-btn fw-bold text-white" data-bs-toggle="modal" data-bs-target="#addReminderModal"
                data-date="<?= $selected_date ?>">
                <i class="fas fa-plus"></i>
                <span class="d-none d-md-inline">Add Schedule</span>
            </button>
        </div>
    </div>

    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const calendarEl = document.getElementById('calendar');

            // Prepare events for FullCalendar from PHP data
            const calendarEvents = [
                <?php foreach ($all_daily_events as $event): ?>
                                                                                                                                                                                                            {
                        title: '<?= addslashes($event['title']) ?>',
                        start: '<?= $selected_date ?>T<?= $event['time'] ?>',
                        <?php if ($event['type'] === 'schedule' && !empty($event['end_time'])): ?>
                                                                                                                                                                                                                                                                                                                                                                                                            end: '<?= $selected_date ?>T<?= $event['end_time'] ?>',
                        <?php endif; ?>
                                                                                                                                                                                                                description: '<?= addslashes($event['type'] === 'schedule' ? $event['location'] : $event['description']) ?>',
                        color: '<?= $event['type'] === 'schedule' ? '   ' : '#ffc107' ?>'
                    },
                <?php endforeach; ?>
            ];

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: calendarEvents,
                eventClick: function (info) {
                    alert('Event: ' + info.event.title + '\nDescription: ' + info.event.extendedProps.description);
                },
                dateClick: function (info) {
                    window.location.href = 'schedule.php?date=' + info.dateStr;
                }
            });

            calendar.render();

            // Handle modal functionality
            const navItems = document.querySelectorAll('.nav-tabs-custom .nav-link');
            const modal = new bootstrap.Modal(document.getElementById('calendarModal'));
            const modalBody = document.getElementById('calendarModalBody');
            const modalTitle = document.getElementById('calendarModalLabel');

            navItems.forEach(item => {
                item.addEventListener('click', function (event) {
                    event.preventDefault();
                    const view = this.getAttribute('data-view');
                    const titleMap = {
                        'month': 'Month View',
                        'week': 'Week View',
                        'day': 'Day View'
                    };
                    modalTitle.textContent = titleMap[view];

                    fetch(`../../includes/fetch_calendar_view.php?view=${view}`)
                        .then(response => response.text())
                        .then(data => {
                            modalBody.innerHTML = data;
                            modal.show();
                        })
                        .catch(error => console.error('Error fetching calendar view:', error));
                });
            });

            // Handle add reminder modal
            const addReminderModal = document.getElementById('addReminderModal');
            addReminderModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const selectedDate = button.getAttribute('data-date');
                const reminderDateInput = document.getElementById('reminderDate');
                if (selectedDate) {
                    reminderDateInput.value = selectedDate;
                }
            });
        });
    </script>

    <!-- Your existing modals -->
    <div class="modal fade" id="calendarModal" tabindex="-1" aria-labelledby="calendarModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="calendarModalLabel">Calendar View</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="calendarModalBody">
                    <p>Loading...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addReminderModal" tabindex="-1" aria-labelledby="addReminderModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addReminderModalLabel">Add New Reminder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addReminderForm" action="../../includes/add_reminder_handler.php" method="POST">
                        <div class="mb-3">
                            <label for="reminderTitle" class="form-label">Title</label>
                            <input type="text" class="form-control" id="reminderTitle" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="reminderDescription" class="form-label">Description (Optional)</label>
                            <textarea class="form-control" id="reminderDescription" name="description"
                                rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="reminderDate" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="reminderDate" name="due_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="reminderTime" class="form-label">Due Time (Optional)</label>
                            <input type="time" class="form-control" id="reminderTime" name="due_time">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="addReminderForm" class="btn btn-primary">Save Reminder</button>
                </div>
            </div>
        </div>
    </div>

    <?php require_once '../../templates/footer.php'; ?>
</body>
<?php include('../../includes/semantics/footer.php'); ?>

</html>