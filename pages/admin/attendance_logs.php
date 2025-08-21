<?php
// CHRONONAV_WEB_DOSS/pages/admin/attendance_logs.php
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php'; // For requireRole()

// Ensure only 'admin' role can access this page.
requireRole(['admin']);

$user = $_SESSION['user'];
$current_user_id = $user['id'];

// --- Fetch fresh admin data for display in header and profile sections ---
$stmt_admin = $conn->prepare("SELECT name, email, profile_img, role FROM users WHERE id = ?");
if ($stmt_admin) {
    $stmt_admin->bind_param("i", $current_user_id);
    $stmt_admin->execute();
    $result_admin = $stmt_admin->get_result();
    if ($result_admin->num_rows > 0) {
        $admin_data = $result_admin->fetch_assoc();
        // Update session with fresh data to ensure profile_img is current
        $_SESSION['user'] = array_merge($_SESSION['user'], $admin_data);
        $display_username = htmlspecialchars($admin_data['name'] ?? 'Admin');
        $display_user_role = htmlspecialchars(ucfirst($admin_data['role'] ?? 'Admin'));
        $profile_img_src = (strpos($admin_data['profile_img'] ?? '', 'uploads/') === 0) ? '../../' . htmlspecialchars($admin_data['profile_img']) : '../../uploads/profiles/default-avatar.png';
    } else {
        // Fallback if user data for header somehow isn't found (shouldn't happen with auth_check)
        error_log("Security Alert: Admin User ID {$current_user_id} in session not found in database for attendance_logs.");
        session_destroy();
        header('Location: ../../auth/login.php?error=user_not_found');
        exit();
    }
    $stmt_admin->close();
} else {
    error_log("Database query preparation failed for admin profile in attendance_logs: " . $conn->error);
    $display_username = 'Admin User';
    $display_user_role = 'Admin';
    $profile_img_src = '../../uploads/profiles/default-avatar.png';
}

$page_title = "All Class Attendance Logs";
$current_page = "attendance_logs"; // For sidenav highlighting

$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// --- Fetch ALL Classes in the System ---
$classes = [];
$stmt_classes = $conn->prepare("
    SELECT c.class_id, c.class_name, c.class_code, c.semester, c.academic_year,
           u.name AS faculty_name, u.email AS faculty_email -- Fetch faculty info
    FROM classes c
    JOIN users u ON c.faculty_id = u.id -- Join to get faculty name
    ORDER BY c.academic_year DESC, c.semester DESC, c.class_name ASC
");

// Handle case where academic_year might not exist (if database not updated with ALTER TABLE)
if (!$stmt_classes) {
    // Fallback query if academic_year column is missing
    error_log("Attempted to prepare query with academic_year, but it failed: " . $conn->error);
    $stmt_classes = $conn->prepare("
        SELECT c.class_id, c.class_name, c.class_code, c.semester,
               u.name AS faculty_name, u.email AS faculty_email
        FROM classes c
        JOIN users u ON c.faculty_id = u.id
        ORDER BY c.semester DESC, c.class_name ASC
    ");
    if (!$stmt_classes) {
        $_SESSION['message'] = "Critical Database Error: Could not prepare class query. Please contact support.";
        $_SESSION['message_type'] = "danger";
        // Exit or redirect if database is fundamentally broken
        exit();
    }
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
    if (!$stmt_sessions) {
        error_log("Failed to prepare session query for class ID " . $class_id . ": " . $conn->error);
        continue;
    }
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
                   u.name AS student_name, u.student_id AS student_school_id
            FROM attendance_records ar
            JOIN users u ON ar.student_id = u.id
            WHERE ar.session_id = ?
            ORDER BY u.name ASC
        ");
        if (!$stmt_attendance) {
            error_log("Failed to prepare attendance query for session ID " . $session_id . ": " . $conn->error);
            continue;
        }
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

// --- START HTML STRUCTURE ---
// Include the admin header which contains <head> and opening <body> tags
require_once '../../templates/admin/header_admin.php';
require_once '../../templates/admin/sidenav_admin.php'; // Sidenav is included here
?>


<link rel="stylesheet" href="../../assets/css/admin_css/attendance_logs.css">

<div class="main-content-wrapper">
    <div class="container-fluid py-4">
        <h2 class="mb-4"><?= $page_title ?></h2>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="attendance-log-container">
            <?php if (empty($classes)): ?>
                <div class="alert alert-info text-center">No classes found in the system to display attendance logs.</div>
            <?php else: ?>
                <?php foreach ($class_attendance_data as $class_id => $data): ?>
                    <div class="card mb-4 class-card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <?= htmlspecialchars($data['info']['class_name']) ?> (<?= htmlspecialchars($data['info']['class_code']) ?>)
                                </div>
                                <small class="text-white-50 text-end">
                                    <?= htmlspecialchars($data['info']['semester']) ?>
                                    <?php if (isset($data['info']['academic_year'])): ?>
                                        <?= htmlspecialchars($data['info']['academic_year']) ?>
                                    <?php endif; ?>
                                    <br>Faculty: <?= htmlspecialchars($data['info']['faculty_name']) ?>
                                </small>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($data['sessions'])): ?>
                                <p class="text-muted text-center">No attendance sessions recorded for this class yet.</p>
                            <?php else: ?>
                                <?php foreach ($data['sessions'] as $session_id => $session_data): ?>
                                    <div class="class-session-item mb-3 p-3 border rounded bg-light">
                                        <h6>
                                            <i class="far fa-calendar-alt text-primary me-2"></i> Session on <?= date('M d, Y', strtotime($session_data['info']['session_date'])) ?>
                                            <?php if ($session_data['info']['actual_start_time'] && $session_data['info']['actual_end_time']): ?>
                                                (<?= date('h:i A', strtotime($session_data['info']['actual_start_time'])) ?> - <?= date('h:i A', strtotime($session_data['info']['actual_end_time'])) ?>)
                                            <?php endif; ?>
                                            <?php if ($session_data['info']['room_name']): ?>
                                                <small class="text-muted ms-2"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($session_data['info']['room_name']) ?></small>
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
                                                    <li class="list-group-item attendance-record d-flex justify-content-between align-items-center px-0">
                                                        <div>
                                                            <strong><?= htmlspecialchars($record['student_name']) ?></strong>
                                                            <?php if ($record['student_school_id']): ?>
                                                                <small class="text-muted ms-2">(ID: <?= htmlspecialchars($record['student_school_id']) ?>)</small>
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
                                                        <div class="attendance-actions d-flex align-items-center gap-2">
                                                            <span class="badge rounded-pill 
                                                                <?php 
                                                                    switch(htmlspecialchars($record['status'])) {
                                                                        case 'Present': echo 'bg-success'; break;
                                                                        case 'Absent': echo 'bg-danger'; break;
                                                                        case 'Late': echo 'bg-warning text-dark'; break;
                                                                        default: echo 'bg-secondary'; break;
                                                                    }
                                                                ?>">
                                                                <?= htmlspecialchars($record['status']) ?>
                                                            </span>
                                                            <a href="edit_attendance.php?record_id=<?= $record['record_id'] ?>" class="btn btn-sm btn-info" title="Edit Attendance"><i class="fas fa-edit"></i></a>
                                                            <form action="../../actions/admin/attendance_crud.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this attendance record? This action cannot be undone.');">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="record_id" value="<?= $record['record_id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete Attendance"><i class="fas fa-trash-alt"></i></button>
                                                            </form>
                                                        </div>
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

<script src="../../assets/js/jquery.min.js"></script>
<script src="../../assets/js/script.js"></script>
<?php
// Include the common footer which closes <body> and <html> and includes common JS
require_once '../../templates/footer.php';
?>
