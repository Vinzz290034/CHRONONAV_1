<?php
// CHRONONAV_WEB_DOSS/pages/faculty/notification_preferences.php

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
            error_log("Security Alert: User ID {$userId} in session not found in database for notifications page (faculty).");
            session_destroy();
            header('Location: ../../auth/login.php?error=user_data_missing');
            exit();
        }
        $stmt->close();
    } else {
        error_log("Database query preparation failed in getProfileDropdownData (faculty/notifications): " . $conn->error);
    }
    return $data;
}
extract(getProfileDropdownData($conn, $user['id']));
// --- END: Profile Dropdown Data ---


// Page specific variables for header and sidenav
$page_title = "Notification Preferences";
$current_page = "notification_preferences"; // IMPORTANT: Matches the 'notification_preferences' in sidenav_faculty.php

?>

<?php require_once '../../templates/faculty/header_faculty.php'; ?>
<?php require_once '../../templates/faculty/sidenav_faculty.php'; ?>

<div class="main-content-wrapper">
    <div class="main-dashboard-content">
        <div class="section-content-card card p-4">
            <h2>Manage Notification Preferences</h2>
            <p>Customize how you receive alerts and updates related to your classes, appointments, and general announcements.</p>

            <form action="process_notification_preferences.php" method="POST" class="mt-4">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="emailNotifications" name="email_notifications" checked>
                    <label class="form-check-label" for="emailNotifications">Email Notifications</label>
                    <small class="form-text text-muted">Receive important updates via email.</small>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="smsNotifications" name="sms_notifications">
                    <label class="form-check-label" for="smsNotifications">SMS Notifications</label>
                    <small class="form-text text-muted">Get urgent alerts directly to your phone (data rates may apply).</small>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="inAppNotifications" name="in_app_notifications" checked>
                    <label class="form-check-label" for="inAppNotifications">In-App Notifications</label>
                    <small class="form-text text-muted">See alerts directly within the ChronoNav application.</small>
                </div>

                <h5 class="mt-4 mb-3">Notification Categories</h5>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="scheduleChanges" name="category_schedule" checked>
                    <label class="form-check-label" for="scheduleChanges">Schedule Changes</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="newAppointments" name="category_appointments" checked>
                    <label class="form-check-label" for="newAppointments">New Appointments/Bookings</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="generalAnnouncements" name="category_announcements">
                    <label class="form-check-label" for="generalAnnouncements">General Announcements</label>
                </div>

                <button type="submit" class="btn btn-primary mt-4">Save Preferences</button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>
<script src="../../assets/js/jquery.min.js"></script>
<script src="../../assets/js/script.js"></script>