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
require_once '../../templates/admin/sidenav_admin.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<link rel="stylesheet" href="../../assets/css/admin_css/dashboards.css">

<div class="wrapper" data-user-role="<?= htmlspecialchars($user_role) ?>">
    <div class="main-content-wrapper">
        <div class="main-dashboard-content container-fluid py-4">
            <h2>Welcome, Admin <?= $display_name ?></h2>
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search for links, reports, etc.">
            </div>

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

            <div class="dashboard-overview-cards row mb-4">
                <div class="col-md-4 mb-3">
                    <a href="user_management.php" class="card-link text-decoration-none">
                        <div class="card text-center p-3 h-100 shadow-sm card-blue">
                            <i class="fas fa-users fa-2x mb-2"></i>
                            <h5>Total Users</h5>
                            <p class="fs-4 fw-bold"><?= $total_users ?></p>
                        </div>
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="support_center.php" class="card-link text-decoration-none">
                        <div class="card text-center p-3 h-100 shadow-sm card-teal">
                            <i class="fas fa-ticket-alt fa-2x mb-2"></i>
                            <h5>Active Tickets</h5>
                            <p class="fs-4 fw-bold"><?= $active_tickets ?></p>
                        </div>
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="announcements.php" class="card-link text-decoration-none">
                        <div class="card text-center p-3 h-100 shadow-sm card-orange">
                            <i class="fas fa-bullhorn fa-2x mb-2"></i>
                            <h5>Announcements</h5>
                            <p class="fs-4 fw-bold"><?= $new_announcements ?></p>
                        </div>
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="feedback_list.php" class="card-link text-decoration-none">
                        <div class="card text-center p-3 h-100 shadow-sm card-purple">
                            <i class="fas fa-comment-dots fa-2x mb-2"></i>
                            <h5>Total Feedback</h5>
                            <p class="fs-4 fw-bold"><?= $total_feedbacks ?></p>
                        </div>
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="room_manager.php" class="card-link text-decoration-none">
                        <div class="card text-center p-3 h-100 shadow-sm card-green">
                            <i class="fas fa-door-open fa-2x mb-2"></i>
                            <h5>Total Rooms</h5>
                            <p class="fs-4 fw-bold"><?= $total_rooms ?></p>
                        </div>
                    </a>
                </div>
            </div>

            
            <div class="row mb-4">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-chart-pie text-secondary"></i> Overall System Metrics</h5>
                            <canvas id="dashboardPieChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-chart-bar text-secondary"></i> Activity Metrics</h5>
                            <canvas id="dashboardBarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-chart-pie text-secondary"></i> User Role Distribution</h5>
                            <canvas id="userRolePieChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-university text-secondary"></i> User Distribution by Department</h5>
                            <canvas id="departmentPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin-links card p-4 shadow-sm">
                <h4>Quick Admin Links</h4>
                <ul class="list-group list-group-flush" id="adminLinksList">
                    <li class="list-group-item">
                        <a href="user_management.php"> <i class="fas fa-users"></i> User Management Panel
                        </a>
                        <small class="text-muted d-block mt-1">Add, edit, or remove user accounts and manage roles.</small>
                    </li>
                    <li class="list-group-item">
                        <a href="class_room_assignments.php">
                            <i class="fas fa-chalkboard-teacher"></i> Manage Class Offerings & Assignments
                        </a>
                        <small class="text-muted d-block mt-1">Assign faculty to classes, and allocate rooms and schedules.</small>
                    </li>
                    <li class="list-group-item">
                        <a href="office_hours_requests.php">
                            <i class="fas fa-user-clock"></i> Manage Office Hours Requests
                        </a>
                        <small class="text-muted d-block mt-1">Review and approve/reject faculty requests for office hours.</small>
                    </li>
                    <li class="list-group-item">
                        <a href="room_manager.php">
                            <i class="fas fa-building"></i> Building Room Manager
                        </a>
                        <small class="text-muted d-block mt-1">Add, edit, or remove physical rooms and their details.</small>
                    </li>
                    <li class="list-group-item">
                        <a href="announcements.php">
                            <i class="fas fa-bullhorn"></i> Campus Announcement Board
                        </a>
                        <small class="text-muted d-block mt-1">Create and manage campus-wide announcements.</small>
                    </li>
                    <li class="list-group-item">
                        <a href="calendar.php">
                            <i class="fas fa-calendar-alt"></i> Academic Calendar Viewer
                        </a>
                        <small class="text-muted d-block mt-1">View important academic dates and events.</small>
                    </li>
                    <li class="list-group-item">
                        <a href="audit_logs.php"> <i class="fas fa-list-alt"></i> System Logs and Activities
                        </a>
                        <small class="text-muted d-block mt-1">Monitor system activities and user interactions.</small>
                    </li>
                    <li class="list-group-item">
                        <a href="support_center.php">
                            <i class="fas fa-question-circle"></i> Help & Support Center
                        </a>
                        <small class="text-muted d-block mt-1">Manage user support tickets and common queries.</small>
                    </li>
                    <li class="list-group-item">
                        <a href="manage_faqs.php">
                            <i class="fas fa-question"></i> Manage FAQs
                        </a>
                        <small class="text-muted d-block mt-1">Add, edit, or remove frequently asked questions.</small>
                    </li>
                    <li class="list-group-item">
                        <a href="feedback_list.php">
                            <i class="fas fa-list"></i> Feedback List
                        </a>
                        <small class="text-muted d-block mt-1">Able to see all feedback from all users.</small>
                    </li>
                </ul>
            </div>

            <div class="admin-links card p-4 shadow-sm mt-5"> <h4>Administrator Tools</h4>
                <ul class="list-group list-group-flush" id="adminToolsList">
                    <li class="list-group-item">
                        <a href="../admin/attendance_logs.php">
                            <i class="fas fa-clipboard-list"></i> View All Class Attendance Logs
                        </a>
                        <small class="text-muted d-block mt-1">
                            Access and review attendance records for all classes in the system.
                        </small>
                    </li>
                    <li class="list-group-item">
                        <a href="report_generator.php">
                            <i class="fas fa-chart-bar"></i> Report Generator
                        </a>
                        <small class="text-muted d-block mt-1">
                            Generate detailed usage and attendance reports for the system.
                        </small>
                    </li>
                </ul>
            </div>

            <div class="row mt-5">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-exclamation-circle text-warning"></i> Quick Links</h5>
                            <ul class="list-unstyled" id="quickLinksList">
                                <li><a href="#">Student Appointments (Future Feature)</a></li>
                                <li><a href="#">Announcements (Future Feature)</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-info-circle text-info"></i> Your Profile</h5>
                            <p class="card-text">
                                Name: <strong><?= $display_name ?></strong><br>
                                Email: <strong><?= htmlspecialchars($user['email'] ?? 'N/A') ?></strong><br>
                                Role: <strong><?= ucfirst(htmlspecialchars($user['role'] ?? 'N/A')) ?></strong>
                            </p>
                            <a href="../admin/view_profile.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-user-circle"></i> View Profile</a> </div>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../../assets/js/script.js"></script>
<script src="../../assets/js/onboarding_tour.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
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

        // Generate an array of distinct colors for departments (you can add more as needed)
        const departmentColors = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
            '#FF9900', '#C9CBCF', '#8AC926', '#1982C4', '#6A4C93',
            '#A70000', '#00A7A7', '#A7A700', '#00A700', '#A700A7',
            '#B3B300', '#FF4D4D', '#4DA6FF', '#66FF66', '#FF66B2' // More colors for more departments
        ];

        // Only attempt to draw charts if primary dashboard data is numeric
        if (!isNaN(totalUsers) && !isNaN(activeTickets) && !isNaN(newAnnouncements)) {
            // Pie Chart for Overall System Metrics (Existing)
            const pieCtx = document.getElementById('dashboardPieChart').getContext('2d');
            new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: ['Total Users', 'Active Tickets'],
                    datasets: [{
                        data: [totalUsers, activeTickets],
                        backgroundColor: ['#007bff', '#17a2b8'], // Primary and Info colors
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

            // Bar Chart for Activity Metrics (Existing)
            const barCtx = document.getElementById('dashboardBarChart').getContext('2d');
            new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: ['Total Users', 'Active Tickets', 'New Announcements'],
                    datasets: [{
                        label: 'Counts',
                        data: [totalUsers, activeTickets, newAnnouncements],
                        backgroundColor: [
                            'rgba(0, 123, 255, 0.7)', // Primary
                            'rgba(23, 162, 184, 0.7)', // Info
                            'rgba(40, 167, 69, 0.7)'  // Success
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
                                precision: 0 // Ensure integer ticks on y-axis
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
                    labels: ['Admin', 'Faculty', 'Student'], // Labels for roles
                    datasets: [{
                        data: [adminCount, facultyCount, studentCount],
                        backgroundColor: ['#0000FF', '#FF0000', '#00FF00'], // Blue, Red, Green
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
                    labels: departmentLabels, // Department names
                    datasets: [{
                        data: departmentData, // Counts per department
                        backgroundColor: departmentColors.slice(0, departmentLabels.length), // Apply colors
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

    // --- Search Functionality (New Code) ---
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');

        // Target the containers you want to search within
        const linkLists = [
            document.getElementById('adminLinksList'),
            document.getElementById('adminToolsList'),
            document.getElementById('quickLinksList')
        ];

        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();

            linkLists.forEach(list => {
                if (list) { // Check if the element exists
                    const listItems = list.getElementsByTagName('li');
                    Array.from(listItems).forEach(item => {
                        const itemText = item.textContent || item.innerText;
                        if (itemText.toLowerCase().includes(searchTerm)) {
                            item.style.display = 'block'; // Or 'list-item' depending on your CSS
                        } else {
                            item.style.display = 'none';
                        }
                    });
                }
            });
        });
    });
</script>

<?php require_once '../../templates/footer.php'; ?>