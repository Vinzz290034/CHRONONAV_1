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
$user_id = $user['id'] ?? 0; // Get user ID for fetching schedule

$onboarding_steps = [];
try {
    $pdo = get_db_connection();
    $onboarding_steps = getOnboardingSteps($pdo, $user_role);
} catch (PDOException $e) {
    error_log("Onboarding data fetch error: " . $e->getMessage());
}

// --- NEW PHP FUNCTION: Fetch User's Schedule ---
function getUserSchedule($user_id, $pdo) {
    try {
        // Assuming your schedule table is named 'user_schedule'
        $stmt = $pdo->prepare("SELECT course_no, time, days, room, instructor FROM user_schedule WHERE user_id = ? ORDER BY FIELD(days, 'M', 'T', 'W', 'Th', 'F', 'S'), time");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Failed to fetch user schedule: " . $e->getMessage());
        return [];
    }
}
$user_schedule = getUserSchedule($user_id, $pdo);
// --- END NEW PHP FUNCTION ---

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
                <div id="onboardingContent" class="mt-3"></div>
            </div>

            <div class="dashboard-widgets-grid">
                
                <div class="schedule-widget card p-3 mb-4">
                    <h5><i class="fas fa-calendar-alt me-2"></i> My Current Study Load</h5>
                    <div id="scheduleDisplayContent">
                        <?php if (empty($user_schedule)): ?>
                            <div class="text-center p-5">
                                <i class="fas fa-clock fa-3x text-secondary mb-3"></i>
                                <p class="text-muted">No schedule found. Get started by adding your study load!</p>
                                <button class="btn btn-add-lg" data-bs-toggle="modal" data-bs-target="#ocrModal"><i class="fas fa-file-upload me-2"></i> Add Study Load</button>
                            </div>
                        <?php else: ?>
                            <table class="table table-sm table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th>Time</th>
                                        <th>Days</th>
                                        <th>Room</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($user_schedule as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['course_no']) ?></td>
                                            <td><?= htmlspecialchars($item['time']) ?></td>
                                            <td><?= htmlspecialchars($item['days']) ?></td>
                                            <td><?= htmlspecialchars($item['room']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <div class="text-end">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#ocrModal"><i class="fas fa-edit me-1"></i> Update Schedule</button>
                            </div>
                        <?php endif; ?>
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
                    <div id="download-link-container" class="alert alert-info d-none mt-3">
                        <p class="mb-0">You can download the **extracted schedule** (which reflects the data below) as a PDF:</p>
                        <a href="#" id="downloadExtractedPdf" target="_blank" class="btn btn-sm btn-info mt-2"><i class="fas fa-download me-1"></i> Download Extracted PDF</a>
                    </div>
                    <button class="btn btn-primary mt-2" id="processOcrBtn">Process Document</button>
                </div>

                <div id="preview-step" style="display: none;">
                    <h6>Step 2: Preview Extracted Schedule</h6>
                    <p class="text-muted">Please review the extracted data for accuracy before confirming.</p>
                    <div id="preview-content" class="p-3 border rounded mb-3" style="max-height: 400px; overflow-y: auto;">
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
// Global variable to hold the extracted schedule data after successful OCR
let extractedScheduleData = [];

// Function to reset the modal state
function resetOcrModal() {
    $("#studyLoadPdf").val(''); // Clear file input
    $("#upload-step").show();
    $("#preview-step").hide();
    $("#confirmation-step").hide();
    $("#ocr-alert").addClass("d-none").removeClass("alert-success alert-danger alert-info").text('');
    $("#preview-content").html('<p class="text-center text-muted">Awaiting file upload...</p>');
    $("#download-link-container").addClass("d-none"); // Hide download link on reset
    extractedScheduleData = [];
}

// Reset modal when it's hidden (e.g., user closes it)
$('#ocrModal').on('hidden.bs.modal', function () {
    resetOcrModal();
});


// ================== AJAX SEARCH (UNCHANGED) ==================
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

// ================== OCR STEP 1: UPLOAD & PROCESS ==================
$("#processOcrBtn").click(function() {
    let file = $("#studyLoadPdf")[0].files[0];
    if (!file) {
        $("#ocr-alert").removeClass("d-none alert-success alert-info").addClass("alert-danger").text("Please upload a file first.");
        return;
    }
    
    // Hide previous download link
    $("#download-link-container").addClass("d-none");
    
    // Show loading state
    $("#ocr-alert").removeClass("d-none alert-danger alert-success").addClass("alert-info").text("Processing document using Tesseract, please wait... This may take a moment.");
    $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

    let formData = new FormData();
    formData.append("studyLoadPdf", file);

    $.ajax({
        url: "../../pages/user/process_ocr.php",
        method: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
        success: function(response) {
            $("#processOcrBtn").prop('disabled', false).text('Process Document'); // Reset button
            if (response.success) {
                // Store the extracted data globally for the next step
                extractedScheduleData = response.schedule; 
                
                // Hide upload step and show preview
                $("#upload-step").hide();
                $("#preview-step").show();
                $("#ocr-alert").removeClass("d-none alert-danger alert-info").addClass("alert-success").text("Document processed successfully! Please review the extracted schedule.");

                // Check for and set the download link
                if (response.extracted_pdf_url) {
                    $("#downloadExtractedPdf").attr("href", response.extracted_pdf_url);
                    $("#download-link-container").removeClass("d-none");
                }

                // Generate HTML for the schedule table
                let scheduleHtml = `<table class="table table-sm table-striped table-hover">
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
                
                extractedScheduleData.forEach(item => {
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
                $("#ocr-alert").removeClass("d-none alert-success alert-info").addClass("alert-danger").text(response.error);
                $("#upload-step").show();
                $("#preview-step").hide();
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            $("#processOcrBtn").prop('disabled', false).text('Process Document'); // Reset button
            // Handle AJAX errors (e.g., server not responding)
            $("#ocr-alert").removeClass("d-none alert-success alert-info").addClass("alert-danger").text("An unexpected error occurred during processing. Please try again.");
            console.error("AJAX Error: ", textStatus, errorThrown);
        }
    });
});

// ================== OCR STEP 2: BACK BUTTON ==================
$("#backToUploadBtn").click(function() {
    $("#upload-step").show();
    $("#preview-step").hide();
    $("#ocr-alert").addClass("d-none"); // Clear alert
    $("#download-link-container").addClass("d-none"); // Hide download link
});

// ================== OCR STEP 3: CONFIRM & SAVE SCHEDULE (UNCHANGED) ==================
$("#confirmScheduleBtn").click(function() {
    if (extractedScheduleData.length === 0) {
        $("#ocr-alert").removeClass("d-none alert-success").addClass("alert-danger").text("No schedule data to save. Please re-upload your document.");
        return;
    }
    
    // Show loading state
    $("#ocr-alert").removeClass("d-none alert-danger alert-success").addClass("alert-info").text("Saving schedule...");
    $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');

    $.ajax({
        url: "../../pages/user/save_schedule.php",
        method: "POST",
        // Send the extracted schedule data as a JSON string
        data: { schedule: JSON.stringify(extractedScheduleData) }, 
        dataType: "json",
        success: function(response) {
            $("#confirmScheduleBtn").prop('disabled', false).text('Confirm Extracted Schedule'); // Reset button
            if (response.success) {
                $("#preview-step").hide();
                $("#confirmation-step").show();
                $("#ocr-alert").removeClass("d-none alert-danger alert-info").addClass("alert-success").text("Success! Your schedule has been saved.");
                
                // *** CRITICAL: Refresh the dashboard schedule content ***
                setTimeout(() => {
                    // Simple full page refresh to reload the PHP-rendered schedule
                    window.location.reload(); 
                }, 1000); 

            } else {
                $("#ocr-alert").removeClass("d-none alert-success alert-info").addClass("alert-danger").text(response.error || "An error occurred while saving the schedule.");
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            $("#confirmScheduleBtn").prop('disabled', false).text('Confirm Extracted Schedule'); // Reset button
            $("#ocr-alert").removeClass("d-none alert-success alert-info").addClass("alert-danger").text("An unexpected network error occurred while saving.");
            console.error("Save Schedule AJAX Error: ", textStatus, errorThrown);
        }
    });
});

// ================== AJAX ONBOARDING (UNCHANGED) ==================
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