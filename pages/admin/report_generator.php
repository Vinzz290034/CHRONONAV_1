<?php
// CHRONONAV_WEB_DOSS/pages/admin/report_generator.php
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php'; // Assuming requireRole function is here

// Composer Autoloader
require_once '../../vendor/autoload.php';

// Ensure user is an admin
// The requireRole function handles redirection and messages
requireRole(['admin']);

// Fetch user data for header display (name, role, profile_img)
// This is done here so the variables are available before including the header.
$user_id = $_SESSION['user']['id'];
$stmt = $conn->prepare("SELECT name, role, profile_img FROM users WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $header_user_data = $result->fetch_assoc();
        $display_username = htmlspecialchars($header_user_data['name'] ?? 'Admin User');
        $display_user_role = htmlspecialchars(ucfirst($header_user_data['role'] ?? 'Admin'));
        // Construct profile_img_src for the header, relative to the templates/admin directory
        // Assuming uploads/profiles is relative to the project root (CHRONONAV_WEB_DOSS)
        $profile_img_src = (strpos($header_user_data['profile_img'], 'uploads/') === 0) ? '../../' . $header_user_data['profile_img'] : '../../uploads/profiles/default-avatar.png';
    } else {
        // Fallback if user data for header somehow isn't found (shouldn't happen with auth_check)
        $display_username = 'Admin User';
        $display_user_role = 'Admin';
        $profile_img_src = '../../uploads/profiles/default-avatar.png';
    }
    $stmt->close();
} else {
    $display_username = 'Admin User';
    $display_user_role = 'Admin';
    $profile_img_src = '../../uploads/profiles/default-avatar.png';
}


// Set variables for header and sidenav
$page_title = "Report Generator";
$current_page = "report_generator"; // For sidenav highlighting

// Messages will be set in the logic file (if any) or by requireRole and pulled from session
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message']);
unset($_SESSION['message_type']);

// Fetch list of faculties for the filter dropdown
$faculties = [];
$sql_faculties = "SELECT id, name FROM users WHERE role = 'faculty' ORDER BY name";
$result_faculties = $conn->query($sql_faculties);
if ($result_faculties && $result_faculties->num_rows > 0) {
    while ($row = $result_faculties->fetch_assoc()) {
        $faculties[] = $row;
    }
}

// Retrieve filter parameters from GET request for display
$filter_faculty_id = $_GET['faculty_id'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Report Data retrieval logic
$report_data = [];

// Determine the threshold for 'total_expected_sessions' based on end_date filter
// Using prepared statement for condition as well for safety
$expected_sessions_end_condition = "CONCAT(cs_sub.session_date, ' ', cs_sub.actual_end_time) <= NOW()"; // Default to past sessions up to now
$expected_sessions_end_param = null;
$expected_sessions_end_type = '';

if (!empty($end_date)) {
    // If end_date is provided, use it for the condition
    // WARNING: This assumes that $end_date is already safe for direct embedding
    // in the subquery's SQL string if it's not being bound.
    // If you intend this to be a bound parameter, it needs to be handled carefully
    // with subqueries (e.g., using variables in SQL or running separate prepares).
    // For now, I'll proceed with your original logic's assumption.
    $expected_sessions_end_condition = "CONCAT(cs_sub.session_date, ' 23:59:59') <= '" . $conn->real_escape_string($end_date) . "'";
    // If it was meant to be bound, the condition would be `?` and you'd add to main params for binding.
}


// SQL query to fetch report data
$sql = "
    SELECT
        u.name AS faculty_name,
        u.id AS faculty_id,
        c.class_id,
        c.class_code,
        c.class_name,
        c.semester,
        c.academic_year,
        COUNT(DISTINCT cs.id) AS total_sessions_recorded,
        (SELECT COUNT(DISTINCT student_id) FROM class_students WHERE class_id = c.class_id) AS total_students_in_class,
        SUM(CASE WHEN ar.status = 'Present' THEN 1 ELSE 0 END) AS total_present,
        SUM(CASE WHEN ar.status = 'Absent' THEN 1 ELSE 0 END) AS total_absent,
        SUM(CASE WHEN ar.status = 'Late' THEN 1 ELSE 0 END) AS total_late,
        SUM(CASE WHEN ar.status IS NOT NULL THEN 1 ELSE 0 END) AS total_attendance_marked,
        (SELECT COUNT(cs_sub.id) FROM class_sessions cs_sub WHERE cs_sub.class_id = c.class_id AND {$expected_sessions_end_condition}) AS total_expected_sessions
    FROM
        users u
    LEFT JOIN
        classes c ON u.id = c.faculty_id
    LEFT JOIN
        class_sessions cs ON c.class_id = cs.class_id
    LEFT JOIN
        attendance_records ar ON cs.id = ar.session_id
    WHERE
        u.role = 'faculty'
";

$params = [];
$types = '';

if (!empty($filter_faculty_id)) {
    $sql .= " AND u.id = ?";
    $params[] = $filter_faculty_id;
    $types .= 'i';
}

if (!empty($start_date)) {
    $sql .= " AND cs.session_date >= ?";
    $params[] = $start_date;
    $types .= 's';
}
if (!empty($end_date)) {
    $sql .= " AND cs.session_date <= ?";
    $params[] = $end_date;
    $types .= 's';
}

$sql .= " GROUP BY u.id, u.name, c.class_id, c.class_code, c.class_name, c.semester, c.academic_year
          ORDER BY u.name, c.class_name";


// Prepare and execute the main report query
$stmt_report = $conn->prepare($sql);
if ($stmt_report === false) {
    error_log("Error preparing report query: " . $conn->error);
} else {
    if (!empty($params)) {
        // Need to use call_user_func_array for dynamic parameters with bind_param
        $ref_params = [];
        foreach ($params as $key => $value) {
            $ref_params[$key] = &$params[$key]; // Pass by reference
        }
        call_user_func_array([$stmt_report, 'bind_param'], array_merge([$types], $ref_params));
    }
    $stmt_report->execute();
    $result_report = $stmt_report->get_result();
    while ($row = $result_report->fetch_assoc()) {
        $report_data[] = $row;
    }
    $stmt_report->close();
}

// --- START HTML STRUCTURE ---
// Include the admin header which contains <head> and opening <body> tags
require_once '../../templates/admin/header_admin.php';
require_once '../../templates/admin/sidenav_admin.php'; // Sidenav is included here
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/admin_css/report_generator.css">
<div class="main-content-wrapper">
    <div class="container-fluid py-4">
        <h2 class="mb-4"><?= $page_title ?></h2>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card p-4 shadow-sm mb-4">
            <h4>Filter Report</h4>
            <form action="" method="GET" class="report-filters">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="faculty_id" class="form-label">Filter by Faculty:</label>
                        <select class="form-select" id="faculty_id" name="faculty_id">
                            <option value="">All Faculty</option>
                            <?php foreach ($faculties as $faculty): ?>
                                <option value="<?php echo htmlspecialchars($faculty['id']); ?>"
                                    <?php echo ($filter_faculty_id == $faculty['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($faculty['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date:</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date:</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="card p-4 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="m-0">Report Data</h4>
                <a href="../../actions/admin/export_report_pdf.php?faculty_id=<?php echo htmlspecialchars($filter_faculty_id); ?>&start_date=<?php echo htmlspecialchars($start_date); ?>&end_date=<?php echo htmlspecialchars($end_date); ?>" class="btn btn-danger">
                    <i class="fas fa-file-pdf me-2"></i> Export to PDF
                </a>
            </div>
            <?php if (empty($report_data)): ?>
                <div class="alert alert-info text-center">No report data available for the selected filters.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped report-table">
                        <thead>
                            <tr>
                                <th>Faculty Name</th>
                                <th>Class Code</th>
                                <th>Class Name</th>
                                <th>Semester (Year)</th>
                                <th>Recorded Sessions</th>
                                <th>Expected Sessions (Past)</th>
                                <th>Attendance Marked (%)</th>
                                <th>Students Enrolled</th>
                                <th>Present</th>
                                <th>Absent</th>
                                <th>Late</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                                <?php
                                $attendance_percentage = 0;
                                if ($row['total_students_in_class'] > 0 && $row['total_sessions_recorded'] > 0) {
                                    $possible_records = $row['total_students_in_class'] * $row['total_sessions_recorded'];
                                    if ($possible_records > 0) {
                                        $attendance_percentage = ($row['total_attendance_marked'] / $possible_records) * 100;
                                    }
                                }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['faculty_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['class_code']); ?></td>
                                    <td><?php echo htmlspecialchars($row['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['semester'] ?? 'N/A') . ' (' . htmlspecialchars($row['academic_year'] ?? 'N/A') . ')'; ?></td>
                                    <td><?php echo htmlspecialchars($row['total_sessions_recorded']); ?></td>
                                    <td><?php echo htmlspecialchars($row['total_expected_sessions']); ?></td>
                                    <td><?php echo number_format($attendance_percentage, 2); ?>%</td>
                                    <td><?php echo htmlspecialchars($row['total_students_in_class']); ?></td>
                                    <td><?php echo htmlspecialchars($row['total_present']); ?></td>
                                    <td><?php echo htmlspecialchars($row['total_absent']); ?></td>
                                    <td><?php echo htmlspecialchars($row['total_late']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="../../assets/js/jquery.min.js"></script>
<script src="../../assets/js/script.js"></script>
<?php
// Include the common footer which closes <body> and <html> and includes common JS
include_once '../../templates/footer.php';
?>