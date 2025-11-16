<?php
// CHRONONAV_WEB_DOSS/pages/admin/dashboard.php

require_once '../../middleware/auth_check.php';
require_once '../../includes/functions.php';
require_once '../../includes/db_connect.php';
require_once '../../includes/onboarding_functions.php';

// This role check ensures only 'admin' role can access this specific dashboard.
requireRole(['admin']);

$user = $_SESSION['user'];
$user_role = $user['role'] ?? 'guest'; // Get user role for conditional display

// Page-specific variables
$page_title = "Admin Dashboard";
$current_page = "admin_home";
$display_name = htmlspecialchars($user['name'] ?? 'Admin');

// --- Fetch Dashboard Data Dynamically ---
$total_users = 0;
$active_tickets = 0;
$new_announcements = 0;
$total_feedbacks = 0;
$total_rooms = 0;

// Variables for user roles counts
$admin_count = 0;
$faculty_count = 0;
$student_count = 0; // for 'user' role

// New variable for department counts
$department_counts = [];
$onboarding_steps = []; // Variable to hold onboarding steps

try {
    $pdo = get_db_connection();

    // Query for Total Users
    $stmt_users = $pdo->query("SELECT COUNT(*) AS total_users FROM users");
    $total_users = $stmt_users->fetchColumn();

    // Query for Active Tickets
    $stmt_tickets = $pdo->query("SELECT COUNT(*) AS active_tickets FROM tickets WHERE status IN ('open', 'in progress')");
    $active_tickets = $stmt_tickets->fetchColumn();

    // Query for New Announcements
    $stmt_announcements = $pdo->query("SELECT COUNT(*) AS new_announcements FROM announcements");
    $new_announcements = $stmt_announcements->fetchColumn();

    //Query for total feedback
    $stmt_feedbacks = $pdo->query("SELECT COUNT(*) AS total_feedbacks FROM feedback");
    $total_feedbacks = $stmt_feedbacks->fetchColumn();

    //Query for total room
    $stmt_rooms = $pdo->query("SELECT COUNT(*) AS total_rooms FROM rooms");
    $total_rooms = $stmt_rooms->fetchColumn();

    // --- Fetch User Role Counts for the Pie Chart ---
    $stmt_admin = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $admin_count = $stmt_admin->fetchColumn();

    $stmt_faculty = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'faculty'");
    $faculty_count = $stmt_faculty->fetchColumn();

    $stmt_student = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
    $student_count = $stmt_student->fetchColumn();

    // --- Fetch Department Counts for the new Pie Chart ---
    $stmt_departments = $pdo->query("SELECT department, COUNT(*) AS count FROM users GROUP BY department");
    $department_counts_raw = $stmt_departments->fetchAll(PDO::FETCH_ASSOC);

    foreach ($department_counts_raw as $row) {
        $departmentName = $row['department'] ?: 'Unassigned';
        $department_counts[$departmentName] = $row['count'];
    }

    // Fetch onboarding steps for the current user role
    $onboarding_steps = getOnboardingSteps($pdo, $user['role']);

} catch (PDOException $e) {
    error_log("Dashboard Data Fetch Error: " . $e->getMessage());
    $total_users = "Error";
    $active_tickets = "Error";
    $new_announcements = "Error";
    $admin_count = "Error";
    $faculty_count = "Error";
    $student_count = "Error";
    $total_feedbacks = "Error";
    $total_rooms = "Error";
    $department_counts = [];
}

// =========================================================================================
// Start of HTML Output
// =========================================================================================

require_once '../../templates/admin/header_admin.php';
?>

<link rel="stylesheet" href="../../assets/css/onboarding.css">

<style>
    .main-dashboard-content {
        margin-left: 20%;
        padding: 0px 35px;
    }

    .main-dashboard-content-wrapper {
        font-family: "Space Grotesk", "Noto Sans", sans-serif;
        min-height: 100vh;
        width: 100%;
    }

    /* Custom styles to match the exact design */
    .search-bar {
        display: flex;
        align-items: center;
        background-color: rgb(231, 231, 231);
        border-radius: 0.75rem;
        padding: 0px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        margin-top: 15px;
        margin-bottom: 25px;
    }

    .search-bar-custom {
        height: 48px;
        background-color: #eaedf1;
        border-radius: 0.75rem;
        border: none;
    }

    .search-bar-custom .input-group-text {
        background-color: #eaedf1;
        border: none;
        border-radius: 0.75rem 0 0 0.75rem;
        color: #5c748a;
        padding-left: 1rem;
    }

    .search-bar-custom .form-control {
        background-color: #eaedf1;
        border: none;
        border-radius: 0 0.75rem 0.75rem 0;
        color: #101518;
        padding-left: 0.5rem;
        height: 48px;
    }

    .search-bar-custom .form-control:focus {
        box-shadow: none;
        background-color: #eaedf1;
    }

    .search-bar-custom .form-control::placeholder {
        color: #5c748a;
    }

    .btn-custom-outline {
        background-color: #eaedf1;
        color: #101518;
        border: none;
        border-radius: 9999px;
        font-weight: 500;
        font-size: 0.875rem;
        padding: 8px 16px;
        min-width: 84px;
        height: 32px;
    }

    .btn-custom-primary {
        background-color: #dce8f3;
        color: #101518;
        border: none;
        border-radius: 9999px;
        font-weight: 500;
        font-size: 0.875rem;
        padding: 8px 16px;
        min-width: 84px;
        height: 32px;
    }

    .text-truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .welcome-title {
        font-size: 28px;
        font-weight: bold;
        color: #101518;
        line-height: 1.2;
    }

    .section-title {
        font-size: 22px;
        font-weight: bold;
        color: #101518;
        line-height: 1.2;
    }

    .onboarding-controls {
        background-color: #e9f5ff;
    }

    .onboarding-controls .btn-custom-blue,
    .onboarding-controls .btn-custom-primary,
    .onboarding-controls .btn-custom-outline {
        background: #E8EDF2;
    }

    /* Scrollbar Styling */
    ::-webkit-scrollbar {
        width: 12px;
        height: 12px;
    }

    ::-webkit-scrollbar-track {
        background: #ffffff;
    }

    ::-webkit-scrollbar-thumb {
        background-color: #737373;
        border-radius: 6px;
        border: 3px solid #ffffff;
    }

    ::-webkit-scrollbar-thumb:hover {
        background-color: #2e78c6;
    }

    /* Admin specific styles */
    .admin-links .list-group-item {
        border: none;
        padding: 16px 20px;
        background-color: #f9fafb;
        margin-bottom: 8px;
        border-radius: 0.5rem;
    }

    .admin-links .list-group-item a {
        color: #101518;
        text-decoration: none;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .profile.btn.btn-custom-outline {
        background: #2e77c67a;
    }

    .profile.btn.btn-custom-outline:hover {
        background: #2E78C6;
        color: #FFFFFF;
    }

    .admin-links .list-group-item a:hover {
        color: #2e78c6;
    }

    .admin-links .list-group-item small {
        color: #5c748a;
        font-size: 0.875rem;
        margin-top: 4px;
        padding-left: 2rem;
    }

    .d-flex.flex-wrap.gap-2 {
        color: #2e78c6;
    }

    .list-unstyled li a {
        color: #101518;
        text-decoration: none;
    }

    .list-unstyled li a:hover {
        font-weight: bold;
        color: #2e78c6;
    }

    .card {
        border: none;
        border-radius: 0.75rem;
        background: white;
    }

    .card-body h5 {
        color: #101518;
        font-weight: 600;
    }

    .card-text {
        color: #5c748a;
    }

    /* Enhanced Card Styles */
    .dashboard-overview-cards {
        flex-wrap: nowrap;
        /* Prevent wrapping */
        gap: 20px;
        /* Space between cards */
    }

    .dashboard-overview-cards .card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        height: 100%;
        background-color: #eaedf1;
    }

    .dashboard-overview-cards .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .card-container {
        width: 220px;
        /* Adjust to your preference */
        flex: 0 0 auto;
    }

    .card-box {
        width: 200px;
        /* Same fixed size for all cards */
        flex: 0 0 auto;
        /* Prevent shrinking */
    }


    .card-blue {
        border-top: 4px solid #007bff;
    }

    .card-teal {
        border-top: 4px solid #17a2b8;
    }

    .card-orange {
        border-top: 4px solid #fd7e14;
    }

    .card-purple {
        border-top: 4px solid #6f42c1;
    }

    .card-green {
        border-top: 4px solid #28a745;
    }

    .dashboard-overview-cards .card h5 {
        color: #0e151b;
        font-size: 1rem;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    .dashboard-overview-cards .card p {
        color: #2E78C6;
        font-size: 1.8rem;
        font-weight: bold;
        margin-bottom: 0;
    }

    .dashboard-overview-cards .card i {
        color: #737373;
    }





    /* Chart container styling */
    /* Enhanced Chart Styling */
    .chart-container {
        position: relative;
        height: 320px;
        width: 100%;
        background: #ffffff;
        border-radius: 12px;
        padding: 15px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .chart-card {
        background: #ffffff;
        border: 1px solid #e8edf3;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .chart-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .chart-card .card-body {
        padding: 20px;
    }

    .chart-title {
        font-family: "Inter", "Noto Sans", sans-serif;
        font-size: 16px;
        font-weight: 600;
        color: #0e151b;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .chart-title i {
        color: #1d7dd7;
        font-size: 18px;
    }

    canvas {
        max-width: 100% !important;
        height: auto !important;
    }

    /* Custom scrollbar for chart legends */
    .chart-legend-container {
        max-height: 200px;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: #d1dce6 #f8fafb;
    }

    .chart-legend-container::-webkit-scrollbar {
        width: 6px;
    }

    .chart-legend-container::-webkit-scrollbar-track {
        background: #f8fafb;
        border-radius: 3px;
    }

    .chart-legend-container::-webkit-scrollbar-thumb {
        background: #d1dce6;
        border-radius: 3px;
    }

    .chart-legend-container::-webkit-scrollbar-thumb:hover {
        background: #a8b5c1;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .chart-container {
            height: 280px;
            padding: 10px;
        }

        .chart-card .card-body {
            padding: 15px;
        }

        .chart-title {
            font-size: 14px;
        }
    }



    /* Add these media queries at the end of your existing CSS - EXACT SAME STRUCTURE AS FACULTY DASHBOARD */

    /* Mobile: 767px and below */
    @media (max-width: 767px) {
        .main-dashboard-content {
            margin-left: 0;
            padding: 15px;
        }

        .welcome-title {
            font-size: 22px;
        }

        .section-title {
            font-size: 18px;
        }

        .search-bar-custom {
            height: 42px;
        }

        .search-bar-custom .form-control {
            height: 42px;
            font-size: 0.875rem;
        }

        input#searchInput {
            padding-right: 1rem;
        }

        .dashboard-overview-cards {
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
        }

        .card-box {
            width: calc(50% - 10px);
            min-width: 140px;
        }

        .dashboard-overview-cards .card {
            padding: 15px;
        }

        .dashboard-overview-cards .card h5 {
            font-size: 0.875rem;
        }

        .dashboard-overview-cards .card p {
            font-size: 1.5rem;
        }

        .onboarding-controls .d-flex {
            flex-direction: column;
            gap: 10px;
        }

        .onboarding-controls .btn {
            width: 100%;
        }

        /* Chart adjustments for mobile */
        .chart-container {
            height: 250px;
            padding: 10px;
        }

        .chart-card .card-body {
            padding: 15px;
        }

        .chart-title {
            font-size: 14px;
        }

        /* Admin-specific mobile adjustments */
        .admin-links .list-group-item {
            padding: 12px 16px;
            margin-bottom: 8px;
        }

        .admin-links .list-group-item a {
            font-size: 0.875rem;
            gap: 0.5rem;
        }

        .admin-links .list-group-item small {
            font-size: 0.8rem;
            padding-left: 1.5rem;
        }

        .row.mt-4.px-3 {
            margin-top: 1rem !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        .col-md-6.mb-4 {
            margin-bottom: 1rem !important;
        }

        .card.shadow-sm.h-100.border-0 {
            margin-bottom: 1rem;
        }

        /* Analytics section grid adjustment */
        .row.mb-4.px-3 .col-md-6 {
            margin-bottom: 1rem;
        }
    }

    /* Tablet: 768px to 1023px */
    @media (min-width: 768px) and (max-width: 1023px) {
        .main-dashboard-content {
            margin-left: 80px;
            padding: 20px 25px;
        }

        .welcome-title {
            font-size: 24px;
        }

        .section-title {
            font-size: 20px;
        }

        .dashboard-overview-cards {
            flex-wrap: wrap;
            gap: 15px;
        }

        .card-box {
            width: calc(33.333% - 15px);
        }

        /* Admin-specific tablet adjustments */
        .admin-links .list-group-item {
            padding: 14px 18px;
        }

        .row.mt-4.px-3 {
            padding-left: 0.75rem !important;
            padding-right: 0.75rem !important;
        }

        /* Chart adjustments for tablet */
        .chart-container {
            height: 280px;
        }
    }

    /* Desktop: 1024px and above */
    @media (min-width: 1024px) {
        .main-dashboard-content {
            margin-left: 20%;
            padding: 20px 35px;
        }

        .welcome-title {
            font-size: 28px;
        }

        .section-title {
            font-size: 22px;
        }

        .dashboard-overview-cards {
            flex-wrap: nowrap;
            gap: 20px;
        }

        .card-box {
            width: 200px;
        }

        /* Admin-specific desktop adjustments */
        .admin-links .list-group-item {
            padding: 16px 20px;
        }

        .row.mt-4.px-3 {
            padding-left: 0.75rem !important;
            padding-right: 0.75rem !important;
        }

        /* Chart adjustments for desktop */
        .chart-container {
            height: 320px;
        }
    }
</style>

<!-- Favicon -->
<link rel="icon" type="image/x-icon"
    href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png">

<?php include('../../includes/semantics/head.php'); ?>

<div class="d-flex" id="wrapper" data-user-role="<?= $user['role'] ?>">
    <?php
    $sidenav_path = '../../templates/admin/sidenav_admin.php';
    require_once $sidenav_path;
    ?>

    <div class="main-dashboard-content-wrapper" id="page-content-wrapper">
        <div class="main-dashboard-content">
            <!-- Header Section -->
            <div class="d-flex flex-column px-3 pt-4 pb-2">
                <h2 class="welcome-title mb-0">Welcome, Admin <?= $display_name ?></h2>
            </div>

            <!-- SEARCH BAR WITH AJAX - Updated with proper styling -->
            <div class="search-bar position-relative mb-4">
                <div class="input-group search-bar-custom">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" id="searchInput" class="form-control"
                        placeholder="Search for links, reports, etc.">
                </div>
                <div id="searchResults" class="list-group position-absolute w-100 mt-1"
                    style="z-index:1000; display: none;"></div>
            </div>

            <!-- Welcome Card - Keep original structure but update styling -->
            <div class="card p-4 mb-4 border-0 shadow p-3 mb-5 rounded">
                <p class="text-dark mb-3">This is your central hub for managing your academic responsibilities.</p>
                <div class="onboarding-controls mt-4 p-3 border rounded">
                    <h5 class="text-dark fw-bold mb-3">Onboarding & Quick Guides</h5>
                    <p class="text-muted mb-3">Learn more about using ChronoNav, view helpful tips, or restart your
                        guided tour.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-custom-blue rounded-pill fw-medium" id="viewTourBtn">
                            <i class="fas fa-route me-1"></i> View Tour
                        </button>
                        <button class="btn btn-custom-primary rounded-pill fw-medium" id="viewTipsBtn">
                            <i class="fas fa-lightbulb me-1"></i> View Tips
                        </button>
                        <button class="btn btn-custom-outline rounded-pill fw-medium" id="restartOnboardingBtn">
                            <i class="fas fa-sync-alt me-1"></i> Restart Onboarding
                        </button>
                    </div>
                </div>
                <!-- AJAX container for onboarding tips -->
                <div id="onboardingContent" class="mt-3"></div>
            </div>

            <!-- Key Metrics Section -->
            <div class="px-3 pt-3 pb-1">
                <h3 class="section-title mb-3">Key Metrics / Status Cards</h3>
            </div>

            <div class="dashboard-overview-cards d-flex justify-content-center mb-5 px-3">
                <div class="card-box">
                    <a href="user_management.php" class="card-link text-decoration-none">
                        <div class="card text-start p-4 h-100 card-blue border-0">
                            <i class="fas fa-users fa-2x mb-2 fs-5"></i>
                            <h5 class="fs-">Total Users</h5>
                            <p class="metric-value"><?= $total_users ?></p>
                        </div>
                    </a>
                </div>
                <div class="card-box">
                    <a href="support_center.php" class="card-link text-decoration-none">
                        <div class="card text-start p-4 h-100 card-teal border-0">
                            <i class="fas fa-ticket-alt fa-2x mb-2 fs-5"></i>
                            <h5>Active Tickets</h5>
                            <p class="metric-value"><?= $active_tickets ?></p>
                        </div>
                    </a>
                </div>
                <div class="card-box">
                    <a href="announcements.php" class="card-link text-decoration-none">
                        <div class="card text-start p-4 h-100 card-orange border-0">
                            <i class="fas fa-bullhorn fa-2x mb-2 fs-5"></i>
                            <h5>Announcements</h5>
                            <p class="metric-value"><?= $new_announcements ?></p>
                        </div>
                    </a>
                </div>
                <div class="card-box">
                    <a href="feedback_list.php" class="card-link text-decoration-none">
                        <div class="card text-start p-4 h-100 card-purple border-0">
                            <i class="fas fa-comment-dots fa-2x mb-2 fs-5"></i>
                            <h5>Total Feedback</h5>
                            <p class="metric-value"><?= $total_feedbacks ?></p>
                        </div>
                    </a>
                </div>
                <div class="card-box">
                    <a href="room_manager.php" class="card-link text-decoration-none">
                        <div class="card text-start p-4 h-100 card-green border-0">
                            <i class="fas fa-door-open fa-2x mb-2 fs-5"></i>
                            <h5>Total Rooms</h5>
                            <p class="metric-value"><?= $total_rooms ?></p>
                        </div>
                    </a>
                </div>
            </div>


            <!-- Analytics Section -->
            <div class="px-3 pt-3 pb-1">
                <h3 class="section-title mb-3">Analytics</h3>
            </div>

            <div class="row mb-4 px-3">
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-chart-pie text-secondary"></i> Overall System
                                Metrics</h5>
                            <canvas id="dashboardPieChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-chart-bar text-secondary"></i> Activity Metrics</h5>
                            <canvas id="dashboardBarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4 px-3">
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-chart-pie text-secondary"></i> User Role
                                Distribution</h5>
                            <canvas id="userRolePieChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-university text-secondary"></i> User Distribution by
                                Department</h5>
                            <canvas id="departmentPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Admin Links Section -->
            <div class="px-3 pt-3 pb-1">
                <h3 class="section-title mb-3">Quick Admin Links</h3>
            </div>

            <div class="admin-links px-3">
                <ul class="list-group list-group-flush" id="adminLinksList">
                    <li class="list-group-item bg-info-subtle">
                        <a href="user_management.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                viewBox="0 0 256 256">
                                <path
                                    d="M216,40H40A16,16,0,0,0,24,56V200a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40ZM40,56H216V88H40ZM216,200H40V104H216v96Z">
                                </path>
                            </svg>
                            User Management Panel
                        </a>
                        <small class="text-muted d-block mt-1">
                            Add, edit, or remove user accounts and manage roles.
                        </small>
                    </li>
                    <li class="list-group-item bg-info-subtle">
                        <a href="class_room_assignments.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                viewBox="0 0 256 256">
                                <path
                                    d="M216,40H40A16,16,0,0,0,24,56V200a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40ZM40,56H216V88H40ZM216,200H40V104H216v96Z">
                                </path>
                            </svg>
                            Manage Class Offerings & Assignments
                        </a>
                        <small class="text-muted d-block mt-1">
                            Assign faculty to classes, and allocate rooms and schedules.
                        </small>
                    </li>
                    <li class="list-group-item bg-info-subtle">
                        <a href="office_hours_requests.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                viewBox="0 0 256 256">
                                <path
                                    d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm64-88a8,8,0,0,1-8,8H128a8,8,0,0,1-8-8V72a8,8,0,0,1,16,0v48h48A8,8,0,0,1,192,128Z">
                                </path>
                            </svg>
                            Manage Office Hours Requests
                        </a>
                        <small class="text-muted d-block mt-1">
                            Review and approve/reject faculty requests for office hours.
                        </small>
                    </li>
                    <li class="list-group-item bg-info-subtle">
                        <a href="room_manager.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                viewBox="0 0 256 256">
                                <path
                                    d="M216,40H40A16,16,0,0,0,24,56V200a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40ZM40,56H216V88H40ZM216,200H40V104H216v96Z">
                                </path>
                            </svg>
                            Building Room Manager
                        </a>
                        <small class="text-muted d-block mt-1">
                            Add, edit, or remove physical rooms and their details.
                        </small>
                    </li>
                    <li class="list-group-item bg-info-subtle">
                        <a href="announcements.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                viewBox="0 0 256 256">
                                <path
                                    d="M216,40H40A16,16,0,0,0,24,56V200a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40ZM40,56H216V88H40ZM216,200H40V104H216v96Z">
                                </path>
                            </svg>
                            Campus Announcement Board
                        </a>
                        <small class="text-muted d-block mt-1">
                            Create and manage campus-wide announcements.
                        </small>
                    </li>
                    <li class="list-group-item bg-info-subtle">
                        <a href="calendar.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                viewBox="0 0 256 256">
                                <path
                                    d="M208,32H184V24a8,8,0,0,0-16,0v8H88V24a8,8,0,0,0-16,0v8H48A16,16,0,0,0,32,48V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V48A16,16,0,0,0,208,32ZM72,48v8a8,8,0,0,0,16,0V48h80v8a8,8,0,0,0,16,0V48h24V80H48V48ZM208,208H48V96H208V208Z">
                                </path>
                            </svg>
                            Academic Calendar Viewer
                        </a>
                        <small class="text-muted d-block mt-1">
                            View important academic dates and events.
                        </small>
                    </li>
                    <li class="list-group-item bg-info-subtle">
                        <a href="audit_logs.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                viewBox="0 0 256 256">
                                <path
                                    d="M216,40H40A16,16,0,0,0,24,56V200a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40ZM40,56H216V88H40ZM216,200H40V104H216v96Z">
                                </path>
                            </svg>
                            System Logs and Activities
                        </a>
                        <small class="text-muted d-block mt-1">
                            Monitor system activities and user interactions.
                        </small>
                    </li>
                    <li class="list-group-item bg-info-subtle">
                        <a href="support_center.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                viewBox="0 0 256 256">
                                <path
                                    d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm-8-80V80a8,8,0,0,1,16,0v56a8,8,0,0,1-16,0Zm20,36a12,12,0,1,1-12-12A12,12,0,0,1,140,172Z">
                                </path>
                            </svg>
                            Help & Support Center
                        </a>
                        <small class="text-muted d-block mt-1">
                            Manage user support tickets and common queries.
                        </small>
                    </li>
                    <li class="list-group-item bg-info-subtle">
                        <a href="manage_faqs.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                viewBox="0 0 256 256">
                                <path
                                    d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm-8-80V80a8,8,0,0,1,16,0v56a8,8,0,0,1-16,0Zm20,36a12,12,0,1,1-12-12A12,12,0,0,1,140,172Z">
                                </path>
                            </svg>
                            Manage FAQs
                        </a>
                        <small class="text-muted d-block mt-1">
                            Add, edit, or remove frequently asked questions.
                        </small>
                    </li>
                    <li class="list-group-item bg-info-subtle">
                        <a href="feedback_list.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                viewBox="0 0 256 256">
                                <path
                                    d="M216,40H40A16,16,0,0,0,24,56V200a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40ZM40,56H216V88H40ZM216,200H40V104H216v96Z">
                                </path>
                            </svg>
                            Feedback List
                        </a>
                        <small class="text-muted d-block mt-1">
                            Able to see all feedback from all users.
                        </small>
                    </li>
                </ul>
            </div>

            <!-- Administrator Tools Section -->
            <div class="px-3 pt-4 pb-1">
                <h3 class="section-title mb-3">Administrator Tools</h3>
            </div>

            <div class="admin-links px-3">
                <ul class="list-group list-group-flush" id="adminToolsList">
                    <li class="list-group-item bg-info-subtle">
                        <a href="../admin/attendance_logs.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                viewBox="0 0 256 256">
                                <path
                                    d="M216,40H40A16,16,0,0,0,24,56V200a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40ZM40,56H216V88H40ZM216,200H40V104H216v96Z">
                                </path>
                            </svg>
                            View All Class Attendance Logs
                        </a>
                        <small class="text-muted d-block mt-1">
                            Access and review attendance records for all classes in the system.
                        </small>
                    </li>
                    <li class="list-group-item bg-info-subtle">
                        <a href="report_generator.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                viewBox="0 0 256 256">
                                <path
                                    d="M216,40H40A16,16,0,0,0,24,56V200a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40ZM40,56H216V88H40ZM216,200H40V104H216v96Z">
                                </path>
                            </svg>
                            Report Generator
                        </a>
                        <small class="text-muted d-block mt-1">
                            Generate detailed usage and attendance reports for the system.
                        </small>
                    </li>
                </ul>
            </div>

            <!-- Quick Links and Profile Cards -->
            <div class="row mt-4 px-3">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100 border-0">
                        <div class="card-body">
                            <h5 class="card-title text-dark fw-bold">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                    class="text-warning me-2" viewBox="0 0 256 256">
                                    <path
                                        d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm-8-80V80a8,8,0,0,1,16,0v56a8,8,0,0,1-16,0Zm20,36a12,12,0,1,1-12-12A12,12,0,0,1,140,172Z">
                                    </path>
                                </svg>
                                Quick Links
                            </h5>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <a href="#" class="text-decoration-none">Student Appointments (Future
                                        Feature)</a>
                                </li>
                                <li>
                                    <a href="calendar.php" class="text-decoration-none">Announcements (Future
                                        Feature)</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100 border-0">
                        <div class="card-body">
                            <h5 class="card-title text-dark fw-bold">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                    class="text-info me-2" viewBox="0 0 256 256">
                                    <path
                                        d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm-8-80V80a8,8,0,0,1,16,0v56a8,8,0,0,1-16,0Zm20,36a12,12,0,1,1-12-12A12,12,0,0,1,140,172Z">
                                    </path>
                                </svg>
                                Your Profile
                            </h5>
                            <p class="card-text text-muted">
                                Name: <strong class="text-dark"><?= $display_name ?></strong><br>
                                Email: <strong
                                    class="text-dark"><?= htmlspecialchars($user['email'] ?? 'N/A') ?></strong><br>
                                Role: <strong
                                    class="text-dark"><?= ucfirst(htmlspecialchars($user['role'] ?? 'N/A')) ?></strong>
                            </p>
                            <a href="../admin/view_profile.php" class="profile btn btn-custom-outline rounded-pill">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                    class="me-1 mb-1" viewBox="0 0 256 256">
                                    <path
                                        d="M230.92,212c-15.23-26.33-38.7-45.21-66.09-54.16a72,72,0,1,0-73.66,0C63.78,166.78,40.31,185.66,25.08,212a8,8,0,1,0,13.85,8c18.84-32.56,52.14-52,89.07-52s70.23,19.44,89.07,52a8,8,0,1,0,13.85-8ZM72,96a56,56,0,1,1,56,56A56.06,56.06,0,0,1,72,96Z">
                                    </path>
                                </svg>
                                View Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/common/onboarding_modal.php'; ?>

<script id="tour-data" type="application/json">
    <?= json_encode($onboarding_steps); ?>
</script>

<?php require_once '../../templates/footer.php'; ?>
<script src="../../assets/js/jquery.min.js"></script>
<script src="../../assets/js/script.js"></script>
<script src="../../assets/js/onboarding_tour.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // ================== AJAX SEARCH ==================
    $("#searchInput").on("keyup", function () {
        let query = $(this).val();
        if (query.length > 2) {
            $.ajax({
                url: "../../pages/admin/search.php",
                method: "GET",
                data: { q: query },
                success: function (response) {
                    let data = JSON.parse(response);
                    let output = "";
                    if (data.length > 0) {
                        data.forEach(item => {
                            output += `<a href="#" class="list-group-item list-group-item-action">${item.title}</a>`;
                        });
                    } else {
                        output = `<div class="list-group-item text-muted">No results found</div>`;
                    }
                    $("#searchResults").html(output).show();
                }
            });
        } else {
            $("#searchResults").hide();
        }
    });

    // ================== AJAX ONBOARDING ==================
    $("#viewTipsBtn").click(function () {
        $.get("../../pages/admin/get_tips.php", function (data) {
            $("#onboardingContent").html(data);
        });
    });
    $("#restartOnboardingBtn").click(function () {
        $.post("../../pages/admin/restart_onboarding.php", { user: "<?= $user['id'] ?? 0 ?>" }, function (data) {
            $("#onboardingContent").html("<div class='alert alert-success'>Onboarding restarted!</div>");
        });
    });

    // ================== CHART.JS IMPLEMENTATION ==================
    document.addEventListener('DOMContentLoaded', function () {
        // Data passed from PHP to JavaScript
        const totalUsers = <?= json_encode($total_users) ?>;
        const activeTickets = <?= json_encode($active_tickets) ?>;
        const newAnnouncements = <?= json_encode($new_announcements) ?>;

        // Data for user roles
        const adminCount = <?= json_encode($admin_count) ?>;
        const facultyCount = <?= json_encode($faculty_count) ?>;
        const studentCount = <?= json_encode($student_count) ?>;

        // Data for department counts
        const departmentLabels = Object.keys(<?= json_encode($department_counts) ?>);
        const departmentData = Object.values(<?= json_encode($department_counts) ?>);

        // Generate an array of distinct colors for departments
        const departmentColors = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
            '#FF9900', '#C9CBCF', '#8AC926', '#1982C4', '#6A4C93',
            '#A70000', '#00A7A7', '#A7A700', '#00A700', '#A700A7',
            '#B3B300', '#FF4D4D', '#4DA6FF', '#66FF66', '#FF66B2'
        ];

        // Only attempt to draw charts if primary dashboard data is numeric
        if (!isNaN(totalUsers) && !isNaN(activeTickets) && !isNaN(newAnnouncements)) {
            // Pie Chart for Overall System Metrics
            const pieCtx = document.getElementById('dashboardPieChart').getContext('2d');
            new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: ['Total Users', 'Active Tickets'],
                    datasets: [{
                        data: [totalUsers, activeTickets],
                        backgroundColor: ['#007bff', '#17a2b8'],
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Overall System Metrics'
                        }
                    }
                }
            });

            // Bar Chart for Activity Metrics
            const barCtx = document.getElementById('dashboardBarChart').getContext('2d');
            new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: ['Total Users', 'Active Tickets', 'New Announcements'],
                    datasets: [{
                        label: 'Counts',
                        data: [totalUsers, activeTickets, newAnnouncements],
                        backgroundColor: [
                            'rgba(0, 123, 255, 0.7)',
                            'rgba(23, 162, 184, 0.7)',
                            'rgba(40, 167, 69, 0.7)'
                        ],
                        borderColor: [
                            'rgba(0, 123, 255, 1)',
                            'rgba(23, 162, 184, 1)',
                            'rgba(40, 167, 69, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false,
                        },
                        title: {
                            display: true,
                            text: 'Overall System Metrics'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        } else {
            console.error("Primary dashboard data could not be fetched. Some charts may not be displayed.");
        }

        // New Pie Chart for User Role Distribution
        if (!isNaN(adminCount) && !isNaN(facultyCount) && !isNaN(studentCount) && (adminCount + facultyCount + studentCount > 0)) {
            const userRolePieCtx = document.getElementById('userRolePieChart').getContext('2d');
            new Chart(userRolePieCtx, {
                type: 'pie',
                data: {
                    labels: ['Admin', 'Faculty', 'Student'],
                    datasets: [{
                        data: [adminCount, facultyCount, studentCount],
                        backgroundColor: ['#0000FF', '#FF0000', '#00FF00'],
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'User Distribution by Role'
                        }
                    }
                }
            });
        } else {
            console.error("User role data could not be fetched or is zero. User role pie chart will not be displayed.");
        }

        // New Pie Chart for User Distribution by Department
        if (departmentLabels.length > 0 && departmentData.some(count => count > 0)) {
            const departmentPieCtx = document.getElementById('departmentPieChart').getContext('2d');
            new Chart(departmentPieCtx, {
                type: 'pie',
                data: {
                    labels: departmentLabels,
                    datasets: [{
                        data: departmentData,
                        backgroundColor: departmentColors.slice(0, departmentLabels.length),
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'User Distribution by Department'
                        }
                    }
                }
            });
        } else {
            console.error("Department data could not be fetched or is empty. Department distribution pie chart will not be displayed.");
        }
    });

    // --- Search Functionality ---
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');

        // Target the containers you want to search within
        const linkLists = [
            document.getElementById('adminLinksList'),
            document.getElementById('adminToolsList'),
            document.getElementById('quickLinksList')
        ];

        searchInput.addEventListener('keyup', function () {
            const searchTerm = this.value.toLowerCase();

            linkLists.forEach(list => {
                if (list) {
                    const listItems = list.getElementsByTagName('li');
                    Array.from(listItems).forEach(item => {
                        const itemText = item.textContent || item.innerText;
                        if (itemText.toLowerCase().includes(searchTerm)) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                }
            });
        });
    });
</script>

<?php include('../../includes/semantics/footer.php'); ?>






<!-- // =========================================================================================
// JavaScript for Chart Rendering
// =========================================================================================
?> -->

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Get PHP data and pass it to JavaScript
        const totalUsers = <?= json_encode($total_users) ?>;
        const activeTickets = <?= json_encode($active_tickets) ?>;
        const announcements = <?= json_encode($new_announcements) ?>;
        const totalFeedbacks = <?= json_encode($total_feedbacks) ?>;
        const totalRooms = <?= json_encode($total_rooms) ?>;

        // Data for User Role Distribution Pie Chart
        const adminCount = <?= json_encode($admin_count) ?>;
        const facultyCount = <?= json_encode($faculty_count) ?>;
        const studentCount = <?= json_encode($student_count) ?>;

        // Data for Department Distribution Pie Chart
        const departmentCounts = <?= json_encode($department_counts) ?>;

        // Set fixed height for all chart containers
        const chartHeight = '300px'; // Fixed height for all charts

        // ======================================================================
        // 2. Bar Chart: Activity Metrics
        // ======================================================================
        const barCtx = document.getElementById('dashboardBarChart');

        if (barCtx) {
            // Set container height
            barCtx.parentElement.style.height = chartHeight;
            barCtx.style.height = '100%';

            new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: ['Active Tickets', 'Announcements', 'Total Feedback', 'Total Rooms'],
                    datasets: [{
                        label: 'Counts',
                        data: [activeTickets, announcements, totalFeedbacks, totalRooms],
                        backgroundColor: [
                            'rgba(23, 162, 184, 0.6)',
                            'rgba(253, 126, 20, 0.6)',
                            'rgba(111, 66, 193, 0.6)',
                            'rgba(40, 167, 69, 0.6)'
                        ],
                        borderColor: [
                            '#17a2b8',
                            '#fd7e14',
                            '#6f42c1',
                            '#28a745'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true, // Changed to true for better proportions
                    aspectRatio: 2, // Set aspect ratio for better bar chart proportions
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // ======================================================================
        // 3. Pie Chart: User Role Distribution
        // ======================================================================
        const userRoleCtx = document.getElementById('userRolePieChart');

        if (userRoleCtx) {
            // Set container height
            userRoleCtx.parentElement.style.height = chartHeight;
            userRoleCtx.style.height = '100%';

            new Chart(userRoleCtx, {
                type: 'pie',
                data: {
                    labels: ['Admin', 'Faculty', 'Student (User)'],
                    datasets: [{
                        data: [adminCount, facultyCount, studentCount],
                        backgroundColor: [
                            '#007bff',
                            '#ffc107',
                            '#28a745'
                        ],
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2, // Square aspect ratio for pie charts
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        title: {
                            display: false,
                        }
                    }
                }
            });
        }

        // ======================================================================
        // 4. Pie Chart: User Distribution by Department
        // ======================================================================
        const departmentCtx = document.getElementById('departmentPieChart');
        const departmentLabels = Object.keys(departmentCounts);
        const departmentData = Object.values(departmentCounts);

        const departmentColors = departmentLabels.map((_, index) => {
            const colors = ['#dc3545', '#007bff', '#17a2b8', '#ffc107', '#28a745', '#6f42c1', '#fd7e14'];
            return colors[index % colors.length];
        });

        if (departmentCtx) {
            // Set container height
            departmentCtx.parentElement.style.height = chartHeight;
            departmentCtx.style.height = '100%';

            new Chart(departmentCtx, {
                type: 'pie',
                data: {
                    labels: departmentLabels,
                    datasets: [{
                        data: departmentData,
                        backgroundColor: departmentColors,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2, // Square aspect ratio for pie charts
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        title: {
                            display: false,
                        }
                    }
                }
            });
        }

    });
</script>