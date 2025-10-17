<?php
// CHRONONAV_WEB_UNO/pages/faculty/dashboard.php

// Required files for authentication, database connection, and onboarding functions
require_once '../../middleware/auth_check.php';
require_once '../../includes/functions.php';
require_once '../../includes/db_connect.php';
require_once '../../includes/onboarding_functions.php';

// Ensure the user is logged in and has the 'faculty' or 'admin' role
requireRole(['faculty', 'admin']);

// Get user data from session *after* auth_check and role check
$user = $_SESSION['user'];

// Set page-specific variables for the header and sidenav
$page_title = "Faculty Dashboard";
$current_page = "dashboard";

// Variables for the header template
$display_username = htmlspecialchars($user['name'] ?? 'Faculty');
$display_user_role = htmlspecialchars($user['role'] ?? 'Faculty');

// Attempt to get profile image path for the header
$profile_img_src = '../../uploads/profiles/default-avatar.png';
if (!empty($user['profile_img']) && file_exists('../../' . $user['profile_img'])) {
    $profile_img_src = '../../' . $user['profile_img'];
}

// Fetch onboarding steps for the current user role
$onboarding_steps = [];
try {
    $pdo = get_db_connection();
    $onboarding_steps = getOnboardingSteps($pdo, $user['role']);
} catch (PDOException $e) {
    error_log("Onboarding data fetch error: " . $e->getMessage());
}

$header_path = '../../templates/faculty/header_faculty.php';
require_once $header_path;
?>

<link rel="stylesheet" href="../../assets/css/faculty_css/faculty_style.css">
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

    .study-load-card-custom {
        border-radius: 0.75rem;
        overflow: hidden;
        border: none;
        background: white;
    }

    .study-load-image-custom {
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        border-radius: 0.75rem;
        min-height: 200px;
        height: 100%;
    }

    .class-item-custom {
        background-color: #f9fafb;
        padding: 12px 16px;
        border-radius: 0.5rem;
        margin-bottom: 8px;
    }

    .class-image-custom {
        width: 56px;
        height: 74.67px;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        border-radius: 0.5rem;
        flex-shrink: 0;
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

    /* Faculty specific styles */
    .faculty-links .list-group-item {
        border: none;
        padding: 16px 20px;
        background-color: #f9fafb;
        margin-bottom: 8px;
        border-radius: 0.5rem;
    }

    .faculty-links .list-group-item a {
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

    .faculty-links .list-group-item a:hover {
        color: #2e78c6;
    }

    .faculty-links .list-group-item small {
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
</style>

<!-- Favicon -->
<link rel="icon" type="image/x-icon"
    href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png">

<?php include('../../includes/semantics/head.php'); ?>

<div class="d-flex" id="wrapper" data-user-role="<?= $user['role'] ?>">
    <?php
    $sidenav_path = '../../templates/faculty/sidenav_faculty.php';
    require_once $sidenav_path;
    ?>

    <div class="main-dashboard-content-wrapper" id="page-content-wrapper">
        <div class="main-dashboard-content">
            <!-- Header Section -->
            <div class="d-flex flex-column px-3 pt-4 pb-2">
                <h2 class="welcome-title mb-0">Welcome, <?= htmlspecialchars($user['name']) ?></h2>
            </div>

            <!-- SEARCH BAR WITH AJAX - Updated with proper styling -->
            <div class="search-bar position-relative mb-4">
                <div class="input-group search-bar-custom">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" id="searchInput" class="form-control"
                        placeholder="Search your schedule, classes, or faculty resources...">
                </div>
                <div id="searchResults" class="list-group position-absolute w-100 mt-1"
                    style="z-index:1000; display: none;"></div>
            </div>

            <!-- Welcome Card - Keep original structure but update styling -->
            <div class="card p-4 mb-4 border-0 shadow p-3 mb-5 rounded">
                <p class="text-dark mb-3">This is your central hub for managing your academic responsibilities. Monitor
                    your classes, office hours, and faculty calendar.</p>
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

            <!-- Faculty Links Section -->
            <div class="px-3 pt-3 pb-1">
                <h3 class="section-title mb-0">Faculty Schedule Manager</h3>
            </div>

            <div class="faculty-links px-3">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item bg-info-subtle">
                        <a href="my_classes.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                viewBox="0 0 256 256">
                                <path
                                    d="M216,40H40A16,16,0,0,0,24,56V200a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40ZM40,56H216V88H40ZM216,200H40V104H216v96Z">
                                </path>
                            </svg>
                            View Class Schedule list & Assigned Rooms
                        </a>
                        <small class="text-muted d-block mt-1">
                            View all your assigned classes, including rooms, days, and times.
                        </small>
                    </li>
                    <li class="list-group-item bg-info-subtle">
                        <a href="set_office_consultation.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                viewBox="0 0 256 256">
                                <path
                                    d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm64-88a8,8,0,0,1-8,8H128a8,8,0,0,1-8-8V72a8,8,0,0,1,16,0v48h48A8,8,0,0,1,192,128Z">
                                </path>
                            </svg>
                            Set My Office & Consultation Hours
                        </a>
                        <small class="text-muted d-block mt-1">
                            Request office hours for admin approval and manage your general consultation slots.
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
                            View Faculty Calendar
                        </a>
                        <small class="text-muted d-block mt-1">
                            See your personal schedule, class timings, and important events.
                        </small>
                    </li>
                </ul>
            </div>

            <?php if ($user['role'] === 'admin'): ?>
                <!-- Admin Links Section -->
                <div class="px-3 pt-4 pb-1">
                    <h3 class="section-title mb-0">Administrator Tools</h3>
                </div>

                <div class="faculty-links px-3">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
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
                    </ul>
                </div>
            <?php endif; ?>

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
                                Name: <strong class="text-dark"><?= $display_username ?></strong><br>
                                Email: <strong
                                    class="text-dark"><?= htmlspecialchars($user['email'] ?? 'N/A') ?></strong><br>
                                Role: <strong
                                    class="text-dark"><?= ucfirst(htmlspecialchars($user['role'] ?? 'N/A')) ?></strong>
                            </p>
                            <a href="view_profile.php" class="profile btn btn-custom-outline rounded-pill">
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

<script>
    // ================== AJAX SEARCH ==================
    $("#searchInput").on("keyup", function () {
        let query = $(this).val();
        if (query.length > 2) {
            $.ajax({
                url: "../../pages/faculty/search.php",
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
        $.get("../../pages/faculty/get_tips.php", function (data) {
            $("#onboardingContent").html(data);
        });
    });
    $("#restartOnboardingBtn").click(function () {
        $.post("../../pages/faculty/restart_onboarding.php", { user: "<?= $user['id'] ?? 0 ?>" }, function (data) {
            $("#onboardingContent").html("<div class='alert alert-success'>Onboarding restarted!</div>");
        });
    });
</script>

<?php include('../../includes/semantics/footer.php'); ?>