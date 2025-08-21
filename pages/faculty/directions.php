<?php
// CHRONONAV_WEB_DOSS/pages/faculty/directions.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

requireRole(['faculty']);

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

$user = $_SESSION['user'];

// --- START: Profile Dropdown Data (same as above) ---
function getProfileDropdownData($conn, $userId) { /* ... same function content ... */
    $data = [
        'display_username' => 'Unknown User',
        'display_user_role' => 'Role Not Set',
        'profile_img_src' => '../../uploads/profiles/default-avatar.png'
    ];

    $stmt = $conn->prepare("SELECT name, email, profile_img, role FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user_from_db = $result->fetch_assoc();
            $_SESSION['user'] = array_merge($_SESSION['user'], $user_from_db);
            $data['display_username'] = htmlspecialchars($user_from_db['name']);
            $data['display_user_role'] = htmlspecialchars(ucfirst($user_from_db['role']));
            if (!empty($user_from_db['profile_img'])) {
                $display_profile_img = htmlspecialchars($user_from_db['profile_img']);
                $data['profile_img_src'] = (strpos($display_profile_img, 'uploads/') === 0) ? '../../' . $display_profile_img : $display_profile_img;
            }
        } else {
            error_log("Security Alert: User ID {$userId} in session not found in database for directions page (faculty).");
            session_destroy();
            header('Location: ../../auth/login.php?error=user_data_missing');
            exit();
        }
        $stmt->close();
    } else {
        error_log("Database query preparation failed in getProfileDropdownData (faculty/directions): " . $conn->error);
    }
    return $data;
}
extract(getProfileDropdownData($conn, $user['id']));
// --- END: Profile Dropdown Data ---


// Page specific variables for header and sidenav
$page_title = "Directions";
$current_page = "directions"; // IMPORTANT: Matches the 'directions' in sidenav_faculty.php
require_once '../../templates/faculty/header_faculty.php'; 
?>
<?php require_once '../../templates/faculty/sidenav_faculty.php'; ?>

<div class="main-content-wrapper">
    <div class="main-dashboard-content">
        <div class="section-content-card card p-4">
            <h2>Campus Directions</h2>
            <p>This page will provide navigation and maps for various campus locations relevant to faculty.</p>
            <div class="map-placeholder mt-4">
                <img src="https://via.placeholder.com/800x400?text=Campus+Map+Goes+Here" alt="Campus Map" class="img-fluid">
                <p class="text-muted mt-2">Example: Interactive campus map or list of key locations.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>

<script src="../../assets/js/jquery.min.js"></script>
<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/script.js"></script>