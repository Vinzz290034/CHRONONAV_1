<?php
// CHRONONAV_WEB_DOSS/templates/admin/sidenav_admin.php
// This file assumes $current_page is set in the including script (e.g., pages/admin/dashboard.php)

// Paths are relative to this file's location (templates/admin/)
// So, to go to chrononav_web_doss/, we go up two levels.
$base_path = '../../';

$app_logo_path = $base_path . 'assets/img/chrononav_logo.jpg'; // Assuming your logo is .png
$app_name = "ChronoNav";

// The $user variable is typically available from a session check done in the main page
// before including this sidenav. If you need user role for conditional links, ensure $user is passed.
// For example: $user_role = $_SESSION['user']['role'] ?? 'guest';

?>
<link rel="stylesheet" href="../../assets/css/other_css/sidenav_user.css"> 

<div class="app-sidebar">
    <ul class="app-sidebar-menu">
        <li class="nav-item">
            <a class="nav-link <?= ($current_page === 'dashboard') ? 'active' : '' ?>" href="<?= $base_path ?>pages/admin/dashboard.php">
                <i class="fas fa-home"></i>
                <span class="nav-link-text">Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($current_page === 'my_schedule') ? 'active' : '' ?>" href="<?= $base_path ?>pages/admin/schedule.php">
                <i class="fas fa-calendar-alt"></i>
                <span class="nav-link-text">Schedule</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($current_page === 'profile') ? 'active' : '' ?>" href="<?= $base_path ?>pages/admin/view_profile.php">
                <i class="fas fa-user-circle"></i>
                <span class="nav-link-text">Profile</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($current_page === 'report_generator') ? 'active' : '' ?>" href="<?= $base_path ?>pages/admin/report_generator.php">
                <i class="fas fa-file-alt"></i>
                <span class="nav-link-text">Report Generator</span>
            </a>
        </li>
        </ul>
</div>

<style>

</style>