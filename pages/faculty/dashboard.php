<?php
// CHRONONAV_WEB_UNO/pages/faculty/dashboard.php

// Required files for authentication, database connection, and onboarding functions
require_once '../../middleware/auth_check.php';
require_once '../../includes/functions.php';
require_once '../../includes/db_connect.php'; // NEW: Include database connection
require_once '../../includes/onboarding_functions.php'; // NEW: Include onboarding functions

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

// NEW: Fetch onboarding steps for the current user role
$onboarding_steps = [];
try {
    $pdo = get_db_connection();
    $onboarding_steps = getOnboardingSteps($pdo, $user['role']); // Use the user's role
} catch (PDOException $e) {
    error_log("Onboarding data fetch error: " . $e->getMessage());
}

// Include the Faculty-specific Header
require_once '../../templates/faculty/header_faculty.php';
?>

<link rel="stylesheet" href="../../assets/css/faculty_css/faculty_style.css">
<link rel="stylesheet" href="../../assets/css/onboarding.css"> 

<?php
// Include the Faculty-specific Sidenav
require_once '../../templates/faculty/sidenav_faculty.php';
?>

<div class="main-content-wrapper">
    <div class="main-dashboard-content container-fluid py-4">
        <h2>Welcome, <?= ucfirst($display_user_role) ?> <?= $display_username ?>!</h2>
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" id="dashboardSearchInput" placeholder="Search dashboard...">
        </div>

        <div class="searchable-content">
            <div class="card p-4 my-4">
                <p>This is your central hub for managing your academic responsibilities.</p>
                <div class="onboarding-controls mt-4 p-3 border rounded">
                    <h5>Onboarding & Quick Guides</h5>
                    <p>Learn more about using ChronoNav, view helpful tips, or restart your guided tour.</p>
                    <button class="btn btn-primary me-2 mb-2" id="viewTourBtn"><i class="fas fa-route me-1"></i> View Step-by-Step Tour</button>
                    <button class="btn btn-info me-2 mb-2" id="viewTipsBtn"><i class="fas fa-lightbulb me-1"></i> View Tips</button>
                    <button class="btn btn-secondary mb-2" id="restartOnboardingBtn"><i class="fas fa-sync-alt me-1"></i> Restart Onboarding</button>
                </div>
            </div>
            <div class="faculty-links mt-5">
                <h4>Faculty Schedule Manager</h4>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="my_classes.php">
                            <i class="fas fa-chalkboard"></i> View Class Schedule list & Assigned Rooms
                        </a>
                        <small class="text-muted d-block mt-1">
                            View all your assigned classes, including rooms, days, and times.
                        </small>
                    </li>
                    <li class="list-group-item">
                        <a href="set_office_consultation.php">
                            <i class="fas fa-clock"></i> Set My Office & Consultation Hours
                        </a>
                        <small class="text-muted d-block mt-1">
                            Request office hours for admin approval and manage your general consultation slots.
                        </small>
                    </li>
                    <li class="list-group-item">
                        <a href="calendar.php">
                            <i class="fas fa-calendar-alt"></i> View Faculty Calendar
                        </a>
                        <small class="text-muted d-block mt-1">
                            See your personal schedule, class timings, and important events.
                        </small>
                    </li>
                </ul>
            </div>

            <?php if ($user['role'] === 'admin'): ?>
            <div class="admin-links mt-5">
                <h4>Administrator Tools</h4>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="../admin/attendance_logs.php">
                            <i class="fas fa-clipboard-list"></i> View All Class Attendance Logs
                        </a>
                        <small class="text-muted d-block mt-1">
                            Access and review attendance records for all classes in the system.
                        </small>
                    </li>
                </ul>
            </div>
            <?php endif; ?>

            <div class="row mt-5">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-exclamation-circle text-warning"></i> Quick Links</h5>
                            <ul class="list-unstyled">
                                <li><a href="#">Student Appointments (Future Feature)</a></li>
                                <li><a href="calendar.php">Announcements (Future Feature)</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-info-circle text-info"></i> Your Profile</h5>
                            <p class="card-text">
                                Name: <strong><?= $display_username ?></strong><br>
                                Email: <strong><?= htmlspecialchars($user['email'] ?? 'N/A') ?></strong><br>
                                Role: <strong><?= ucfirst(htmlspecialchars($user['role'] ?? 'N/A')) ?></strong>
                            </p>
                            <a href="view_profile.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-user-circle"></i> View Profile</a>
                        </div>
                    </div>
                </div>
            </div>
        </div> </div>
    
    <?php require_once '../../templates/common/onboarding_modal.php'; ?>

    <script id="tour-data" type="application/json">
        <?= json_encode($onboarding_steps); ?>
    </script>
    
    <?php
    // Include the Footer
    require_once '../../templates/footer.php';
    ?>
</div>

<script src="../../assets/js/jquery.min.js"></script>
<script src="../../assets/js/script.js"></script>
<script src="../../assets/js/onboarding_tour.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('dashboardSearchInput');
        const searchableSections = document.querySelectorAll('.card, .faculty-links, .admin-links');
        
        // This is a more robust way to handle the search by targeting the sections
        // and including all their text content in the search.
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();

            searchableSections.forEach(section => {
                const sectionText = section.textContent.toLowerCase();
                
                if (sectionText.includes(searchTerm)) {
                    section.style.display = ''; // Show the section
                } else {
                    section.style.display = 'none'; // Hide the section
                }
            });
        });
    });
</script>