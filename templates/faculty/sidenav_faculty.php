<?php
// CHRONONAV_WEB_DOSS/templates/faculty/sidenav_faculty.php
// This file assumes $user (session data) and $current_page are set in the including script.
// $current_page should be a string like 'dashboard', 'my_classes', 'office_hours', 'feedback', 'profile', etc.

// From templates/faculty/sidenav_faculty.php to chrononav_web_doss/
$base_app_path = '../../';

// From templates/faculty/sidenav_faculty.php to pages/faculty/
$base_faculty_pages_path = $base_app_path . 'pages/faculty/';

// Assuming you have chrononav_logo.png in assets/images/
$app_logo_path = $base_app_path . 'assets/img/chrononav_logo.jpg';
$app_name = "ChronoNav";

// The $user variable is typically available from a session check done in the main page
// before including this sidenav.
?>
<link rel="stylesheet" href="../../assets/css/other_css/sidenav_user.css"> 
<div class="app-sidebar">
    
    <ul class="app-sidebar-menu">
        <li class="nav-item">
            <a class="nav-link <?= ($current_page === 'dashboard') ? 'active' : '' ?>" href="<?= $base_faculty_pages_path ?>dashboard.php">
                <i class="fas fa-home"></i>
                <span class="nav-link-text">Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($current_page === 'my_schedule') ? 'active' : '' ?>" href="<?= $base_faculty_pages_path ?>calendar.php"> <i class="fas fa-calendar-alt"></i>
                <span class="nav-link-text">Calendar</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($current_page === 'schedule') ? 'active' : '' ?>" href="<?= $base_faculty_pages_path ?>schedule.php">
                <i class="fas fa-list"></i>
                <span class="nav-link-text">Schedule</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($current_page === 'my_classes') ? 'active' : '' ?>" href="<?= $base_faculty_pages_path ?>my_classes.php">
                <i class="fas fa-chalkboard-teacher"></i>
                <span class="nav-link-text">Classes</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link <?= ($current_page === 'notification_preferences') ? 'active' : '' ?>" href="<?= $base_faculty_pages_path ?>notification_preferences.php">
                <i class="fas fa-bell"></i>
                <span class="nav-link-text">Notifications</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($current_page === 'profile') ? 'active' : '' ?>" href="<?= $base_faculty_pages_path ?>view_profile.php">
                <i class="fas fa-user-circle"></i>
                <span class="nav-link-text">Profile</span>
            </a>
        </li>
    </ul>
</div>

