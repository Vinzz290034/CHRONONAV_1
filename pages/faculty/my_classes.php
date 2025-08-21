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
$current_page = "my_classes"; // For active sidebar link, ensure this matches the 'href' or 'data-page' in sidenav_faculty.php for 'My Classes'

// Variables for the header template (display_username, display_user_role, profile_img_src)
$display_username = htmlspecialchars($user['name'] ?? 'Faculty');
$display_user_role = htmlspecialchars($user['role'] ?? 'Faculty');

// Attempt to get profile image path for the header
$profile_img_src = '../../uploads/profiles/default-avatar.png'; // Default fallback
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

// Handle case where academic_year might not exist (if database not updated with ALTER TABLE)
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
// --- Include the Faculty-specific Header ---
// This includes Bootstrap CSS, Font Awesome CSS, and top_navbar.css
// It also sets up the top navigation bar with user profile dropdown.
require_once '../../templates/faculty/header_faculty.php';
?>
<link rel="stylesheet" href="../../assets/css/faculty_css/my_classes.css">

<?php
// --- Include the Faculty-specific Sidenav ---
// This includes sidenavs.css and sets up the left navigation sidebar.
require_once '../../templates/faculty/sidenav_faculty.php';
?>

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
                                    <th>Class Code</th>
                                    <th>Class Name</th>
                                    <th>Semester (Year)</th>
                                    <th>Room</th>
                                    <th>Day(s)</th>
                                    <th>Time</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assigned_classes as $class): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($class['class_code']) ?></td>
                                        <td><?= htmlspecialchars($class['class_name']) ?></td>
                                        <td>
                                            <?= htmlspecialchars($class['semester']) ?>
                                            <?php if (isset($class['academic_year'])): ?>
                                                (<?= htmlspecialchars($class['academic_year']) ?>)
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($class['room_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($class['day_of_week'] ?? 'N/A') ?></td>
                                        <td>
                                            <?php
                                            $start = $class['start_time'] ? date('h:i A', strtotime($class['start_time'])) : 'N/A';
                                            $end = $class['end_time'] ? date('h:i A', strtotime($class['end_time'])) : 'N/A';
                                            echo "$start - $end";
                                            ?>
                                        </td>
                                        <td class="action-buttons">
                                            <a href="add_session.php?class_id=<?= $class['class_id'] ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-plus-circle"></i> Add Session
                                            </a>
                                            <a href="attendance_logs.php?class_id=<?= $class['class_id'] ?>" class="btn btn-info btn-sm ml-1">
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

<?php require_once '../../templates/footer.php'; ?>