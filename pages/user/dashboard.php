<?php
// CHRONONAV_WEB_DOSS/pages/user/dashboard.php

require_once '../../middleware/auth_check.php';
require_once '../../includes/db_connect.php';
require_once '../../includes/onboarding_functions.php';

$user = $_SESSION['user'];
$page_title = "User Dashboard";
$current_page = "dashboard";
$display_name = htmlspecialchars($user['name'] ?? 'User');
$user_role = htmlspecialchars($user['role'] ?? 'user');

$onboarding_steps = [];
try {
    $pdo = get_db_connection();
    $onboarding_steps = getOnboardingSteps($pdo, $user_role);
} catch (PDOException $e) {
    error_log("Onboarding data fetch error: " . $e->getMessage());
}

$header_path = '../../templates/user/header_user.php';
if (isset($user['role'])) {
    if ($user['role'] === 'admin') {
        $header_path = '../../templates/admin/header_admin.php';
    } elseif ($user['role'] === 'faculty') {
        $header_path = '../../templates/faculty/header_faculty.php';
    }
}
require_once $header_path;
?>

<link rel="stylesheet" href="../../assets/css/user_css/dashboards.css">

<div class="d-flex" id="wrapper" data-user-role="<?= $user_role ?>">
    <?php
    $sidenav_path = '../../templates/user/sidenav_user.php';
    if (isset($user['role'])) {
        if ($user['role'] === 'admin') {
            $sidenav_path = '../../templates/admin/sidenav_admin.php';
        } elseif ($user['role'] === 'faculty') {
            $sidenav_path = '../../templates/faculty/sidenav_faculty.php';
        }
    }
    require_once $sidenav_path;
    ?>
    <div class="main-dashboard-content-wrapper" id="page-content-wrapper">
        <div class="main-dashboard-content">
            <h4>Welcome, <?= htmlspecialchars($user['name']) ?>!</h4>

            <!-- SEARCH BAR WITH AJAX -->
            <div class="search-bar position-relative">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search your schedule, courses, or reminders...">
                <div id="searchResults" class="list-group position-absolute w-100" style="z-index:1000;"></div>
            </div>
            
            <div class="card p-4 mb-4">
                <p>This is your personal space in ChronoNav. Keep an eye on your upcoming schedules and reminders.</p>
                <div class="onboarding-controls mt-4 p-3 border rounded">
                    <h5>Onboarding & Quick Guides</h5>
                    <p>Learn more about using ChronoNav, view helpful tips, or restart your guided tour.</p>
                    <button class="btn btn-primary me-2 mb-2" id="viewTourBtn"><i class="fas fa-route me-1"></i> View Step-by-Step Tour</button>
                    <button class="btn btn-info me-2 mb-2" id="viewTipsBtn"><i class="fas fa-lightbulb me-1"></i> View Tips</button>
                    <button class="btn btn-secondary mb-2" id="restartOnboardingBtn"><i class="fas fa-sync-alt me-1"></i> Restart Onboarding</button>
                </div>
                <!-- AJAX container for onboarding tips -->
                <div id="onboardingContent" class="mt-3"></div>
            </div>

            <div class="dashboard-widgets-grid">
                <div class="study-load-card card">
                    <img src="../../assets/img/chrononav_logo.jpg" alt="Study Illustration">
                    <div class="content">
                        <h5>Add Study Load</h5>
                        <p>Get started by adding your courses for the semester. Plan your academic journey with ease. Add your courses and stay organized.</p>
                        <button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#ocrModal">Add</button>
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

<!-- OCR MODAL -->
<div class="modal fade" id="ocrModal" tabindex="-1" aria-labelledby="ocrModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="ocrModalLabel">OCR Study Load Reader</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="ocr-alert" class="alert d-none" role="alert"></div>
        <div id="upload-step">
          <h6>Step 1: Upload your PDF file</h6>
          <div class="input-group mb-3">
            <input type="file" class="form-control" id="studyLoadPdf" accept="application/pdf">
            <label class="input-group-text" for="studyLoadPdf">Upload</label>
          </div>
          <button class="btn btn-primary" id="processOcrBtn">Process Document</button>
        </div>

        <div id="preview-step" style="display: none;">
          <h6>Step 2: Preview Extracted Schedule</h6>
          <div id="preview-content" class="p-3 border rounded mb-3" style="max-height: 400px; overflow-y: auto;">
            <p class="text-center text-muted">Awaiting file upload...</p>
          </div>
          <button class="btn btn-secondary me-2" id="backToUploadBtn">Back</button>
          <button class="btn btn-success" id="confirmScheduleBtn">Confirm Extracted Schedule</button>
        </div>

        <div id="confirmation-step" style="display: none;">
          <h6>Step 3: Confirmation</h6>
          <p>Your study load has been successfully saved!</p>
          <button class="btn btn-success" data-bs-dismiss="modal">Done</button>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once '../../templates/footer.php'; ?>
<script src="../../assets/js/jquery.min.js"></script>
<script src="../../assets/js/script.js"></script>
<script src="../../assets/js/onboarding_tour.js"></script>

<script>
// ================== AJAX SEARCH ==================
$("#searchInput").on("keyup", function() {
    let query = $(this).val();
    if (query.length > 2) {
        $.ajax({
            url: "../../pages/user/search.php",
            method: "GET",
            data: { q: query },
            success: function(response) {
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

// ================== AJAX OCR UPLOAD ==================
$("#processOcrBtn").click(function() {
    let file = $("#studyLoadPdf")[0].files[0];
    if (!file) {
        $("#ocr-alert").removeClass("d-none alert-success").addClass("alert-danger").text("Please upload a file first.");
        return;
    }

    let formData = new FormData();
    formData.append("studyLoadPdf", file);

    $.ajax({
        url: "../../pages/user/process_ocr.php",
        method: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json", // Add this line to tell jQuery to expect JSON
        success: function(response) {
            if (response.success) {
                // Hide upload step and show preview
                $("#upload-step").hide();
                $("#preview-step").show();
                $("#ocr-alert").removeClass("d-none alert-danger").addClass("alert-success").text("Document processed successfully! Please review the extracted schedule.");

                // Generate HTML for the schedule table
                let scheduleHtml = `<table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Sched No.</th>
                                                <th>Course No.</th>
                                                <th>Time</th>
                                                <th>Days</th>
                                                <th>Room</th>
                                                <th>Units</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;
                
                response.schedule.forEach(item => {
                    scheduleHtml += `<tr>
                                        <td>${item.sched_no}</td>
                                        <td>${item.course_no}</td>
                                        <td>${item.time}</td>
                                        <td>${item.days}</td>
                                        <td>${item.room}</td>
                                        <td>${item.units}</td>
                                    </tr>`;
                });
                
                scheduleHtml += `</tbody></table>`;
                $("#preview-content").html(scheduleHtml);
            } else {
                // If there's an error from the server, display it
                $("#ocr-alert").removeClass("d-none alert-success").addClass("alert-danger").text(response.error);
                $("#upload-step").show();
                $("#preview-step").hide();
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            // Handle AJAX errors (e.g., server not responding)
            $("#ocr-alert").removeClass("d-none alert-success").addClass("alert-danger").text("An unexpected error occurred during processing. Please try again.");
            console.error("AJAX Error: ", textStatus, errorThrown);
        }
    });
});

// ================== AJAX ONBOARDING ==================
$("#viewTipsBtn").click(function() {
    $.get("../../pages/user/get_tips.php", function(data) {
        $("#onboardingContent").html(data);
    });
});
$("#restartOnboardingBtn").click(function() {
    $.post("../../pages/user/restart_onboarding.php", { user: "<?= $user['id'] ?? 0 ?>" }, function(data) {
        $("#onboardingContent").html("<div class='alert alert-success'>Onboarding restarted!</div>");
    });
});
</script>



