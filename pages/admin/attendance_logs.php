<?php
// CHRONONAV_WEB_DOSS/pages/admin/attendance_logs.php
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

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
        $_SESSION['user'] = array_merge($_SESSION['user'], $admin_data);
        $display_username = htmlspecialchars($admin_data['name'] ?? 'Admin');
        $display_user_role = htmlspecialchars(ucfirst($admin_data['role'] ?? 'Admin'));
        $profile_img_src = (strpos($admin_data['profile_img'] ?? '', 'uploads/') === 0) ? '../../' . htmlspecialchars($admin_data['profile_img']) : '../../uploads/profiles/default-avatar.png';
    } else {
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
$current_page = "attendance_logs";

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
           u.name AS faculty_name, u.email AS faculty_email
    FROM classes c
    JOIN users u ON c.faculty_id = u.id
    ORDER BY c.academic_year DESC, c.semester DESC, c.class_name ASC
");

if (!$stmt_classes) {
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

require_once '../../templates/admin/header_admin.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'ChronoNav - Attendance Logs' ?></title>

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

        .main-content-wrapper {
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

        .btn-info {
            background-color: #0dcaf0;
            border-color: #0dcaf0;
            color: #000000;
            font-weight: 600;
            letter-spacing: 0.015em;
            padding: 0.5rem 1rem;
        }

        .btn-info:hover {
            background-color: #31d2f2;
            border-color: #31d2f2;
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

        .action-buttons {
            white-space: nowrap;
        }

        .action-buttons .btn {
            margin-right: 0.5rem;
        }

        .action-buttons .btn:last-child {
            margin-right: 0;
        }

        .badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
        }

        .bg-danger {
            background-color: #dc3545 !important;
        }

        .bg-info {
            background-color: #0dcaf0 !important;
        }

        .bg-success {
            background-color: #198754 !important;
        }

        .bg-warning {
            background-color: #ffc107 !important;
            color: #000 !important;
        }

        .bg-primary {
            background-color: #1d7dd7 !important;
        }

        .bg-secondary {
            background-color: #6c757d !important;
        }

        .class-session-item {
            background-color: #f8fafb;
            border: 1px solid #d1dce6;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .attendance-list {
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
        }

        .attendance-record {
            border-bottom: 1px solid #f1f1f1;
            padding: 1rem;
        }

        .attendance-actions {
            white-space: nowrap;
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
            .main-content-wrapper {
                margin-left: 0;
            }

            .main-dashboard-content {
                padding: 1rem;
            }

            .attendance-actions {
                white-space: normal;
            }

            .attendance-actions .btn {
                margin-bottom: 0.5rem;
                display: block;
                width: 100%;
            }

            .class-session-item {
                padding: 1rem;
            }
        }
    </style>
</head>

<body>
    <?php require_once '../../templates/admin/sidenav_admin.php'; ?>

    <div class="main-content-wrapper">
        <div class="main-dashboard-content p-4">
            <div class="dashboard-header">
                <h2 class="fs-3 px-3 fw-bold"><?= $page_title ?></h2>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="attendance-log-container">
                <?php if (empty($classes)): ?>
                    <div class="alert alert-info mb-0">No classes found in the system to display attendance logs.</div>
                <?php else: ?>
                    <?php foreach ($class_attendance_data as $class_id => $data): ?>
                        <div class="card shadow-sm mb-4">
                            <div class="card-header">
                                <h5 class="mb-0 d-flex justify-content-between align-items-center">
                                    <div>
                                        <?= htmlspecialchars($data['info']['class_name']) ?> (<?= htmlspecialchars($data['info']['class_code']) ?>)
                                    </div>
                                    <small class="text-muted text-end">
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
                                        <div class="class-session-item mb-3">
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
                                                <p class="text-muted mb-2 small">Notes: <?= nl2br(htmlspecialchars($session_data['info']['session_notes'])) ?></p>
                                            <?php endif; ?>

                                            <?php if (empty($session_data['attendance_records'])): ?>
                                                <div class="alert alert-warning alert-sm mt-2 mb-0">No attendance records found for this session.</div>
                                            <?php else: ?>
                                                <div class="attendance-list">
                                                    <?php foreach ($session_data['attendance_records'] as $record): ?>
                                                        <div class="attendance-record d-flex justify-content-between align-items-center">
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
                                                                <span class="badge 
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
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
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

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <?php require_once '../../templates/footer.php'; ?>
</body>

</html>