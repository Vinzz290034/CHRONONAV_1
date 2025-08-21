<?php
// CHRONONAV_WEB_DOSS/templates/user/sidenav_user.php
// This file assumes $user (session data) and $current_page are set in the including script.
// $current_page should be a string like 'dashboard', 'my_schedule', 'directions', 'notifications', 'profile', etc.

// From templates/user/sidenav_user.php to chrononav_web_doss/
$base_app_path = '../../';

// From templates/user/sidenav_user.php to pages/user/
$base_user_pages_path = $base_app_path . 'pages/user/';

// Assuming you have chrononav_logo.png in assets/img/
$app_logo_path = $base_app_path . 'assets/img/chrononav_logo.png';
$app_name = "ChronoNav";
?>

<link rel="stylesheet" href="../../assets/css/other_css/sidenav_user.css"> 


<div class="app-sidebar">
    <ul class="app-sidebar-menu">
        <li class="nav-item">
            <a class="nav-link <?= ($current_page === 'dashboard') ? 'active' : '' ?>" href="<?= $base_user_pages_path ?>dashboard.php">
                <i class="fas fa-home"></i>
                <span class="nav-link-text">Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($current_page === 'my_schedule') ? 'active' : '' ?>" href="<?= $base_user_pages_path ?>schedule.php">
                <i class="fas fa-calendar-alt"></i>
                <span class="nav-link-text">Schedule</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($current_page === 'directions') ? 'active' : '' ?>" href="<?= $base_user_pages_path ?>directions.php">
                <i class="fas fa-map-signs"></i>
                <span class="nav-link-text">Directions</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($current_page === 'notification_preferences') ? 'active' : '' ?>" href="<?= $base_user_pages_path ?>notification_preferences.php">
                <i class="fas fa-bell"></i>
                <span class="nav-link-text">Notifications</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($current_page === 'profile') ? 'active' : '' ?>" href="<?= $base_user_pages_path ?>view_profile.php">
                <i class="fas fa-user-circle"></i>
                <span class="nav-link-text">My Profile</span>
            </a>
        </li>
    </ul>
</div>