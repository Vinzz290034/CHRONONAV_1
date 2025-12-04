<?php
// CHRONONAV_WEB_DOSS/pages/admin/export_logs.php
// Handles filtering and exporting audit log data as a CSV file.

// No need for output buffering here as we only output file headers and data.

require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

/** @var \mysqli $conn */

// 1. Ensure only 'admin' role can access this file
if (function_exists('requireRole')) {
    requireRole(['admin']);
}

// Get filter parameters from the URL (same as audit_logs.php)
$filter_user_id = filter_var($_GET['user_id'] ?? '', FILTER_VALIDATE_INT);
$filter_action = trim($_GET['action'] ?? '');
$filter_start_date = trim($_GET['start_date'] ?? '');
$filter_end_date = trim($_GET['end_date'] ?? '');

$sql_conditions = [];
$sql_types = '';
$sql_params = [];

// Apply Filtering Logic (Duplicated from audit_logs.php)
if ($filter_user_id !== false && $filter_user_id > 0) {
    $sql_conditions[] = "al.user_id = ?";
    $sql_types .= 'i';
    $sql_params[] = $filter_user_id;
}
if (!empty($filter_action)) {
    $sql_conditions[] = "al.action LIKE ?";
    $sql_types .= 's';
    $sql_params[] = '%' . $filter_action . '%';
}
if (!empty($filter_start_date)) {
    $sql_conditions[] = "al.timestamp >= ?";
    $sql_types .= 's';
    $sql_params[] = $filter_start_date . ' 00:00:00';
}
if (!empty($filter_end_date)) {
    $sql_conditions[] = "al.timestamp <= ?";
    $sql_types .= 's';
    $sql_params[] = $filter_end_date . ' 23:59:59';
}

$where_clause = empty($sql_conditions) ? "" : " WHERE " . implode(' AND ', $sql_conditions);


// 2. Construct Final SQL Query (NO LIMIT)
$sql_query = "
    SELECT al.id, al.timestamp, al.action, al.details,
           u.name AS user_name, u.role AS user_role
    FROM audit_log al
    JOIN users u ON al.user_id = u.id
    " . $where_clause . "
    ORDER BY al.timestamp DESC
";

// 3. Prepare and Execute Query
$stmt = $conn->prepare($sql_query);
$logs = [];

if ($stmt) {
    if (!empty($sql_params)) {
        // Bind parameters dynamically
        $bind_names = [$sql_types];
        for ($i = 0; $i < count($sql_params); $i++) {
            $bind_names[] = &$sql_params[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_names);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $logs = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    // If query preparation fails, output a simple error message instead of CSV
    die("Error preparing export query: " . $conn->error);
}


// 4. Generate CSV Headers
$filename = 'chrononav_audit_log_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// 5. Output CSV Data
$output = fopen('php://output', 'w');

// Define CSV Column Headers (must match SELECT order)
$csv_headers = ['Log ID', 'Timestamp', 'Action', 'Details', 'User Name', 'User Role'];
fputcsv($output, $csv_headers);

// Write data rows
foreach ($logs as $log) {
    // Manually format the data row to match headers
    fputcsv($output, [
        $log['id'],
        $log['timestamp'],
        $log['action'],
        $log['details'],
        $log['user_name'],
        $log['user_role']
    ]);
}

fclose($output);
exit;

?>