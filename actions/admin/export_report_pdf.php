<?php
// CHRONONAV_WEB_DOSS/actions/admin/export_report_pdf.php
require_once '../../config/db_connect.php';

// Composer Autoloader - This is the corrected line
require_once '../../vendor/autoload.php';

// Use statements for Dompdf classes
use Dompdf\Dompdf;
use Dompdf\Options;

// Retrieve filter parameters from GET request
$filter_faculty_id = $_GET['faculty_id'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Report Data retrieval logic
$report_data = [];

// Determine the threshold for 'total_expected_sessions' based on end_date filter
if (!empty($end_date)) {
    $expected_sessions_end_condition = "CONCAT(cs_sub.session_date, ' ', cs_sub.actual_end_time) <= '" . $conn->real_escape_string($end_date) . " 23:59:59'";
} else {
    $expected_sessions_end_condition = "CONCAT(cs_sub.session_date, ' ', cs_sub.actual_end_time) < NOW()";
}

$sql = "
    SELECT
        u.name AS faculty_name,
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

$stmt_report = $conn->prepare($sql);
if ($stmt_report === false) {
    error_log("Error preparing report query: " . $conn->error);
    die("Error preparing report query.");
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

$conn->close();

// HTML for the PDF
$html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Report</title>
    <style>
        body { font-family: "Helvetica", "Arial", sans-serif; font-size: 12px; }
        h1, h2 { text-align: center; }
        .report-info { margin-bottom: 20px; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header-logo { text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header-logo">
        </div>
    <h1>Attendance Monitoring System Report</h1>
    <div class="report-info">
        <p><strong>Date Generated:</strong> ' . date('Y-m-d H:i:s') . '</p>
        <p><strong>Faculty ID:</strong> ' . (empty($filter_faculty_id) ? 'All Faculty' : htmlspecialchars($filter_faculty_id)) . '</p>
        <p><strong>Date Range:</strong> ' . (empty($start_date) ? 'N/A' : htmlspecialchars($start_date)) . ' to ' . (empty($end_date) ? 'N/A' : htmlspecialchars($end_date)) . '</p>
    </div>
    <table>
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
        <tbody>';

foreach ($report_data as $row) {
    $attendance_percentage = 0;
    if ($row['total_students_in_class'] > 0 && $row['total_sessions_recorded'] > 0) {
        $possible_records = $row['total_students_in_class'] * $row['total_sessions_recorded'];
        if ($possible_records > 0) {
            $attendance_percentage = ($row['total_attendance_marked'] / $possible_records) * 100;
        }
    }
    $html .= '<tr>
        <td>' . htmlspecialchars($row['faculty_name']) . '</td>
        <td>' . htmlspecialchars($row['class_code']) . '</td>
        <td>' . htmlspecialchars($row['class_name']) . '</td>
        <td>' . htmlspecialchars($row['semester'] ?? 'N/A') . ' (' . htmlspecialchars($row['academic_year'] ?? 'N/A') . ')</td>
        <td>' . htmlspecialchars($row['total_sessions_recorded']) . '</td>
        <td>' . htmlspecialchars($row['total_expected_sessions']) . '</td>
        <td>' . number_format($attendance_percentage, 2) . '%</td>
        <td>' . htmlspecialchars($row['total_students_in_class']) . '</td>
        <td>' . htmlspecialchars($row['total_present']) . '</td>
        <td>' . htmlspecialchars($row['total_absent']) . '</td>
        <td>' . htmlspecialchars($row['total_late']) . '</td>
    </tr>';
}

$html .= '
        </tbody>
    </table>
</body>
</html>';

// Instantiate Dompdf and render the HTML
try {
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    // Output the generated PDF to browser
    $dompdf->stream("attendance_report_" . date('Y-m-d') . ".pdf", array("Attachment" => true));
} catch (Exception $e) {
    die("An error occurred during PDF generation: " . $e->getMessage());
}
?>