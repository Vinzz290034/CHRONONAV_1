<?php
// pages/user/schedule.php
session_start();
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';

// Check if the user is a regular user or faculty
// This allows both regular users and faculty to access the schedule page
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

$current_month = date('m', $selected_timestamp);
$current_year = date('Y', $selected_timestamp);

$first_day_of_month = strtotime($current_year . '-' . $current_month . '-01');
$num_days_in_month = date('t', $first_day_of_month);
$first_day_of_week = date('N', $first_day_of_month); // 1 (Mon) to 7 (Sun)

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
} else {
    error_log("Failed to prepare schedule statement: " . $conn->error);
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
} else {
    error_log("Failed to prepare reminder statement: " . $conn->error);
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

usort($all_daily_events, function($a, $b) {
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

// Adjusted include paths for user-specific templates
require_once '../../templates/user/header_user.php';
require_once '../../templates/user/sidenav_user.php';
?>

<link rel="stylesheet" href="../../assets/css/user_css/schedules.css">

<div class="main-dashboard-content">
    <div class="schedule-calendar-container card p-4 mb-4">
        <div class="calendar-nav-bar">
            <a href="#" class="nav-item" data-bs-toggle="modal" data-bs-target="#calendarModal" data-view="year">Year</a>
            <a href="#" class="nav-item active" data-bs-toggle="modal" data-bs-target="#calendarModal" data-view="month">Month</a>
            <a href="#" class="nav-item" data-bs-toggle="modal" data-bs-target="#calendarModal" data-view="week">Week</a>
            <a href="#" class="nav-item" data-bs-toggle="modal" data-bs-target="#calendarModal" data-view="day">Day</a>
        </div>
        
        <div class="calendar-header">
            <a href="?date=<?= date('Y-m-d', strtotime('-1 month', $first_day_of_month)) ?>" class="calendar-nav-arrow"><i class="fas fa-chevron-left"></i></a>
            <h3><?= date('F Y', $selected_timestamp) ?></h3>
            <a href="?date=<?= date('Y-m-d', strtotime('+1 month', $first_day_of_month)) ?>" class="calendar-nav-arrow"><i class="fas fa-chevron-right"></i></a>
        </div>
        <div class="calendar-days-header">
            <div>S</div>
            <div>M</div>
            <div>T</div>
            <div>W</div>
            <div>T</div>
            <div>F</div>
            <div>S</div>
        </div>
        <div class="calendar-grid">
            <?php
            for ($i = 1; $i < $first_day_of_week; $i++) {
                echo '<div class="calendar-day empty"></div>';
            }
            for ($day = 1; $day <= $num_days_in_month; $day++) {
                $date_obj = new DateTime("{$current_year}-{$current_month}-{$day}");
                $full_date = $date_obj->format('Y-m-d');
                $is_selected = ($full_date === $selected_date) ? 'selected' : '';
                $is_today = ($full_date === date('Y-m-d')) ? 'today' : '';
                echo "<div class='calendar-day {$is_selected} {$is_today}' data-date='{$full_date}'>{$day}</div>";
            }
            ?>
        </div>
    </div>
    <div class="card p-4 upcoming-events-card">
        <h4>Events for <?= date('F d, Y', $selected_timestamp) ?></h4>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addReminderModal" data-date="<?= $selected_date ?>">
                <i class="fas fa-plus"></i> Add Reminder
            </button>
            <a href="calendar.php?date=<?= $selected_date ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-calendar-alt"></i> View Weekly Schedule
            </a>
        </div>
    </div>
    <div class="card p-4 upcoming-events-card">
        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="dashboard-header mb-3">
            <h2><?= $page_title ?></h2>
            <div class="schedule-actions">
                <button class="btn btn-secondary" onclick="window.print()"><i class="fas fa-print"></i> Print Schedule</button>
            </div>
        </div>
        
        <?php if (empty($all_daily_events)): ?>
            <div class="alert alert-info text-center">
                No events or reminders scheduled for this day.
                <br><br>
                <img src="../../assets/img/calendar_placeholders.png" alt="A simple calendar illustration" class="img-fluid" style="max-height: 200px;">
            </div>
        <?php else: ?>

            <div class="event-list">
                <?php foreach ($all_daily_events as $event): ?>
                    <div class="event-item d-flex align-items-center mb-3">
                        <div class="event-icon me-3">
                            <?php if ($event['type'] === 'schedule'): ?>
                                <i class="fas fa-book-open text-primary fa-2x"></i>
                            <?php elseif ($event['type'] === 'reminder'): ?>
                                <i class="fas fa-bell text-warning fa-2x"></i>
                            <?php endif; ?>
                        </div>
                        <div class="event-details flex-grow-1">
                            <h6 class="mb-0"><?= htmlspecialchars($event['title']) ?></h6>
                            <p class="mb-0 text-muted">
                                <?php if ($event['type'] === 'schedule'): ?>
                                    <small><i class="far fa-clock me-1"></i> <?= htmlspecialchars(date('h:i A', strtotime($event['time']))) ?> - <?= htmlspecialchars(date('h:i A', strtotime($event['end_time']))) ?></small><br>
                                    <small><i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($event['location']) ?></small>
                                <?php elseif ($event['type'] === 'reminder'): ?>
                                    <small><i class="far fa-clock me-1"></i> Due: <?= htmlspecialchars(date('h:i A', strtotime($event['time']))) ?></small><br>
                                    <?= !empty($event['description']) ? '<small>' . nl2br(htmlspecialchars($event['description'])) . '</small>' : '' ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm ms-3">View</button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

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

<div class="modal fade" id="addReminderModal" tabindex="-1" aria-labelledby="addReminderModalLabel" aria-hidden="true">
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
                        <textarea class="form-control" id="reminderDescription" name="description" rows="3"></textarea>
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


<script>
    document.addEventListener('DOMContentLoaded', function() {
        const calendarDays = document.querySelectorAll('.calendar-day:not(.empty)');

        calendarDays.forEach(day => {
            day.addEventListener('click', function() {
                const selectedDate = this.getAttribute('data-date');
                window.location.href = `schedule.php?date=${selectedDate}`;
            });
        });

        const addReminderModal = document.getElementById('addReminderModal');
        addReminderModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const selectedDate = button.getAttribute('data-date');
            const reminderDateInput = document.getElementById('reminderDate');
            if (selectedDate) {
                reminderDateInput.value = selectedDate;
            }
        });

        // Handle clicks on the new calendar view links
        const navItems = document.querySelectorAll('.calendar-nav-bar .nav-item');
        const modal = new bootstrap.Modal(document.getElementById('calendarModal'));
        const modalBody = document.getElementById('calendarModalBody');
        const modalTitle = document.getElementById('calendarModalLabel');

        navItems.forEach(item => {
            item.addEventListener('click', function(event) {
                event.preventDefault();

                const view = this.getAttribute('data-view');
                const titleMap = {
                    'year': 'Year View',
                    'month': 'Month View',
                    'week': 'Week View',
                    'day': 'Day View'
                };
                modalTitle.textContent = titleMap[view];
                
                // Fetch content for the selected view
                fetch(`../../includes/fetch_calendar_view.php?view=${view}`)
                    .then(response => response.text())
                    .then(data => {
                        modalBody.innerHTML = data;
                        modal.show();
                    })
                    .catch(error => console.error('Error fetching calendar view:', error));
            });
        });

        // New JavaScript for handling navigation within the modal
        document.getElementById('calendarModal').addEventListener('click', function(event) {
            const arrow = event.target.closest('.calendar-nav-arrow');
            if (arrow) {
                event.preventDefault();

                const view = arrow.getAttribute('data-view');
                const year = arrow.getAttribute('data-year');
                const date = arrow.getAttribute('data-date');
                
                let url = `../../includes/fetch_calendar_view.php?view=${view}`;
                if (year) {
                    url += `&year=${year}`;
                } else if (date) {
                    url += `&date=${date}`;
                }

                // Show a loading message
                modalBody.innerHTML = '<p class="text-center">Loading...</p>';

                fetch(url)
                    .then(response => response.text())
                    .then(data => {
                        modalBody.innerHTML = data;
                    })
                    .catch(error => console.error('Error fetching calendar view:', error));
            }
        });
    });
</script>

<?php require_once '../../templates/footer.php'; ?>