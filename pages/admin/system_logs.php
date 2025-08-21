<?php
// CHRONONAV_WEBZ/pages/admin/system_logs.php

require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php'; // This should establish $conn

// Ensure only admins can access this page
if ($_SESSION['user']['role'] !== 'admin') {
    $_SESSION['message'] = "Access denied. You do not have permission to view this page.";
    $_SESSION['message_type'] = "danger";
    header("Location: ../user/dashboard.php"); // Redirect unauthorized users
    exit();
}

$page_title = "System Logs & Activity";
$current_page = "system_logs";

$message = '';
$message_type = '';

// --- Filtering Parameters ---
$filter_username = $_GET['username'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_start_date = $_GET['start_date'] ?? '';
$filter_end_date = $_GET['end_date'] ?? '';

$sql = "SELECT la.*, u.name AS user_full_name FROM login_attempts la LEFT JOIN users u ON la.user_id = u.id WHERE 1=1";
$params = [];
$param_types = "";

if (!empty($filter_username)) {
    $sql .= " AND la.username LIKE ?";
    $params[] = '%' . $filter_username . '%';
    $param_types .= "s";
}
if (!empty($filter_status)) {
    $sql .= " AND la.status = ?";
    $params[] = $filter_status;
    $param_types .= "s";
}
if (!empty($filter_start_date)) {
    $sql .= " AND la.attempt_time >= ?";
    $params[] = $filter_start_date . " 00:00:00"; // Start of the day
    $param_types .= "s";
}
if (!empty($filter_end_date)) {
    $sql .= " AND la.attempt_time <= ?";
    $params[] = $filter_end_date . " 23:59:59"; // End of the day
    $param_types .= "s";
}

$sql .= " ORDER BY la.attempt_time DESC";

// --- Export Logic ---
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="system_logs_' . date('Ymd_His') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'User ID', 'Username (Attempted)', 'Full Name (if known)', 'IP Address', 'Attempt Time', 'Status', 'User Agent']); // CSV Header

    $stmt_export = $conn->prepare($sql);
    if ($param_types) {
        $stmt_export->bind_param($param_types, ...$params);
    }
    $stmt_export->execute();
    $result_export = $stmt_export->get_result();

    while ($row = $result_export->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['user_id'],
            $row['username'],
            $row['user_full_name'], // Added full name
            $row['ip_address'],
            $row['attempt_time'],
            $row['status'],
            $row['user_agent']
        ]);
    }

    fclose($output);
    $stmt_export->close();
    // Keep $conn open here because it will be closed by footer.php or script termination
    // $conn->close(); // <-- REMOVE THIS LINE
    exit(); // Terminate script after CSV export
}

// --- Fetch Logs for Display ---
$logs = [];
$stmt = $conn->prepare($sql);
if ($param_types) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}
$stmt->close();
// REMOVE THE FOLLOWING LINE: The connection should be closed once at the end of the script,
// likely in a common footer or a dedicated shutdown function.
// $conn->close();
?>


<?php include_once '../../templates/header.php'; ?>
<?php include_once '../../templates/sidenav.php'; ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/admin_css/system_log.css"> 

<div class="main-content-wrapper">
    <div class="main-dashboard-content system-logs-page">
        <div class="system-logs-header">
            <h1>System Logs & Activity</h1>
            <a href="../user/dashboard.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="logs-filter-section">
            <h2><i class="fas fa-filter"></i> Filter Logs</h2>
            <form action="system_logs.php" method="GET" class="filter-form">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="filter_username">Username:</label>
                        <input type="text" id="filter_username" name="username" class="form-control" value="<?= htmlspecialchars($filter_username) ?>" placeholder="Filter by username">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="filter_status">Status:</label>
                        <select id="filter_status" name="status" class="form-control">
                            <option value="">All</option>
                            <option value="success" <?= ($filter_status === 'success') ? 'selected' : '' ?>>Success</option>
                            <option value="failed" <?= ($filter_status === 'failed') ? 'selected' : '' ?>>Failed</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="filter_start_date">Start Date:</label>
                        <input type="date" id="filter_start_date" name="start_date" class="form-control" value="<?= htmlspecialchars($filter_start_date) ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="filter_end_date">End Date:</label>
                        <input type="date" id="filter_end_date" name="end_date" class="form-control" value="<?= htmlspecialchars($filter_end_date) ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply Filters</button>
                <a href="system_logs.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Clear Filters</a>
                <a href="system_logs.php?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-success float-right"><i class="fas fa-file-excel"></i> Export to CSV</a>
            </form>
        </div>

        <div class="logs-display-section mt-4">
            <h2><i class="fas fa-history"></i> Login Records</h2>
            <?php if (!empty($logs)): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped logs-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Attempted Username</th>
                                <th>User (if known)</th>
                                <th>IP Address</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>User Agent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars($log['id']) ?></td>
                                <td><?= htmlspecialchars($log['username']) ?></td>
                                <td><?= htmlspecialchars($log['user_full_name'] ?? 'N/A') ?> (ID: <?= htmlspecialchars($log['user_id'] ?? 'N/A') ?>)</td>
                                <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                <td><?= htmlspecialchars($log['attempt_time']) ?></td>
                                <td>
                                    <span class="badge badge-<?= ($log['status'] === 'success') ? 'success' : 'danger' ?>">
                                        <?= htmlspecialchars(ucfirst($log['status'])) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars(substr($log['user_agent'], 0, 100)) ?>...</td> </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No login records found matching the criteria.</p>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php include_once '../../templates/footer.php'; ?>

<script src="../../assets/js/jquery.min.js"></script>
<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/script.js"></script>

