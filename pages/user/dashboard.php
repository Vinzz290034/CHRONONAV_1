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

<style>
    /* Custom styles to match the exact design */
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
</style>

<!-- Favicon -->
<link rel="icon" type="image/x-icon"
    href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png">

<?php include('../../includes/semantics/head.php'); ?>

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
            <!-- Header Section -->
            <div class="d-flex flex-column px-3 pt-4 pb-2">
                <h2 class="welcome-title mb-0">Welcome, <?= htmlspecialchars($user['name']) ?></h2>
            </div>

            <!-- SEARCH BAR WITH AJAX - Updated with proper styling -->
            <div class="search-bar position-relative mb-4 px-3">
                <div class="input-group search-bar-custom">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" id="searchInput" class="form-control"
                        placeholder="Search your schedule, courses, or reminders...">
                </div>
                <div id="searchResults" class="list-group position-absolute w-100 mt-1"
                    style="z-index:1000; display: none;"></div>
            </div>

            <!-- Welcome Card - Keep original structure but update styling -->
            <div class="card p-4 mb-4 mx-3 border-0 shadow-sm">
                <p class="mb-3">This is your personal space in ChronoNav. Keep an eye on your upcoming schedules and
                    reminders.</p>
                <div class="onboarding-controls mt-4 p-3 border rounded">
                    <h5 class="fw-bold">Onboarding & Quick Guides</h5>
                    <p class="text-muted mb-3">Learn more about using ChronoNav, view helpful tips, or restart your
                        guided tour.</p>
                    <button class="btn btn-primary me-2 mb-2" id="viewTourBtn">
                        <i class="fas fa-route me-1"></i> View Step-by-Step Tour
                    </button>
                    <button class="btn btn-info me-2 mb-2" id="viewTipsBtn">
                        <i class="fas fa-lightbulb me-1"></i> View Tips
                    </button>
                    <button class="btn btn-secondary mb-2" id="restartOnboardingBtn">
                        <i class="fas fa-sync-alt me-1"></i> Restart Onboarding
                    </button>
                </div>
                <!-- AJAX container for onboarding tips -->
                <div id="onboardingContent" class="mt-3"></div>
            </div>

            <!-- Study Load Card - Updated with proper Bootstrap grid -->
            <div class="dashboard-widgets-grid mb-4 px-3">
                <div class="card study-load-card-custom p-0">
                    <div class="row g-0">
                        <div class="col-12 col-xl-8">
                            <div class="study-load-image-custom"
                                style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuD954QIt-gjVs85lBbDP2JqyWtLS1JkL27Y6Or63Qm3SYXXEDGjS_1Zgz278DlR8dGPMDnq8uXRg0NvWFNn3z_sMSFPbzFtTechOl28InzjkBN3fVGWU6VTWRVXNPZ075PPeYJEwmqq6Ye_2n0zcXbwnnIIuRWWKPhUIkz9xa5GmLFWyH_h59WBby1QwlzB_LSr5EVKikvNrmrRTfzrOlefxoZ9Z6fADSNh2E7pi7YyQKNhdB6PlANQDzni9dCVi1p3Ucbkn9SEvw0");'>
                            </div>
                        </div>
                        <div class="col-12 col-xl-4">
                            <div class="card-body d-flex flex-column justify-content-center h-100 p-4">
                                <h5 class="card-title fw-bold mb-2">Add Study Load</h5>
                                <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-end">
                                    <div class="mb-3 mb-xl-0 flex-grow-1">
                                        <p class="text-muted mb-1">Get started by adding your courses for the semester.
                                        </p>
                                        <p class="text-muted mb-0">Plan your academic journey with ease. Add your
                                            courses and stay organized.</p>
                                    </div>
                                    <button class="btn btn-custom-primary ms-xl-3" data-bs-toggle="modal"
                                        data-bs-target="#ocrModal">
                                        <span>Add</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Classes Section -->
            <div class="px-3 pt-3 pb-1">
                <h3 class="section-title mb-0">Upcoming Classes</h3>
            </div>

            <!-- Class Items -->
            <div class="px-3">
                <!-- Psychology Class -->
                <div class="class-item-custom d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center flex-grow-1">
                        <div class="class-image-custom me-3"
                            style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuDOM2c6pMyg5RHOOB7ea-JzGe0jnIpnFBEu_4CDiQ7ONL7ZY-yeNsTFX4ofDoztdfYyNkpgDvh3-x-pRBvL4yY52jqzmy1rhhyOa0iVzY5QFRCbGZkv5fmQgfg1gJPyT6YE3Xa8gJeHLKkthPg42CihnKelbc1Y6whTtiIhxHfzlHCqcwXkQ6IcwBDqRfMUrK7BR_Ng89q3rfflSzNdmjXGhCQGbWj5Vv4mpEGlNtosTvZY8vX0QB3UHvArKqn9R0ypBhs8NsocSkU");'>
                        </div>
                        <div class="d-flex flex-column flex-grow-1">
                            <p class="text-dark fw-medium mb-1 text-truncate">Introduction to Psychology</p>
                            <p class="text-muted small mb-0 text-truncate-2">Room 201 · 10:00 AM - 11:00 AM</p>
                        </div>
                    </div>
                    <button class="btn btn-custom-outline ms-3 flex-shrink-0">
                        <span>View</span>
                    </button>
                </div>

                <!-- Calculus Class -->
                <div class="class-item-custom d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center flex-grow-1">
                        <div class="class-image-custom me-3"
                            style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuAzOempcQAOz1ClRlfF1DCpzo_NMftLShPYEuW0Tk0krCxtaaAYD6mptvry6TvngRl4eL1GuQoHjW_mxNaa_3a41Er7bF7cokn4msP_rC8rhOC__hZp5G-uGnutB3GelwB9WPP0RdOG38WQ64AXk7VCA0fDnhCNoMBghXsv7FuvxAYXbs_yVKU9hCrsqj1eqVA4F_5gDPhCOA-gQdGf-9xlVQb4usSfjm7NhjKhSixczD2EXFNlBoRbFcndbFMXCeFjpSsLjpjGmjE");'>
                        </div>
                        <div class="d-flex flex-column flex-grow-1">
                            <p class="text-dark fw-medium mb-1 text-truncate">Calculus I</p>
                            <p class="text-muted small mb-0 text-truncate-2">Room 305 · 11:30 AM - 12:30 PM</p>
                        </div>
                    </div>
                    <button class="btn btn-custom-outline ms-3 flex-shrink-0">
                        <span>View</span>
                    </button>
                </div>

                <!-- English Literature Class -->
                <div class="class-item-custom d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center flex-grow-1">
                        <div class="class-image-custom me-3"
                            style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuAIslxgzb_Ge2_CuAzi0CCtpc6UYvrCuw1I3jlmS0ACDVDoaXOfOsEQzeNs_HZ-1-_COcnRTacG_nhP-XdJupXhe0pVNN8A4Qp6ufhjLuaiTIt2l6XBzvKBAANxlRY0BEGNeiybpXUKlz7KCTNacTogVXhB8_tjiJrAFYSjxKyHDVCy4VocCquXxzjgCJcrSITFIFGhAbbPz7Wm-gTSf4uigdiTym8eJHrAhY3hx0yIOuKiKmJ79C3UmDSZwZFlqOp9JesWNkOcneU");'>
                        </div>
                        <div class="d-flex flex-column flex-grow-1">
                            <p class="text-dark fw-medium mb-1 text-truncate">English Literature</p>
                            <p class="text-muted small mb-0 text-truncate-2">Room 102 · 1:00 PM - 2:00 PM</p>
                        </div>
                    </div>
                    <button class="btn btn-custom-outline ms-3 flex-shrink-0">
                        <span>View</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/common/onboarding_modal.php'; ?>

<script id="tour-data" type="application/json">
    <?= json_encode($onboarding_steps); ?>
</script>

<!-- OCR MODAL - Keep original structure -->
<div class="modal fade" id="ocrModal" tabindex="-1" aria-labelledby="ocrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="ocrModalLabel">OCR Study Load Reader</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="ocr-alert" class="alert d-none" role="alert"></div>
                <div id="upload-step">
                    <h6 class="fw-medium">Step 1: Upload your PDF file</h6>
                    <div class="input-group mb-3">
                        <input type="file" class="form-control" id="studyLoadPdf" accept="application/pdf">
                        <label class="input-group-text" for="studyLoadPdf">Upload</label>
                    </div>
                    <button class="btn btn-primary" id="processOcrBtn">Process Document</button>
                </div>

                <div id="preview-step" style="display: none;">
                    <h6 class="fw-medium">Step 2: Preview Extracted Schedule</h6>
                    <div id="preview-content" class="p-3 border rounded mb-3"
                        style="max-height: 400px; overflow-y: auto;">
                        <p class="text-center text-muted">Awaiting file upload...</p>
                    </div>
                    <button class="btn btn-secondary me-2" id="backToUploadBtn">Back</button>
                    <button class="btn btn-success" id="confirmScheduleBtn">Confirm Extracted Schedule</button>
                </div>

                <div id="confirmation-step" style="display: none;">
                    <h6 class="fw-medium">Step 3: Confirmation</h6>
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
    $("#searchInput").on("keyup", function () {
        let query = $(this).val();
        if (query.length > 2) {
            $.ajax({
                url: "../../pages/user/search.php",
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

    // ================== AJAX OCR UPLOAD ==================
    $("#processOcrBtn").click(function () {
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
            dataType: "json",
            success: function (response) {
                if (response.success) {
                    $("#upload-step").hide();
                    $("#preview-step").show();
                    $("#ocr-alert").removeClass("d-none alert-danger").addClass("alert-success").text("Document processed successfully! Please review the extracted schedule.");

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
                    $("#ocr-alert").removeClass("d-none alert-success").addClass("alert-danger").text(response.error);
                    $("#upload-step").show();
                    $("#preview-step").hide();
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                $("#ocr-alert").removeClass("d-none alert-success").addClass("alert-danger").text("An unexpected error occurred during processing. Please try again.");
                console.error("AJAX Error: ", textStatus, errorThrown);
            }
        });
    });

    // ================== AJAX ONBOARDING ==================
    $("#viewTipsBtn").click(function () {
        $.get("../../pages/user/get_tips.php", function (data) {
            $("#onboardingContent").html(data);
        });
    });
    $("#restartOnboardingBtn").click(function () {
        $.post("../../pages/user/restart_onboarding.php", { user: "<?= $user['id'] ?? 0 ?>" }, function (data) {
            $("#onboardingContent").html("<div class='alert alert-success'>Onboarding restarted!</div>");
        });
    });

    // Back to upload button functionality
    $("#backToUploadBtn").click(function () {
        $("#preview-step").hide();
        $("#upload-step").show();
        $("#ocr-alert").addClass("d-none");
    });
</script>

<?php include('../../includes/semantics/footer.php'); ?>