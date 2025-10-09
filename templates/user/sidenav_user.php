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

<style>
    /* Sidenav General Styles */
    .app-sidebar {
        font-family: 'Space Grotesk', 'Noto Sans', sans-serif;
        width: 20%;
        background-color: #fff;
        padding: 1rem;
        position: fixed;
        height: 50vh;
        overflow-y: auto;
        z-index: 1000;
        display: flex;
        flex-direction: column;
    }

    /* Sidebar Header (Logo and App Name) */
    .sidebar-header {
        display: flex;
        align-items: center;
        padding: 15px 20px;
        margin-bottom: 20px;
        border-bottom: 1px solid rgba(239, 232, 232, 0.1);
        justify-content: center;
    }

    /* Transparent Scrollbar */
    .app-sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .app-sidebar::-webkit-scrollbar-track {
        background: transparent;
    }

    .app-sidebar::-webkit-scrollbar-thumb {
        background-color: transparent;
        border-radius: 20px;
        border: none;
    }

    /* Firefox support */
    .app-sidebar {
        scrollbar-width: thin;
        scrollbar-color: transparent transparent;
    }

    .logo-container {
        display: flex;
        align-items: center;
        text-decoration: none;
        color: rgba(0, 0, 0, 1);
        font-size: 1.5rem;
        font-weight: 700;
    }

    .app-logo {
        width: 45px;
        color: rgba(0, 0, 0, 1);
        height: 45px;
        object-fit: contain;
        margin-right: 12px;
        border-radius: 5px;
    }

    /* Sidebar Menu - Updated with observed design */
    .app-sidebar-menu {
        flex-grow: 1;
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .app-sidebar-menu .nav-item {
        margin-bottom: 0.25rem;
    }

    .app-sidebar-menu .nav-link {
        display: flex;
        align-items: center;
        padding: 0.5rem 0.75rem;
        color: #111418;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
        transition: all 0.3s ease;
        border-radius: 0.75rem;
        gap: 0.75rem;
        margin: 0;
    }

    .app-sidebar-menu .nav-link svg {
        width: 24px;
        height: 24px;
        color: #111418;
        flex-shrink: 0;
    }

    /* Hover State - Updated */
    .app-sidebar-menu .nav-link:hover {
        background-color: #f8f9fa;
        color: #111418;
        transform: none;
        box-shadow: none;
    }

    .app-sidebar-menu .nav-link:hover svg {
        color: #111418;
    }

    /* Active State - Updated */
    .app-sidebar-menu .nav-link.active {
        background-color: #f0f2f5;
        color: #111418;
        font-weight: 500;
        box-shadow: none;
        transform: none;
    }

    .app-sidebar-menu .nav-link.active svg {
        color: #111418;
    }

    .nav-link-text {
        color: inherit;
        font-size: 0.875rem;
        font-weight: 500;
        line-height: 1.25;
        margin: 0;
    }

    /* Specific for Logout button at the bottom */
    .app-sidebar .nav-item.mt-auto {
        margin-top: auto;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        padding-top: 15px;
        margin-top: 20px;
    }

    .app-sidebar .nav-item.mt-auto .nav-link {
        color: #e74c3c;
        font-weight: 600;
    }

    .app-sidebar .nav-item.mt-auto .nav-link svg {
        color: #e74c3c;
    }

    .app-sidebar .nav-item.mt-auto .nav-link:hover {
        background-color: #c0392b;
        color: #ffffff;
    }

    .app-sidebar .nav-item.mt-auto .nav-link:hover svg {
        color: #ffffff;
    }

    /* Media Queries for Responsiveness */
    @media (max-width: 768px) {
        .app-sidebar {
            width: 100%;
            min-height: auto;
            padding-top: 10px;
        }

        .sidebar-header {
            padding: 10px;
            justify-content: center;
        }

        .app-name {
            display: none;
        }

        .app-logo {
            margin-right: 0;
        }

        .app-sidebar-menu .nav-link {
            padding: 12px 0;
            justify-content: center;
            margin: 0 5px;
        }

        .app-sidebar-menu .nav-link svg {
            margin-right: 0;
        }

        .app-sidebar-menu .nav-link .nav-link-text {
            display: none;
        }

        .app-sidebar-menu .nav-item.active .nav-link {
            transform: translateX(0);
        }
    }

    /* Basic styling for the main content area to make space for the sidebar */
    .main-content-wrapper {
        margin-left: 320px;
        transition: margin-left 0.3s ease;
    }

    @media (max-width: 768px) {
        .main-content-wrapper {
            margin-left: 0;
        }
    }

    /* ====================================================================== */
    /* Dark Mode Overrides                          */
    /* ====================================================================== */
    body.dark-mode .app-sidebar {
        background-color: #1e1e1e;
        border-right: 1px solid #333;
        color: #ffffff;
    }

    body.dark-mode .sidebar-header {
        border-bottom: 1px solid #444;
    }

    body.dark-mode .logo-container {
        color: #ffffff;
    }

    body.dark-mode .app-sidebar-menu .nav-link {
        color: #e0e0e0;
    }

    body.dark-mode .app-sidebar-menu .nav-link svg {
        color: #b0b0b0;
    }

    body.dark-mode .app-sidebar-menu .nav-link:hover {
        background-color: #2c2c2c;
        color: #ffffff;
    }

    body.dark-mode .app-sidebar-menu .nav-link:hover svg {
        color: #ffffff;
    }

    body.dark-mode .app-sidebar-menu .nav-link.active {
        background-color: #2c2c2c;
        color: #ffffff;
    }

    body.dark-mode .app-sidebar-menu .nav-link.active svg {
        color: #ffffff;
    }

    body.dark-mode .app-sidebar .nav-item.mt-auto {
        border-top: 1px solid #444;
    }
</style>

<div class="app-sidebar" id="sidebar">
    <div class="app-sidebar-menu">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= ($current_page === 'dashboard') ? 'active' : '' ?>"
                    href="<?= $base_path ?>pages/user/dashboard.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" fill="currentColor"
                        viewBox="0 0 256 256">
                        <path
                            d="M218.83,103.77l-80-75.48a1.14,1.14,0,0,1-.11-.11,16,16,0,0,0-21.53,0l-.11.11L37.17,103.77A16,16,0,0,0,32,115.55V208a16,16,0,0,0,16,16H96a16,16,0,0,0,16-16V160h32v48a16,16,0,0,0,16,16h48a16,16,0,0,0,16-16V115.55A16,16,0,0,0,218.83,103.77ZM208,208H160V160a16,16,0,0,0-16-16H112a16,16,0,0,0-16,16v48H48V115.55l.11-.1L128,40l79.9,75.43.11.1Z">
                        </path>
                    </svg>
                    <span class="nav-link-text fs-6">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($current_page === 'calendar') ? 'active' : '' ?>"
                    href="<?= $base_path ?>pages/user/calendar.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" fill="currentColor"
                        viewBox="0 0 256 256">
                        <path
                            d="M208,32H184V24a8,8,0,0,0-16,0v8H88V24a8,8,0,0,0-16,0v8H48A16,16,0,0,0,32,48V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V48A16,16,0,0,0,208,32ZM72,48v8a8,8,0,0,0,16,0V48h80v8a8,8,0,0,0,16,0V48h24V80H48V48ZM208,208H48V96H208V208Zm-96-88v64a8,8,0,0,1-16,0V132.94l-4.42,2.22a8,8,0,0,1-7.16-14.32l16-8A8,8,0,0,1,112,120Zm59.16,30.45L152,176h16a8,8,0,0,1,0,16H136a8,8,0,0,1-6.4-12.8l28.78-38.37A8,8,0,1,0,145.07,132a8,8,0,1,1-13.85-8A24,24,0,0,1,176,136,23.76,23.76,0,0,1,171.16,150.45Z">
                        </path>
                    </svg>
                    <span class="nav-link-text fs-6">Calendar</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($current_page === 'schedule') ? 'active' : '' ?>"
                    href="<?= $base_path ?>pages/user/schedule.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" fill="currentColor"
                        viewBox="0 0 256 256">
                        <path
                            d="M208,32H184V24a8,8,0,0,0-16,0v8H88V24a8,8,0,0,0-16,0v8H48A16,16,0,0,0,32,48V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V48A16,16,0,0,0,208,32ZM72,48v8a8,8,0,0,0,16,0V48h80v8a8,8,0,0,0,16,0V48h24V80H48V48ZM208,208H48V96H208V208Zm-96-88v64a8,8,0,0,1-16,0V132.94l-4.42,2.22a8,8,0,0,1-7.16-14.32l16-8A8,8,0,0,1,112,120Zm59.16,30.45L152,176h16a8,8,0,0,1,0,16H136a8,8,0,0,1-6.4-12.8l28.78-38.37A8,8,0,1,0,145.07,132a8,8,0,1,1-13.85-8A24,24,0,0,1,176,136,23.76,23.76,0,0,1,171.16,150.45Z">
                        </path>
                    </svg>
                    <span class="nav-link-text fs-6">Schedule</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= ($current_page === 'profile') ? 'active' : '' ?>"
                    href="<?= $base_path ?>pages/user/view_profile.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" fill="currentColor"
                        viewBox="0 0 256 256">
                        <path
                            d="M230.92,212c-15.23-26.33-38.7-45.21-66.09-54.16a72,72,0,1,0-73.66,0C63.78,166.78,40.31,185.66,25.08,212a8,8,0,1,0,13.85,8c18.84-32.56,52.14-52,89.07-52s70.23,19.44,89.07,52a8,8,0,1,0,13.85-8ZM72,96a56,56,0,1,1,56,56A56.06,56.06,0,0,1,72,96Z">
                        </path>
                    </svg>
                    <span class="nav-link-text fs-6">Profile</span>
                </a>
            </li>
        </ul>
    </div>
    <div class="app-version text-left py-3 m-3 fw-bold">
        <p class="text-muted mb-0">ChronoNav v1.0</p>
    </div>
</div>