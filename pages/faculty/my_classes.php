<?php
// CHRONONAV_WEB_UNO/pages/faculty/my_classes.php
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php'; // For requireRole()

// Ensure only 'faculty' role can access this page.
requireRole(['faculty']);

$user = $_SESSION['user'];
$current_faculty_id = $user['id'];

// Set page-specific variables for the header and sidenav
$page_title = "My Class Schedule";
$current_page = "my_classes";

$display_username = htmlspecialchars($user['name'] ?? 'Faculty');
$display_user_role = htmlspecialchars($user['role'] ?? 'Faculty');

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

$assigned_classes = [];

// Fetch classes assigned to the current faculty
$stmt = $conn->prepare("
    SELECT c.class_id, c.class_code, c.class_name, c.semester, c.day_of_week,
           c.start_time, c.end_time, r.room_name, c.academic_year
    FROM classes c
    LEFT JOIN rooms r ON c.room_id = r.id
    WHERE c.faculty_id = ?
    ORDER BY c.day_of_week, c.start_time ASC
");

if (!$stmt) {
    error_log("Failed to prepare query with academic_year: " . $conn->error);
    $stmt = $conn->prepare("
        SELECT c.class_id, c.class_code, c.class_name, c.semester, c.day_of_week,
               c.start_time, c.end_time, r.room_name
        FROM classes c
        LEFT JOIN rooms r ON c.room_id = r.id
        WHERE c.faculty_id = ?
        ORDER BY c.day_of_week, c.start_time ASC
    ");
    if (!$stmt) {
        $_SESSION['message'] = "Critical Database Error: Could not prepare class query. Please contact support.";
        $_SESSION['message_type'] = "danger";
        exit();
    }
}

if ($stmt) {
    $stmt->bind_param("i", $current_faculty_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $assigned_classes[] = $row;
    }
    $stmt->close();
} else {
    $_SESSION['message'] = "Database error preparing class query: " . $conn->error;
    $_SESSION['message_type'] = "danger";
}

?>

<?php
require_once '../../templates/faculty/header_faculty.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'ChronoNav - My Classes' ?></title>

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
            /* Width of the sidenav */
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

        .table-bordered {
            border: 1px solid #d1dce6;
        }

        .table-bordered th,
        .table-bordered td {
            border: 1px solid #d1dce6;
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

        .ml-1 {
            margin-left: 0.25rem;
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

        /* Mobile: 767px and below */
        @media (max-width: 767px) {
            .sched.main-content-wrapper {
                margin-left: 0 !important;
                width: 100% !important;
            }

            .main-dashboard-content {
                padding: 1rem !important;
                height: auto !important;
            }

            .dashboard-header h2 {
                font-size: 1.5rem !important;
                text-align: center;
                margin-bottom: 1rem !important;
            }

            .card {
                margin-bottom: 1rem !important;
            }

            .card-header {
                padding: 1rem !important;
            }

            .card-header h5 {
                font-size: 1.25rem !important;
                text-align: center;
            }

            .card-body {
                padding: 1rem !important;
            }

            .table-responsive {
                border: 1px solid #d1dce6;
                border-radius: 8px;
            }

            .table th,
            .table td {
                padding: 0.75rem 0.5rem !important;
                font-size: 0.875rem !important;
            }

            .action-buttons {
                white-space: normal !important;
                text-align: center;
            }

            .action-buttons .btn {
                margin-bottom: 0.5rem !important;
                display: block !important;
                width: 100% !important;
                margin-right: 0 !important;
            }

            .btn-sm {
                height: auto !important;
                min-height: 44px;
                display: flex !important;
                align-items: center;
                justify-content: center;
            }

            /* Hide less important columns on mobile */
            .table th:nth-child(3),
            .table td:nth-child(3),
            .table th:nth-child(4),
            .table td:nth-child(4) {
                display: none;
            }

            .alert {
                margin: 1rem 0.5rem !important;
                text-align: center;
            }
        }

        /* Tablet: 768px to 1023px */
        @media (min-width: 768px) and (max-width: 1023px) {
            .sched.main-content-wrapper {
                margin-left: 15% !important;
                width: 85% !important;
            }

            .main-dashboard-content {
                padding: 1.5rem !important;
            }

            .dashboard-header h2 {
                font-size: 1.75rem !important;
            }

            .card-header {
                padding: 1.25rem !important;
            }

            .card-header h5 {
                font-size: 1.375rem !important;
            }

            .card-body {
                padding: 1.25rem !important;
            }

            .table th,
            .table td {
                padding: 0.875rem !important;
                font-size: 0.9rem !important;
            }

            .action-buttons {
                white-space: normal;
            }

            .action-buttons .btn {
                margin-bottom: 0.25rem;
                display: inline-block;
                width: auto;
            }

            /* Hide semester column on tablet to save space */
            .table th:nth-child(3),
            .table td:nth-child(3) {
                display: none;
            }
        }

        /* Desktop: 1024px and above */
        @media (min-width: 1024px) {
            .sched.main-content-wrapper {
                margin-left: 20% !important;
                width: 80% !important;
            }

            .main-dashboard-content {
                padding: 2rem !important;
            }

            .dashboard-header h2 {
                font-size: 1.75rem !important;
            }

            .card-header {
                padding: 1.5rem !important;
            }

            .card-header h5 {
                font-size: 1.5rem !important;
            }

            .card-body {
                padding: 1.5rem !important;
            }

            .table th,
            .table td {
                padding: 1rem !important;
                font-size: 0.95rem !important;
            }

            .action-buttons {
                white-space: nowrap;
            }

            .action-buttons .btn {
                margin-bottom: 0;
                display: inline-block;
                width: auto;
            }
        }

        /* Enhanced table responsiveness */
        @media (max-width: 767px) {
            .table thead {
                display: none;
            }

            .table tbody tr {
                display: block;
                margin-bottom: 1rem;
                border: 1px solid #d1dce6;
                border-radius: 8px;
                padding: 1rem;
            }

            .table tbody td {
                display: block;
                text-align: right;
                padding: 0.5rem !important;
                border: none;
                position: relative;
            }

            .table tbody td::before {
                content: attr(data-label);
                position: absolute;
                left: 0.5rem;
                top: 0.5rem;
                font-weight: 600;
                color: #0e151b;
            }

            .action-buttons td::before {
                display: none;
            }

            .action-buttons {
                text-align: center !important;
                margin-top: 1rem;
            }
        }

        /* Improved button styling for mobile */
        @media (max-width: 767px) {
            .btn {
                min-height: 44px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 0.9rem;
            }

            .btn i {
                margin-right: 0.5rem;
            }
        }

        /* Better card spacing */
        @media (max-width: 767px) {
            .card {
                margin: 0.5rem !important;
            }
        }

        /* Print styles */
        @media print {
            .sched.main-content-wrapper {
                margin-left: 0 !important;
                width: 100% !important;
            }

            .btn,
            .action-buttons {
                display: none !important;
            }

            .card {
                box-shadow: none !important;
                border: 1px solid #d1dce6 !important;
            }
        }
    </style>
</head>

<body>
    <?php require_once '../../templates/faculty/sidenav_faculty.php'; ?>

    <div class="sched main-content-wrapper">
        <div class="main-dashboard-content p-4">
            <div class="dashboard-header">
                <h2 class="fs-3 px-3 fw-bold"><?= $page_title ?></h2>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Your Assigned Classes</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($assigned_classes)): ?>
                        <div class="alert alert-info mb-0">You are not currently assigned to any classes.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th data-label="Class Code">Class Code</th>
                                        <th data-label="Class Name">Class Name</th>
                                        <th data-label="Semester">Semester (Year)</th>
                                        <th data-label="Room">Room</th>
                                        <th data-label="Day">Day(s)</th>
                                        <th data-label="Time">Time</th>
                                        <th data-label="Actions">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assigned_classes as $class): ?>
                                        <tr>
                                            <td data-label="Class Code"><?= htmlspecialchars($class['class_code']) ?></td>
                                            <td data-label="Class Name"><?= htmlspecialchars($class['class_name']) ?></td>
                                            <td data-label="Semester">
                                                <?= htmlspecialchars($class['semester']) ?>
                                                <?php if (isset($class['academic_year'])): ?>
                                                    (<?= htmlspecialchars($class['academic_year']) ?>)
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Room"><?= htmlspecialchars($class['room_name'] ?? 'N/A') ?></td>
                                            <td data-label="Day"><?= htmlspecialchars($class['day_of_week'] ?? 'N/A') ?></td>
                                            <td data-label="Time">
                                                <?php
                                                $start = $class['start_time'] ? date('h:i A', strtotime($class['start_time'])) : 'N/A';
                                                $end = $class['end_time'] ? date('h:i A', strtotime($class['end_time'])) : 'N/A';
                                                echo "$start - $end";
                                                ?>
                                            </td>
                                            <td class="action-buttons">
                                                <a href="add_session.php?class_id=<?= $class['class_id'] ?>"
                                                    class="btn btn-primary btn-sm">
                                                    <i class="fas fa-plus-circle"></i> Add Session
                                                </a>
                                                <a href="attendance_logs.php?class_id=<?= $class['class_id'] ?>"
                                                    class="btn btn-info btn-sm ml-1">
                                                    <i class="fas fa-eye"></i> View Attendance
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <?php require_once '../../templates/footer.php'; ?>
</body>

</html>
<?php include('../../includes/semantics/footer.php'); ?>