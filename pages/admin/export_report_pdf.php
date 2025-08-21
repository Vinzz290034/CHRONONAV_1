<?php
// CHRONONAV_WEB_DOSS/pages/admin/report_generator.php
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php'; // For requireRole()

requireRole(['admin']); // Only admins can access the report generator

$page_title = "Report Generator";
$current_page = "reports"; // For active sidebar/nav link

$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Fetch all faculty members for the filter dropdown
$faculty_members = [];
$stmt_faculty = $conn->prepare("SELECT id, full_name FROM users WHERE role = 'faculty' ORDER BY full_name ASC");
if ($stmt_faculty) {
    $stmt_faculty->execute();
    $result_faculty = $stmt_faculty->get_result();
    while ($row = $result_faculty->fetch_assoc()) {
        $faculty_members[] = $row;
    }
    $stmt_faculty->close();
} else {
    error_log("Error fetching faculty members: " . $conn->error);
}

// Default filter values
$filter_faculty_id = $_GET['faculty_id'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$report_data = []; // This will hold the generated report data

// Process report generation request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['generate_report']) || !empty($_GET['faculty_id']) || !empty($_GET['start_date']) || !empty($_GET['end_date']))) {

    // Determine the threshold for 'total_expected_sessions' based on end_date filter
    $session_end_threshold = !empty($end_date) ? "CONCAT(cs_sub.session_date, ' ', cs_sub.actual_end_time) <= ?" : "CONCAT(cs_sub.session_date, ' ', cs_sub.actual_end_time) < NOW()";

    $sql = "
        SELECT
            u.full_name AS faculty_name,
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
            (SELECT COUNT(cs_sub.id) FROM class_sessions cs_sub WHERE cs_sub.class_id = c.class_id AND {$session_end_threshold}) AS total_expected_sessions
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

    // Parameters for the main query
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

    $sql .= " GROUP BY u.id, u.full_name, c.class_id, c.class_code, c.class_name, c.semester, c.academic_year
              ORDER BY u.full_name, c.class_name";

    $stmt_report = $conn->prepare($sql);

    if ($stmt_report === false) { // Check for prepare error
        $_SESSION['message'] = "Error preparing report query: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    } else {
        // Handle parameters for the subquery (`total_expected_sessions`)
        if (!empty($end_date)) {
            // If end_date is used in the main query, it's also used in the subquery's threshold.
            // The position matters when binding. Append it to the parameters list.
            $params_for_subquery = $params; // Copy current params
            $params_for_subquery[] = $end_date . ' 23:59:59'; // Full datetime for comparison
            $types_for_subquery = $types . 's'; // Add type for end_date datetime
            
            // This is where it gets tricky with dynamically added subquery params.
            // A simpler approach is to bind the subquery parameter directly
            // inside the main query's WHERE clause or ensure the main query
            // and subquery parameters are ordered correctly if using one bind.
            // For now, given the structure, the easiest is to make the subquery part of the SQL string directly.
            // Let's modify the SQL generation slightly.

            // Re-think: The subquery `(SELECT COUNT(cs_sub.id) FROM ... WHERE ... {$session_end_threshold})`
            // needs its own parameter if $session_end_threshold uses a '?' placeholder.
            // To bind all parameters at once, the `?` for the subquery would need to be at a specific known position.
            // Given the complexity of dynamic `?` placement, let's adjust the `total_expected_sessions` subquery
            // to directly use the `$end_date` variable if it's set, making it less reliant on bind_param for that specific part.
            // This avoids complex dynamic binding for the subquery's parameter.

            // Reverting to simpler SQL for total_expected_sessions within the main query.
            // The previous code had `CONCAT(cs_sub.session_date, ' ', cs_sub.actual_end_time) < NOW()`.
            // If we want to filter by $end_date, it's safer to build the string.

            // Let's keep the `total_expected_sessions` calculation simpler for the main query.
            // If `end_date` is important for `total_expected_sessions`, it should be part of the main query's logic.
            // For now, the current setup of `total_expected_sessions` is based on "past sessions relative to now".
            // If you want "past sessions relative to filter end_date", the SQL needs dynamic string insertion.
            // Let's use the provided $end_date for the `total_expected_sessions` calculation in the SQL.
            // The `total_expected_sessions` subquery should reflect the `end_date` of the report.

            // Corrected approach for `total_expected_sessions` to respect `end_date` filter
            if (!empty($end_date)) {
                $expected_sessions_end_condition = "CONCAT(cs_sub.session_date, ' ', cs_sub.actual_end_time) <= '" . $conn->real_escape_string($end_date) . " 23:59:59'";
            } else {
                $expected_sessions_end_condition = "CONCAT(cs_sub.session_date, ' ', cs_sub.actual_end_time) < NOW()";
            }

            $sql = "
                SELECT
                    u.full_name AS faculty_name,
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

            // Parameters for the main query
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

            $sql .= " GROUP BY u.id, u.full_name, c.class_id, c.class_code, c.class_name, c.semester, c.academic_year
                      ORDER BY u.full_name, c.class_name";

            $stmt_report = $conn->prepare($sql);

            if ($stmt_report === false) { // Re-check prepare error after modifying SQL
                $_SESSION['message'] = "Error preparing report query (after date adjustment): " . $conn->error;
                $_SESSION['message_type'] = "danger";
            } else {
                if (!empty($params)) {
                    $stmt_report->bind_param($types, ...$params);
                }
                $stmt_report->execute();
                $result_report = $stmt_report->get_result();
                while ($row = $result_report->fetch_assoc()) {
                    $report_data[] = $row;
                }
                $stmt_report->close();
            }
        }
    }
}
require_once '../../templates/admin/header_admin.php';
require_once '../../templates/admin/sidenav_admin.php';
?>
<link rel="stylesheet" href="../../assets/css/styles.css">
<style>
    /* Add any specific styles for reports here */
    .report-table th, .report-table td {
        white-space: nowrap; /* Prevent wrapping for better table readability */
        padding: 8px;
    }
    .report-table tbody tr:hover {
        background-color: #f8f9fa;
    }
</style>

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
                <h5 class="mb-0">Generate Usage Report</h5>
            </div>
            <div class="card-body">
                <form action="report_generator.php" method="GET" class="form-inline mb-4">
                    <div class="form-group mr-3 mb-2">
                        <label for="faculty_id" class="mr-2">Faculty:</label>
                        <select class="form-control" id="faculty_id" name="faculty_id">
                            <option value="">All Faculty</option>
                            <?php foreach ($faculty_members as $faculty): ?>
                                <option value="<?= htmlspecialchars($faculty['id']) ?>"
                                    <?= ($filter_faculty_id == $faculty['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($faculty['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mr-3 mb-2">
                        <label for="start_date" class="mr-2">Start Date:</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                    </div>
                    <div class="form-group mr-3 mb-2">
                        <label for="end_date" class="mr-2">End Date:</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                    </div>
                    <button type="submit" name="generate_report" value="1" class="btn btn-primary mb-2 mr-2"><i class="fas fa-file-alt"></i> Generate Report</button>

                    <?php if (!empty($report_data)): ?>
                        <a href="../../actions/admin/export_report_pdf.php?<?= http_build_query($_GET) ?>" class="btn btn-danger mb-2">
                            <i class="fas fa-file-pdf"></i> Export to PDF
                        </a>
                    <?php endif; ?>
                </form>

                <?php if (isset($_GET['generate_report']) && empty($report_data)): ?>
                    <div class="alert alert-info">No data found for the selected filters.</div>
                <?php elseif (!empty($report_data)): ?>
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
                                        // Calculate attendance marked percentage
                                        $attendance_percentage = 0;
                                        // Ensure total_sessions_recorded is not zero before division
                                        if ($row['total_students_in_class'] > 0 && $row['total_sessions_recorded'] > 0) {
                                            $possible_records = $row['total_students_in_class'] * $row['total_sessions_recorded'];
                                            if ($possible_records > 0) {
                                                $attendance_percentage = ($row['total_attendance_marked'] / $possible_records) * 100;
                                            }
                                        }
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['faculty_name']) ?></td>
                                        <td><?= htmlspecialchars($row['class_code']) ?></td>
                                        <td><?= htmlspecialchars($row['class_name']) ?></td>
                                        <td><?= htmlspecialchars($row['semester'] ?? 'N/A') ?> (<?= htmlspecialchars($row['academic_year'] ?? 'N/A') ?>)</td>
                                        <td><?= htmlspecialchars($row['total_sessions_recorded']) ?></td>
                                        <td><?= htmlspecialchars($row['total_expected_sessions']) ?></td>
                                        <td><?= number_format($attendance_percentage, 2) ?>%</td>
                                        <td><?= htmlspecialchars($row['total_students_in_class']) ?></td>
                                        <td><?= htmlspecialchars($row['total_present']) ?></td>
                                        <td><?= htmlspecialchars($row['total_absent']) ?></td>
                                        <td><?= htmlspecialchars($row['total_late']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">Use the filters above to generate a report.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>

<script src="../../assets/js/jquery.min.js"></script>
<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/script.js"></script>