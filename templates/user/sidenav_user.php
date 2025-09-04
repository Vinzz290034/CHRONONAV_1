<?php
// CHRONONAV_WEB_DOSS/templates/user/sidenav_user.php

// This file assumes $current_page is set in the including script (e.g., pages/user/dashboard.php)
// Paths are relative to this file's location (templates/user/)
$base_path = '../../';

// Define the name and logo of the application
$app_name = "ChronoNav";
$app_logo_path = $base_path . 'assets/img/chrononav_logo.jpg';

if (!isset($current_page)) {
    $current_page = '';
}
?>

<link rel="stylesheet" href="../../assets/css/other_css/sidenav_users.css">

<div class="app-sidebar" id="sidebar">
    <div class="app-sidebar-menu">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= ($current_page === 'dashboard') ? 'active' : '' ?>" href="<?= $base_path ?>pages/user/dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="nav-link-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($current_page === 'calendar') ? 'active' : '' ?>" href="<?= $base_path ?>pages/user/calendar.php">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="nav-link-text">Calendar</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($current_page === 'schedule') ? 'active' : '' ?>" href="<?= $base_path ?>pages/user/schedule.php">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="nav-link-text">Schedule</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= ($current_page === 'profile') ? 'active' : '' ?>" href="<?= $base_path ?>pages/user/view_profile.php">
                    <i class="fas fa-user-circle"></i>
                    <span class="nav-link-text">Profile</span>
                </a>
            </li>
        </ul>
    </div>
    <div class="app-version text-center py-3">
        <p class="text-muted mb-0">ChronoNav v1.0</p>
    </div>
</div>