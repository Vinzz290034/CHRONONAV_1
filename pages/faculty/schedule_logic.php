<?php
// CHRONONAV_WEB_UNO/pages/faculty/schedule_logic.php

// Include the authentication middleware to check if the user is logged in
require_once '../../middleware/auth_check.php';

// Include the database connection configuration
require_once '../../config/db_connect.php';

// Set the current page for the sidebar active state
$current_page = 'schedule';

// --- Authorization Check for this Specific Page ---
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] !== 'faculty' && $_SESSION['user']['role'] !== 'admin')) {
    $_SESSION['message'] = "Access denied. You do not have permission to view this page.";
    $_SESSION['message_type'] = "danger";
    if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin') {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../../index.php");
    }
    exit();
}

// Get the logged-in faculty's ID from the session
$faculty_id = $_SESSION['user']['id'];

// --- Fetch Rooms for Dropdowns ---
$rooms = [];
$rooms_sql = "SELECT id, room_name FROM rooms ORDER BY room_name ASC";
if ($rooms_stmt = $conn->prepare($rooms_sql)) {
    $rooms_stmt->execute();
    $rooms_result = $rooms_stmt->get_result();
    while ($row = $rooms_result->fetch_assoc()) {
        $rooms[] = $row;
    }
    $rooms_stmt->close();
} else {
    error_log("Failed to prepare rooms fetching statement: " . $conn->error);
    $_SESSION['message'] = "Database error: Could not load rooms. " . $conn->error;
    $_SESSION['message_type'] = "danger";
}


// --- Handle Form Submissions (Add/Edit/Delete Schedule) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? ''; // hidden input for action (add, edit, delete)
    $redirect_url = $_SERVER['PHP_SELF']; // Redirect back to this page

    switch ($action) {
        case 'add_schedule':
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $room_id = filter_var($_POST['room_id'] ?? '', FILTER_VALIDATE_INT);
            $event_date_str = $_POST['event_date'] ?? ''; // YYYY-MM-DD
            $start_time = $_POST['start_time'] ?? '';
            $end_time = $_POST['end_time'] ?? '';
            $academic_year = trim($_POST['academic_year'] ?? '');
            $semester = $_POST['semester'] ?? '';

            // Derive day_of_week from event_date
            $day_of_week = '';
            if (!empty($event_date_str)) {
                $day_of_week_num = date('w', strtotime($event_date_str)); // 0 for Sunday, 6 for Saturday
                $day_names_map = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                $day_of_week = $day_names_map[$day_of_week_num];
            }

            // Basic validation
            if (empty($title) || $room_id === false || empty($day_of_week) || empty($start_time) || empty($end_time) || empty($academic_year) || empty($semester)) {
                $_SESSION['message'] = "Please fill all required fields for adding a schedule.";
                $_SESSION['message_type'] = "danger";
            } else {
                $insert_sql = "INSERT INTO schedules (faculty_id, title, description, room_id, day_of_week, start_time, end_time, academic_year, semester) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                if ($stmt = $conn->prepare($insert_sql)) {
                    $stmt->bind_param("issssssss", $faculty_id, $title, $description, $room_id, $day_of_week, $start_time, $end_time, $academic_year, $semester);
                    if ($stmt->execute()) {
                        $_SESSION['message'] = "Schedule added successfully!";
                        $_SESSION['message_type'] = "success";
                    } else {
                        // Check for specific foreign key error
                        if ($stmt->errno == 1452) { // Error code for foreign key constraint fail
                             $_SESSION['message'] = "Error adding schedule: The selected Room does not exist. Please choose an existing Room.";
                        } else {
                            $_SESSION['message'] = "Error adding schedule: " . $stmt->error;
                        }
                        $_SESSION['message_type'] = "danger";
                        error_log("Error adding schedule: " . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    $_SESSION['message'] = "Database error: Could not prepare statement to add schedule.";
                    $_SESSION['message_type'] = "danger";
                    error_log("Failed to prepare add schedule statement: " . $conn->error);
                }
            }
            header("Location: " . $redirect_url);
            exit();

        case 'edit_schedule':
            $schedule_id = filter_var($_POST['schedule_id'] ?? '', FILTER_VALIDATE_INT);
            $title = trim($_POST['edit_title'] ?? '');
            $description = trim($_POST['edit_description'] ?? '');
            $room_id = filter_var($_POST['edit_room_id'] ?? '', FILTER_VALIDATE_INT);
            $day_of_week = $_POST['edit_day_of_week'] ?? ''; // This should be pre-filled correctly
            $start_time = $_POST['edit_start_time'] ?? '';
            $end_time = $_POST['edit_end_time'] ?? '';
            $academic_year = trim($_POST['edit_academic_year'] ?? '');
            $semester = $_POST['edit_semester'] ?? '';

            if ($schedule_id === false || empty($title) || $room_id === false || empty($day_of_week) || empty($start_time) || empty($end_time) || empty($academic_year) || empty($semester)) {
                $_SESSION['message'] = "Please fill all required fields and ensure a valid ID for editing.";
                $_SESSION['message_type'] = "danger";
            } else {
                $update_sql = "UPDATE schedules SET title = ?, description = ?, room_id = ?, day_of_week = ?, start_time = ?, end_time = ?, academic_year = ?, semester = ? WHERE schedule_id = ? AND faculty_id = ?";
                if ($stmt = $conn->prepare($update_sql)) {
                    $stmt->bind_param("ssssssssii", $title, $description, $room_id, $day_of_week, $start_time, $end_time, $academic_year, $semester, $schedule_id, $faculty_id);
                    if ($stmt->execute()) {
                        $_SESSION['message'] = "Schedule updated successfully!";
                        $_SESSION['message_type'] = "success";
                    } else {
                         // Check for specific foreign key error
                        if ($stmt->errno == 1452) { // Error code for foreign key constraint fail
                             $_SESSION['message'] = "Error updating schedule: The selected Room does not exist. Please choose an existing Room.";
                        } else {
                            $_SESSION['message'] = "Error updating schedule: " . $stmt->error;
                        }
                        $_SESSION['message_type'] = "danger";
                        error_log("Error updating schedule: " . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    $_SESSION['message'] = "Database error: Could not prepare statement to update schedule.";
                    $_SESSION['message_type'] = "danger";
                    error_log("Failed to prepare update schedule statement: " . $conn->error);
                }
            }
            header("Location: " . $redirect_url);
            exit();

        case 'delete_schedule':
            $schedule_id = filter_var($_POST['schedule_id'] ?? '', FILTER_VALIDATE_INT);

            if ($schedule_id === false) {
                $_SESSION['message'] = "Invalid schedule ID for deletion.";
                $_SESSION['message_type'] = "danger";
            } else {
                // Ensure only the owner can delete their schedule
                $delete_sql = "DELETE FROM schedules WHERE schedule_id = ? AND faculty_id = ?";
                if ($stmt = $conn->prepare($delete_sql)) {
                    $stmt->bind_param("ii", $schedule_id, $faculty_id);
                    if ($stmt->execute()) {
                        $_SESSION['message'] = "Schedule deleted successfully!";
                        $_SESSION['message_type'] = "success";
                    } else {
                        $_SESSION['message'] = "Error deleting schedule: " . $stmt->error;
                        $_SESSION['message_type'] = "danger";
                        error_log("Error deleting schedule: " . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    $_SESSION['message'] = "Database error: Could not prepare statement to delete schedule.";
                    $_SESSION['message_type'] = "danger";
                    error_log("Failed to prepare delete schedule statement: " . $conn->error);
                }
            }
            header("Location: " . $redirect_url);
            exit();
    }
}

// --- Fetch Schedules for Display ---
$schedules = []; // Initialize an empty array to store fetched schedule data

// Prepare SQL query to fetch schedules for the logged-in faculty
// Using the corrected column names: schedule_id, title, description, day_of_week, start_time, end_time, room_id
$sql = "SELECT s.schedule_id, s.title, s.description, s.day_of_week, s.start_time, s.end_time, s.room_id, r.room_name, s.academic_year, s.semester
        FROM schedules s
        JOIN rooms r ON s.room_id = r.id
        WHERE s.faculty_id = ?
        ORDER BY FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), s.start_time ASC";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $schedules[] = $row;
        }
    }
    $stmt->close();
} else {
    error_log("Failed to prepare schedule fetching statement: " . $conn->error);
    $_SESSION['message'] = "Database error: Could not prepare statement to fetch schedule.";
    $_SESSION['message_type'] = "danger";
}

// JSON encode schedules for JavaScript use
$schedules_json = json_encode($schedules);
?>