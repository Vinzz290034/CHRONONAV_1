<?php
// CHRONONAV_WEB_UNO/pages/faculty/attendance_logs.php
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php'; // For requireRole()

// Ensure only 'faculty' and 'admin' roles can access this page.
// Admin might view their own assigned classes using this page,
// or a more comprehensive admin view could be built separately.
requireRole(['faculty', 'admin']);

$user = $_SESSION['user'];
$current_user_id = $user['id'];
$current_user_role = $user['role'];
$page_title = "My Class Attendance Logs";
$current_page = "attendance_logs"; // For active sidebar link

$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// --- Fetch Classes Taught by the Current Faculty/Admin User ---
$classes = [];
$stmt_classes = null;

if ($current_user_role === 'faculty') {
    // Faculty can only view their own classes
    $stmt_classes = $conn->prepare("
        SELECT class_id, class_name, class_code, semester, academic_year
        FROM classes
        WHERE faculty_id = ?
        ORDER BY academic_year DESC, semester DESC, class_name ASC
    ");
    $stmt_classes->bind_param("i", $current_user_id);
} elseif ($current_user_role === 'admin') {
    // Admins can view ALL classes.
    // For "Own Classes" logic for admin, they'd still link to their faculty_id if they teach.
    // If an admin wants to see "ALL" attendance, this query would change significantly.
    // For now, we'll keep it consistent with "Own Classes" as per your request, meaning
    // an admin would see classes they are explicitly assigned to as faculty.
    $stmt_classes = $conn->prepare("
        SELECT class_id, class_name, class_code, semester, academic_year
        FROM classes
        WHERE faculty_id = ?
        ORDER BY academic_year DESC, semester DESC, class_name ASC
    ");
    $stmt_classes->bind_param("i", $current_user_id);
}

if ($stmt_classes) {
    $stmt_classes->execute();
    $result_classes = $stmt_classes->get_result();
    while ($row = $result_classes->fetch_assoc()) {
        $classes[] = $row;
    }
    $stmt_classes->close();
}

// --- Fetch Attendance Logs for Each Class ---
$class_attendance_data = [];

foreach ($classes as $class) {
    $class_id = $class['class_id'];
    $class_attendance_data[$class_id] = [
        'info' => $class,
        'sessions' => []
    ];

    // Fetch class sessions for this specific class
    $stmt_sessions = $conn->prepare("
        SELECT cs.id AS session_id, cs.session_date, cs.actual_start_time, cs.actual_end_time,
               r.room_name, cs.notes AS session_notes
        FROM class_sessions cs
        LEFT JOIN rooms r ON cs.room_id = r.id
        WHERE cs.class_id = ?
        ORDER BY cs.session_date DESC, cs.actual_start_time DESC
    ");
    $stmt_sessions->bind_param("i", $class_id);
    $stmt_sessions->execute();
    $result_sessions = $stmt_sessions->get_result();

    while ($session = $result_sessions->fetch_assoc()) {
        $session_id = $session['session_id'];
        $class_attendance_data[$class_id]['sessions'][$session_id] = [
            'info' => $session,
            'attendance_records' => []
        ];

        // Fetch attendance records for this session
        $stmt_attendance = $conn->prepare("
            SELECT ar.id AS record_id, ar.student_id, ar.status, ar.time_in, ar.time_out, ar.notes AS attendance_notes,
                   u.name AS student_name, u.student_id AS student_school_id -- Assuming 'student_id' in users is for school ID
            FROM attendance_records ar
            JOIN users u ON ar.student_id = u.id
            WHERE ar.session_id = ?
            ORDER BY u.name ASC
        ");
        $stmt_attendance->bind_param("i", $session_id);
        $stmt_attendance->execute();
        $result_attendance = $stmt_attendance->get_result();
        while ($record = $result_attendance->fetch_assoc()) {
            $class_attendance_data[$class_id]['sessions'][$session_id]['attendance_records'][] = $record;
        }
        $stmt_attendance->close();
    }
    $stmt_sessions->close();
}

require_once '../../templates/faculty/header_faculty.php';
require_once '../../templates/faculty/sidenav_faculty.php';
?>
<link rel="stylesheet" href="../../assets/css/faculty_css/attendance_log.css">
  
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

        <div class="attendance-log-container">
            <?php if (empty($classes)): ?>
                <div class="alert alert-info">You are not currently assigned to any classes for attendance tracking.</div>
            <?php else: ?>
                <?php foreach ($class_attendance_data as $class_id => $data): ?>
                    <div class="card mb-4 class-card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <?= htmlspecialchars($data['info']['class_name']) ?> (<?= htmlspecialchars($data['info']['class_code']) ?>)
                                <small class="text-muted float-right"><?= htmlspecialchars($data['info']['semester']) ?> <?= htmlspecialchars($data['info']['academic_year']) ?></small>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($data['sessions'])): ?>
                                <p class="text-muted">No attendance sessions recorded for this class yet.</p>
                            <?php else: ?>
                                <?php foreach ($data['sessions'] as $session_id => $session_data): ?>
                                    <div class="class-session-item mb-3">
                                        <h6>
                                            <i class="far fa-calendar-alt"></i> Session on <?= date('M d, Y', strtotime($session_data['info']['session_date'])) ?>
                                            <?php if ($session_data['info']['actual_start_time'] && $session_data['info']['actual_end_time']): ?>
                                                (<?= date('h:i A', strtotime($session_data['info']['actual_start_time'])) ?> - <?= date('h:i A', strtotime($session_data['info']['actual_end_time'])) ?>)
                                            <?php endif; ?>
                                            <?php if ($session_data['info']['room_name']): ?>
                                                <small class="text-muted ml-2"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($session_data['info']['room_name']) ?></small>
                                            <?php endif; ?>
                                        </h6>
                                        <?php if (!empty($session_data['info']['session_notes'])): ?>
                                            <p class="text-muted mb-1 small">Notes: <?= nl2br(htmlspecialchars($session_data['info']['session_notes'])) ?></p>
                                        <?php endif; ?>

                                        <?php if (empty($session_data['attendance_records'])): ?>
                                            <div class="alert alert-warning alert-sm mt-2 mb-0">No attendance records found for this session.</div>
                                        <?php else: ?>
                                            <ul class="list-group list-group-flush attendance-list">
                                                <?php foreach ($session_data['attendance_records'] as $record): ?>
                                                    <li class="list-group-item attendance-record px-0">
                                                        <div>
                                                            <strong><?= htmlspecialchars($record['student_name']) ?></strong>
                                                            <?php if ($record['student_school_id']): ?>
                                                                <small class="text-muted">(ID: <?= htmlspecialchars($record['student_school_id']) ?>)</small>
                                                            <?php endif; ?>
                                                            <?php if ($record['time_in'] || $record['time_out']): ?>
                                                                <br><small class="text-muted">
                                                                <?php if ($record['time_in']) echo 'In: ' . date('h:i A', strtotime($record['time_in'])); ?>
                                                                <?php if ($record['time_out']) echo ' Out: ' . date('h:i A', strtotime($record['time_out'])); ?>
                                                                </small>
                                                            <?php endif; ?>
                                                            <?php if (!empty($record['attendance_notes'])): ?>
                                                                <br><small class="text-muted">Note: <?= nl2br(htmlspecialchars($record['attendance_notes'])) ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                        <span class="badge attendance-status-badge status-<?= htmlspecialchars($record['status']) ?>">
                                                            <?= htmlspecialchars($record['status']) ?>
                                                        </span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>

<script src="../../assets/js/jquery.min.js"></script>
<script src="../../assets/js/script.js"></script>