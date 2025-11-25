<?php
// CHRONONAV_WEB_DOSS/pages/admin/room_manager.php

require_once '../../middleware/auth_check.php'; // Ensures user is logged in and session is started
require_once '../../config/db_connect.php'; // Database connection
require_once '../../includes/functions.php'; // Assuming requireRole function is here

// Ensure the user is logged in and has the 'admin' role for this admin page
requireRole(['admin']);

// Fetch user data for header display (name, role, profile_img)
// This is done here so the variables are available before including the header.
$user_id = $_SESSION['user']['id'];
$stmt = $conn->prepare("SELECT name, role, profile_img FROM users WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $header_user_data = $result->fetch_assoc();
        $display_username = htmlspecialchars($header_user_data['name'] ?? 'Admin User');
        $display_user_role = htmlspecialchars(ucfirst($header_user_data['role'] ?? 'Admin'));
        // Construct profile_img_src for the header, relative to the templates/admin directory
        $profile_img_src = (strpos($header_user_data['profile_img'], 'uploads/') === 0) ? '../../' . $header_user_data['profile_img'] : '../../uploads/profiles/default-avatar.png';
    } else {
        // Fallback if user data for header somehow isn't found (shouldn't happen with auth_check)
        $display_username = 'Admin User';
        $display_user_role = 'Admin';
        $profile_img_src = '../../uploads/profiles/default-avatar.png';
    }
    $stmt->close();
} else {
    $display_username = 'Admin User';
    $display_user_role = 'Admin';
    $profile_img_src = '../../uploads/profiles/default-avatar.png';
}

$page_title = "Building Room Manager";
$current_page = "room_manager"; // This should match a key in your sidenav for active state

$message = '';
$message_type = '';

// --- Handle Add/Update Room ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_room'])) {
    $room_id = $_POST['room_id'] ?? null;
    $room_name = trim($_POST['room_name'] ?? '');
    $capacity = (int) ($_POST['capacity'] ?? 0);
    $room_type = $_POST['room_type'] ?? 'Classroom';
    $equipment = trim($_POST['equipment'] ?? '');
    $location_description = trim($_POST['location_description'] ?? '');

    if (empty($room_name)) {
        $message = "Room Name is required.";
        $message_type = 'danger';
    } else {
        if ($room_id) { // Update existing room
            $stmt = $conn->prepare("UPDATE rooms SET room_name = ?, capacity = ?, room_type = ?, equipment = ?, location_description = ?, updated_at = NOW() WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("sisssi", $room_name, $capacity, $room_type, $equipment, $location_description, $room_id);
                if ($stmt->execute()) {
                    $message = "Room updated successfully!";
                    $message_type = 'success';
                } else {
                    $message = "Error updating room: " . $stmt->error;
                    $message_type = 'danger';
                }
                $stmt->close();
            }
        } else { // Add new room
            $stmt = $conn->prepare("INSERT INTO rooms (room_name, capacity, room_type, equipment, location_description) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sisss", $room_name, $capacity, $room_type, $equipment, $location_description);
                if ($stmt->execute()) {
                    $message = "Room added successfully!";
                    $message_type = 'success';
                } else {
                    $message = "Error adding room: " . $stmt->error;
                    // Check for duplicate entry error
                    if ($conn->errno == 1062) { // MySQL error code for duplicate entry
                        $message = "Error: A room with this name already exists.";
                    }
                    $message_type = 'danger';
                }
                $stmt->close();
            }
        }
    }
}

// --- Handle Delete Room ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_room'])) {
    $room_id_to_delete = $_POST['delete_room_id'] ?? null;
    if ($room_id_to_delete) {
        $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $room_id_to_delete);
            if ($stmt->execute()) {
                $message = "Room deleted successfully!";
                $message_type = 'success';
            } else {
                $message = "Error deleting room: " . $stmt->error;
                $message_type = 'danger';
            }
            $stmt->close();
        }
    }
}

// --- Handle Linking Room to Schedule ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['link_schedule'])) {
    $schedule_id_to_link = $_POST['schedule_id_to_link'] ?? null;
    $selected_room_id = $_POST['selected_room_id'] ?? null;

    if ($schedule_id_to_link && ($selected_room_id !== null)) { // Allow selected_room_id to be 0 or NULL to unlink
        $stmt = $conn->prepare("UPDATE schedules SET room_id = ? WHERE schedule_id = ?");
        if ($stmt) {
            // If $selected_room_id is "0" or empty, treat it as NULL for the database
            // This assumes the room_id column in the schedules table is nullable.
            $bind_room_id = ($selected_room_id == "0" || empty($selected_room_id)) ? NULL : (int) $selected_room_id;


            // A safer way for nullable INT in mysqli where 0 indicates unlinked:
            $stmt->bind_param("ii", $bind_room_id, $schedule_id_to_link);

            if ($stmt->execute()) {
                $message = "Schedule linked/updated with room successfully!";
                $message_type = 'success';
            } else {
                $message = "Error linking schedule to room: " . $stmt->error;
                $message_type = 'danger';
            }
            $stmt->close();
        }
    } else {
        $message = "Invalid data for linking schedule.";
        $message_type = 'danger';
    }
}


// --- Fetch all Rooms ---
$rooms = [];
$stmt_rooms = $conn->prepare("SELECT * FROM rooms ORDER BY room_name ASC");
$stmt_rooms->execute();
$result_rooms = $stmt_rooms->get_result();
while ($row = $result_rooms->fetch_assoc()) {
    $rooms[] = $row;
}
$stmt_rooms->close();

// --- Fetch all Schedules (and their current room_id) ---
$schedules = [];
// Joining with rooms to get room_name for display
$stmt_schedules = $conn->prepare("SELECT s.*, r.room_name FROM schedules s LEFT JOIN rooms r ON s.room_id = r.id ORDER BY s.day_of_week, s.start_time");
$stmt_schedules->execute();
$result_schedules = $stmt_schedules->get_result();
while ($row = $result_schedules->fetch_assoc()) {
    $schedules[] = $row;
}
$stmt_schedules->close();

// --- START HTML STRUCTURE ---
// Include the admin header which contains <head> and opening <body> tags
require_once '../../templates/admin/header_admin.php';
require_once '../../templates/admin/sidenav_admin.php'; // Sidenav is included here
?>

<style>
    body {
        font-family: "Space Grotesk", "Noto Sans", sans-serif;

        min-height: 100vh;
    }

    .main-dashboard-content {
        margin-left: 20%;
        padding: 0px 35px;
    }

    .main-dashboard-content-wrapper {
        font-family: "Space Grotesk", "Noto Sans", sans-serif;
        min-height: 100vh;
        width: 100%;
    }

    .page-title {
        color: #0e151b;
        font-size: 32px;
        font-weight: 700;
        letter-spacing: -0.015em;
        margin-bottom: 0;
    }

    .section-title {
        color: #0e151b;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: -0.015em;
        margin-bottom: 1rem;
    }

    .btn-primary-custom {
        background-color: #1d7dd7;
        border: none;
        color: #f8fafb;
        font-weight: 700;
        font-size: 0.875rem;
        height: 40px;
        padding: 0 1rem;
        border-radius: 0.5rem;
    }

    .btn-primary-custom:hover {
        background-color: #155bb5;
        color: #f8fafb;
    }

    .btn-light {
        background-color: #e8edf3;
        border: none;
        color: #0e151b;
        font-weight: 700;
        font-size: 0.875rem;
        height: 40px;
        padding: 0 1rem;
        border-radius: 0.5rem;
    }

    .btn-light:hover {
        background-color: #d1dce6;
        color: #0e151b;
    }

    .btn-light-sm {
        border: none;
        color: #0e151b;
        font-weight: 500;
        font-size: 0.875rem;
        height: 32px;
        padding: 0 1rem;
        border-radius: 0.5rem;
    }

    .btn-light-sm .bg-warning-subtle:hover {
        background-color: #d1dce6;
        color: #0e151b;
    }

    /* Form Styles */
    .form-label-custom {
        color: #0e151b;
        font-size: 1rem;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    .form-control-custom {
        background-color: #f8fafb;
        border: 1px solid #d1dce6;
        color: #0e151b;
        height: 56px;
        padding: 15px;
        border-radius: 0.5rem;
        width: 100%;
    }

    .form-control-custom:focus {
        box-shadow: none;
        border-color: #d1dce6;
        background-color: #f8fafb;
    }

    .form-control-custom::placeholder {
        color: #507495;
    }

    textarea.form-control-custom {
        min-height: 144px;
        resize: none;
        height: auto;
    }

    /* Table Styles */
    .table-custom {
        background-color: #f8fafb;
        border: 1px solid #d1dce6;
        border-radius: 0.5rem;
        overflow: hidden;
    }

    .table-custom thead th {
        background-color: #f8fafb;
        color: #0e151b;
        font-size: 0.875rem;
        font-weight: 500;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #d1dce6;
    }

    .table-custom tbody td {
        color: #507495;
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
        border-bottom: 1px solid #d1dce6;
        height: 72px;
        vertical-align: middle;
    }

    .table-custom tbody tr:last-child td {
        border-bottom: none;
    }

    .table-custom tbody .room-name {
        color: #0e151b;
    }

    /* Responsive table container */
    .table-responsive-custom {
        border-radius: 0.5rem;
        border: 1px solid #d1dce6;
        background-color: #f8fafb;
    }

    /* Alert Styles */
    .alert {
        border: none;
        border-radius: 0.5rem;
        padding: 1rem 1.25rem;
        margin: 1rem 0;
        font-weight: 500;
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
    }

    .alert-info {
        background-color: #d1ecf1;
        color: #0c5460;
    }

    /* Button Styles */
    .btn-sm {
        font-size: 0.875rem;
        padding: 0.375rem 0.75rem;
        height: 32px;
        border-radius: 0.5rem;
    }

    .btn-info {
        background-color: #17a2b8;
        border: none;
        color: white;
    }

    .btn-danger {
        background-color: #eb4e5eff;
        border: none;
        color: white;
    }

    .btn-success {
        background-color: #28a745;
        border: none;
        color: white;
    }

    /* Hover effect for the warning-subtle (Edit) */
    .btn-light-sm.bg-warning-subtle:hover {
        background-color: #ffe08a;
        /* darker yellow */
        color: #000;
    }

    /* Hover effect for the danger-subtle (Delete) */
    .btn-light-sm.btn-danger:hover {
        background-color: #f1aeb5;
        /* darker pink/red */
        color: #000;
    }

    /* Badge Styles */
    .badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.35em 0.65em;
        border-radius: 50rem;
    }

    .bg-primary {
        background-color: #1d7dd7 !important;
    }

    .bg-warning {
        background-color: #ffc107 !important;
        color: #000 !important;
    }

    /* Form Select Styles */
    .form-select {
        background-color: #f8fafb;
        border: 1px solid #d1dce6;
        color: #0e151b;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
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

    /* Add these media queries at the end of your existing CSS - ENHANCED MOBILE & TABLET RESPONSIVENESS */

    /* Mobile: 767px and below - ENHANCED */
    @media (max-width: 767px) {
        .main-dashboard-content {
            margin-left: 0 !important;
            padding: 0.75rem !important;
        }

        .p-4 {
            padding: 1rem !important;
        }

        .page-title.fs-3 {
            font-size: 1.4rem !important;
            text-align: center;
            width: 100%;
            min-width: auto !important;
            margin-bottom: 0.5rem !important;
        }

        .section-title {
            font-size: 1.1rem !important;
            text-align: center;
            margin-bottom: 0.75rem !important;
        }

        /* Form mobile optimization */
        .row.mb-2 {
            margin-bottom: 0.5rem !important;
        }

        .col-12.col-md-6 {
            width: 100% !important;
            margin-bottom: 0.75rem !important;
        }

        .form-control-custom {
            height: 48px !important;
            padding: 0.875rem !important;
            font-size: 16px !important;
            /* Prevent zoom on iOS */
            border-radius: 0.375rem !important;
        }

        textarea.form-control-custom {
            min-height: 120px !important;
            height: auto !important;
        }

        .form-label-custom {
            font-size: 0.9rem !important;
            margin-bottom: 0.375rem !important;
            text-align: left;
        }

        /* Button mobile optimization */
        .d-flex.flex-wrap.gap-3 {
            flex-direction: column !important;
            gap: 0.75rem !important;
            width: 100%;
        }

        .btn-primary-custom,
        .btn-light {
            width: 100% !important;
            justify-content: center;
            height: 48px !important;
            margin-bottom: 0 !important;
        }

        /* Table mobile optimization */
        .table-responsive-custom {
            border-radius: 0.375rem !important;
            overflow-x: auto;
        }

        .table-custom {
            min-width: 600px;
            /* Allow horizontal scrolling */
        }

        .table-custom thead th {
            padding: 0.5rem 0.75rem !important;
            font-size: 0.8rem !important;
        }

        .table-custom tbody td {
            padding: 0.5rem 0.75rem !important;
            font-size: 0.8rem !important;
            height: auto !important;
            min-height: 60px;
        }

        .table-custom tbody .fw-bold {
            flex-direction: column !important;
            gap: 0.5rem !important;
            align-items: stretch !important;
        }

        .table-custom .d-flex.gap-2 {
            flex-direction: column !important;
            gap: 0.5rem !important;
        }

        .table-custom .btn-light-sm {
            width: 100% !important;
            justify-content: center;
            height: 40px !important;
        }

        /* Schedule linking form mobile optimization */
        .table-custom .d-flex.align-items-center {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 0.5rem !important;
        }

        .table-custom .form-select {
            width: 100% !important;
            margin-right: 0 !important;
            margin-bottom: 0.5rem !important;
        }

        .table-custom .me-2 {
            margin-right: 0 !important;
        }

        /* Alert mobile optimization */
        .alert {
            padding: 0.875rem !important;
            margin: 0.75rem 0 !important;
            border-radius: 0.375rem !important;
            font-size: 0.9rem;
        }

        /* Specific column adjustments for mobile tables */
        .table-custom th.w-10,
        .table-custom td:first-child {
            min-width: 60px;
        }

        .table-custom th.w-25,
        .table-custom td:nth-child(2) {
            min-width: 120px;
        }

        .table-custom th.w-50,
        .table-custom td:nth-child(6) {
            min-width: 150px;
        }
    }

    /* Tablet: 768px to 1023px - ENHANCED */
    @media (min-width: 768px) and (max-width: 1023px) {
        .main-dashboard-content {
            margin-left: 15% !important;
            padding: 1.25rem !important;
        }

        .p-4 {
            padding: 1.5rem !important;
        }

        .page-title.fs-3 {
            font-size: 1.6rem !important;
        }

        .section-title {
            font-size: 1.3rem !important;
        }

        /* Form tablet optimization */
        .col-12.col-md-6 {
            width: 100% !important;
        }

        .form-control-custom {
            height: 52px !important;
            padding: 1rem !important;
        }

        textarea.form-control-custom {
            min-height: 140px !important;
        }

        /* Button tablet optimization */
        .d-flex.flex-wrap.gap-3 {
            flex-direction: row !important;
            gap: 1rem !important;
        }

        .btn-primary-custom,
        .btn-light {
            flex: 1;
            min-width: 140px;
            max-width: 200px;
        }

        /* Table tablet optimization */
        .table-responsive-custom {
            border-radius: 0.5rem !important;
        }

        .table-custom {
            width: 100%;
        }

        .table-custom thead th {
            padding: 0.625rem 0.875rem !important;
            font-size: 0.85rem !important;
        }

        .table-custom tbody td {
            padding: 0.625rem 0.875rem !important;
            font-size: 0.85rem !important;
        }

        .table-custom .d-flex.gap-2 {
            flex-direction: row !important;
            gap: 0.5rem !important;
            flex-wrap: wrap;
        }

        .table-custom .btn-light-sm {
            flex: 1;
            min-width: 80px;
        }

        /* Schedule linking form tablet optimization */
        .table-custom .d-flex.align-items-center {
            flex-direction: row !important;
            gap: 0.5rem !important;
        }

        .table-custom .form-select {
            flex: 2;
            min-width: 150px;
        }

        .table-custom .btn-light-sm.btn-success {
            flex: 1;
            min-width: 80px;
        }
    }

    /* Desktop: 1024px and above - Refined */
    @media (min-width: 1024px) {
        .main-dashboard-content {
            margin-left: 20% !important;
            padding: 0px 35px !important;
        }

        .p-4 {
            padding: 2rem !important;
        }

        .page-title.fs-3 {
            font-size: 2rem !important;
        }

        .section-title {
            font-size: 1.5rem !important;
        }

        .col-12.col-md-6 {
            width: 50% !important;
        }

        .form-control-custom {
            height: 56px !important;
            padding: 15px !important;
        }

        textarea.form-control-custom {
            min-height: 144px !important;
        }

        .d-flex.flex-wrap.gap-3 {
            flex-direction: row !important;
            gap: 1rem !important;
        }

        .btn-primary-custom,
        .btn-light {
            width: auto !important;
        }

        .table-custom .d-flex.gap-2 {
            flex-direction: row !important;
            gap: 0.5rem !important;
        }

        .table-custom .d-flex.align-items-center {
            flex-direction: row !important;
            gap: 0.5rem !important;
        }
    }

    /* Enhanced responsive sidebar adjustments */
    @media (max-width: 1023px) {
        .sidebar-toggle {
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 1.1rem;
        }
    }

    /* Ensure proper spacing on all devices */
    @media (max-width: 767px) {
        .mb-3 {
            margin-bottom: 1rem !important;
        }

        .mb-5 {
            margin-bottom: 2rem !important;
        }

        .mb-2 {
            margin-bottom: 0.5rem !important;
        }

        .mb-1 {
            margin-bottom: 0.375rem !important;
        }

        .gap-2 {
            gap: 0.5rem !important;
        }

        .gap-3 {
            gap: 1rem !important;
        }
    }

    /* Improved touch targets for mobile */
    @media (max-width: 767px) {
        .btn {
            min-height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-light-sm {
            min-height: 40px;
        }

        .form-control-custom {
            min-height: 48px;
        }

        textarea.form-control-custom {
            min-height: 120px;
        }

        /* Better touch feedback */
        .btn:active,
        .table-custom tbody tr:active {
            transform: scale(0.98);
            transition: transform 0.1s ease;
        }
    }

    /* Enhanced form responsiveness */
    @media (max-width: 767px) {
        .form-control-custom::placeholder {
            font-size: 14px;
        }

        textarea.form-control-custom::placeholder {
            font-size: 14px;
        }

        .form-select {
            font-size: 14px;
            padding: 0.625rem !important;
        }
    }

    /* Print styles for room manager */
    @media print {
        .main-dashboard-content {
            margin-left: 0 !important;
            max-width: 100% !important;
            padding: 0 !important;
        }

        .btn-primary-custom,
        .btn-light,
        .btn-light-sm,
        .sidebar-toggle,
        form {
            display: none !important;
        }

        .table-custom {
            width: 100%;
        }

        .table-custom tbody tr {
            break-inside: avoid;
        }
    }

    /* Responsive sidebar toggle button */
    .sidebar-toggle {
        display: none;
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1100;
        background: #3e99f4;
        color: white;
        border: none;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 1.1rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    @media (max-width: 1023px) {
        .sidebar-toggle {
            display: flex;
            position: fixed;
            right: 1rem;
            left: unset;
            top: 5rem;
            z-index: 1100;
            width: 30px;
            height: 30px;
            background: #f0f2f5;
            color: #111418;
            border: 1px solid #ddd;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, background-color 0.3s ease, right 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    }

    /* Enhanced mobile typography */
    @media (max-width: 767px) {
        body {
            font-size: 14px;
        }

        .form-label-custom {
            font-size: 0.9rem !important;
        }

        .room-name {
            font-size: 0.9rem;
            font-weight: 600;
        }

        .badge {
            font-size: 0.7rem !important;
        }
    }

    /* Improved table scrolling for mobile */
    @media (max-width: 767px) {
        .table-responsive-custom {
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }

        .table-responsive-custom::-webkit-scrollbar {
            height: 6px;
        }

        .table-responsive-custom::-webkit-scrollbar-thumb {
            background-color: #d1dce6;
            border-radius: 3px;
        }
    }

    /* Enhanced loading states */
    @media (max-width: 767px) {
        .table-custom tbody tr {
            transition: all 0.3s ease;
        }

        .table-custom tbody tr:active {
            background-color: #f8f9fa;
        }
    }

    /* Better spacing for mobile forms */
    @media (max-width: 767px) {
        form.mb-5 {
            margin-bottom: 2rem !important;
        }

        .row:last-child {
            margin-bottom: 0 !important;
        }
    }

    /* Tablet-specific form improvements */
    @media (min-width: 768px) and (max-width: 1023px) {
        .form-control-custom {
            max-width: 100%;
        }

        .table-custom {
            font-size: 0.9rem;
        }
    }

    /* Enhanced action buttons in tables */
    @media (max-width: 767px) {
        .table-custom .btn-light-sm {
            font-size: 0.8rem !important;
            padding: 0.5rem 0.75rem !important;
        }

        .table-custom .form-select.form-select-sm {
            font-size: 0.8rem !important;
        }
    }

    /* Improved table header alignment */
    @media (max-width: 767px) {
        .table-custom thead th {
            text-align: center;
        }

        .table-custom tbody td {
            text-align: left;
        }

        .table-custom .d-flex.justify-content-center {
            justify-content: flex-start !important;
        }
    }
</style>



<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- Font Family -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
<!-- important------------------------------------------------------------------------------------------------ -->


<div class="main-dashboard-content">
    <!-- Header Section -->
    <div class="p-4">
        <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
            <p class="page-title mb-0 fs-3" style="min-width: 288px;">Building Room Manager</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Add/Update Room Section -->
        <h2 class="section-title mb-3">+ Add/Update Room</h2>
        <form action="room_manager.php" method="POST" class="mb-5">
            <input type="hidden" name="room_id" id="room_id">

            <div class="row mb-2">
                <div class="col-12 col-md-6">
                    <div class="mb-1">
                        <label class="form-label-custom">Room Name</label>
                        <input type="text" class="form-control-custom" name="room_name" id="room_name"
                            placeholder="e.g., Room 101" required>
                    </div>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-12 col-md-6">
                    <div class="mb-1">
                        <label class="form-label-custom">Capacity</label>
                        <input type="number" class="form-control-custom" name="capacity" id="capacity"
                            placeholder="e.g., 30" min="1">
                    </div>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-12 col-md-6">
                    <div class="mb-1">
                        <label class="form-label-custom">Room Type</label>
                        <select class="form-control-custom" name="room_type" id="room_type">
                            <option value="Classroom">Classroom</option>
                            <option value="Laboratory">Laboratory</option>
                            <option value="Lecture Hall">Lecture Hall</option>
                            <option value="Seminar Room">Seminar Room</option>
                            <option value="Office">Office</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-12 col-md-6">
                    <div class="mb-1">
                        <label class="form-label-custom">Equipment</label>
                        <input type="text" class="form-control-custom" name="equipment" id="equipment"
                            placeholder="e.g., Projector, Whiteboard">
                    </div>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-12 col-md-6">
                    <div class="mb-1">
                        <label class="form-label-custom">Location Description</label>
                        <textarea class="form-control-custom" name="location_description" id="location_description"
                            placeholder="e.g., First floor, near the main entrance"></textarea>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-3 mb-5">
                <button type="submit" name="submit_room" class="btn-primary-custom">Save Room</button>
                <button type="button" class="btn-light" onclick="clearRoomForm()">Clear Form</button>
            </div>
        </form>

        <!-- Existing Rooms Section -->
        <h2 class="section-title mb-3">≡ Existing Rooms</h2>
        <?php if (!empty($rooms)): ?>
            <div class="table-responsive-custom mb-5">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th class="w-10">ID</th>
                            <th class="w-25">Room Name</th>
                            <th class="w-10">Capacity</th>
                            <th class="w-10">Type</th>
                            <th class="w-25">Equipment</th>
                            <th class="w-50">Location</th>
                            <th class="w-auto">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $room): ?>
                            <tr>
                                <td><?= htmlspecialchars($room['id']) ?></td>
                                <td class="room-name"><?= htmlspecialchars($room['room_name']) ?></td>
                                <td><?= htmlspecialchars($room['capacity'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($room['room_type']) ?></td>
                                <td><?= htmlspecialchars($room['equipment'] ?? 'None') ?></td>
                                <td><?= htmlspecialchars($room['location_description'] ?? 'N/A') ?></td>
                                <td class="fw-bold d-flex gap-2 d-flex justify-content-center align-items-center">
                                    <button class="btn-light-sm edit-room-btn btn btn-info"
                                        data-id="<?= htmlspecialchars($room['id']) ?>"
                                        data-name="<?= htmlspecialchars($room['room_name']) ?>"
                                        data-capacity="<?= htmlspecialchars($room['capacity'] ?? '') ?>"
                                        data-type="<?= htmlspecialchars($room['room_type']) ?>"
                                        data-equipment="<?= htmlspecialchars($room['equipment'] ?? '') ?>"
                                        data-location="<?= htmlspecialchars($room['location_description'] ?? '') ?>">
                                        Edit
                                    </button>
                                    <form action="room_manager.php" method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this room? This will unlink it from schedules.');"
                                        class="d-inline">
                                        <input type="hidden" name="delete_room_id" value="<?= htmlspecialchars($room['id']) ?>">
                                        <button type="submit" name="delete_room"
                                            class="btn-light-sm btn btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">No rooms defined yet. Add a new room above.</div>
        <?php endif; ?>

        <!-- Link Rooms to Schedules Section -->
        <h2 class="section-title mb-3">⛓ Link Rooms to Schedules</h2>
        <?php if (!empty($schedules)): ?>
            <div class="table-responsive-custom mb-5">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th class="w-10">Sch.ID</th>
                            <th class="w-25">Title</th>
                            <th class="w-10">Day</th>
                            <th class="w-25">Time</th>
                            <th class="w-10">Current Room</th>
                            <th class="w-50">Link/Change Room</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $schedule): ?>
                            <tr>
                                <td><?= htmlspecialchars($schedule['schedule_id']) ?></td>
                                <td class="room-name"><?= htmlspecialchars($schedule['title']) ?></td>
                                <td><?= htmlspecialchars($schedule['day_of_week']) ?></td>
                                <td><?= htmlspecialchars(date('h:i A', strtotime($schedule['start_time']))) ?> -
                                    <?= htmlspecialchars(date('h:i A', strtotime($schedule['end_time']))) ?>
                                </td>
                                <td>
                                    <?php if (!empty($schedule['room_name'])): ?>
                                        <button class="btn-light-sm w-100"><?= htmlspecialchars($schedule['room_name']) ?></button>
                                    <?php else: ?>
                                        <button class="btn-light-sm w-100">Unassigned</button>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold">
                                    <form action="room_manager.php" method="POST" class="d-flex align-items-center">
                                        <input type="hidden" name="schedule_id_to_link"
                                            value="<?= htmlspecialchars($schedule['schedule_id']) ?>">
                                        <select name="selected_room_id" class="form-select form-select-sm me-2">
                                            <option value="0">-- Unassign Room --</option>
                                            <?php foreach ($rooms as $room): ?>
                                                <option value="<?= htmlspecialchars($room['id']) ?>"
                                                    <?= ($schedule['room_id'] == $room['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($room['room_name']) ?> (Cap.
                                                    <?= htmlspecialchars($room['capacity']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="link_schedule"
                                            class="btn-light-sm btn-light-sm btn btn-success">Save</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">No schedules found to link rooms to.</div>
        <?php endif; ?>
    </div>
</div>

<script src="../../assets/js/jquery.min.js"></script>
<script src="../../assets/js/script.js"></script>
<script>
    // JavaScript for Edit Room button to populate form
    document.querySelectorAll('.edit-room-btn').forEach(button => {
        button.addEventListener('click', function () {
            document.getElementById('room_id').value = this.dataset.id;
            document.getElementById('room_name').value = this.dataset.name;
            document.getElementById('capacity').value = this.dataset.capacity;
            document.getElementById('room_type').value = this.dataset.type;
            document.getElementById('equipment').value = this.dataset.equipment;
            document.getElementById('location_description').value = this.dataset.location;
            // Scroll to the form
            document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
        });
    });

    // JavaScript to clear the form
    function clearRoomForm() {
        document.getElementById('room_id').value = '';
        document.getElementById('room_name').value = '';
        document.getElementById('capacity').value = '';
        document.getElementById('room_type').value = 'Classroom';
        document.getElementById('equipment').value = '';
        document.getElementById('location_description').value = '';
    }
</script>

<?php
// Include the common footer which closes <body> and <html> and includes common JS
include_once '../../templates/footer.php';
?>