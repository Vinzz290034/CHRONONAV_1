<?php
// CHRONONAV_WEB_UNO/backend/faculty/set_office_consultation_logic.php

// Ensure session is started if not already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/db_connect.php'; // Your existing MySQLi connection file

// Ensure user is logged in and is faculty
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'faculty') {
    $_SESSION['message'] = "Access denied. You do not have permission to view this page.";
    $_SESSION['message_type'] = "danger";
    header("Location: ../../auth/logout.php");
    exit();
}

$faculty_id = $_SESSION['user']['id'];

// --- Helper Function (Reused) ---
// Function to execute a prepared statement
function executePreparedQuery($conn, $sql, $params, $types = '') {
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error . " for SQL: " . $sql);
        return false;
    }

    if (!empty($params) && !empty($types)) {
        $bind_names = array($types);
        foreach ($params as $key => $value) {
            $bind_names[] = &$params[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_names);
    }

    $success = $stmt->execute();
    if ($success === false) {
        error_log("Execute failed: " . $stmt->error . " for SQL: " . $sql);
    }
    $stmt->close();
    return $success;
}

// --- Specific Functions for Faculty Office/Consultation Hours ---

/**
 * Fetches all office hour requests made by the current faculty.
 */
function getFacultyOfficeHoursRequests($conn, $faculty_id) {
    $sql = "SELECT
                id,
                proposed_day,
                proposed_start_time,
                proposed_end_time,
                request_letter_message,
                status,
                admin_reply_message,
                requested_at,
                responded_at,
                approved_day,
                approved_start_time,
                approved_end_time
            FROM
                office_hours_request -- Corrected table name
            WHERE
                faculty_id = ?
            ORDER BY
                requested_at DESC";

    $stmt = $conn->prepare($sql);
    $requests = [];
    if ($stmt) {
        $stmt->bind_param("i", $faculty_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
        $result->free();
        $stmt->close();
    } else {
        error_log("Error preparing statement to fetch faculty office hour requests: " . $conn->error);
    }
    return $requests;
}

/**
 * Fetches all consultation hours set by the current faculty.
 */
function getFacultyConsultationHours($conn, $faculty_id) {
    $sql = "SELECT
                id,
                day_of_week,
                start_time,
                end_time,
                is_active
            FROM
                consultation_hours
            WHERE
                faculty_id = ?
            ORDER BY
                FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time ASC";
    // Using FIELD() for custom day order, might need adjustment based on your 'day_of_week' format ('MWF', 'TTh' etc.)
    // If you use 'MWF' etc., you might just want 'day_of_week, start_time ASC' or sort by specific logic in PHP.

    $stmt = $conn->prepare($sql);
    $consultation_slots = [];
    if ($stmt) {
        $stmt->bind_param("i", $faculty_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $consultation_slots[] = $row;
        }
        $result->free();
        $stmt->close();
    } else {
        error_log("Error preparing statement to fetch faculty consultation hours: " . $conn->error);
    }
    return $consultation_slots;
}

// --- Handle POST Requests for Faculty Actions ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'request_office_hours':
            $proposed_day = trim($_POST['proposed_day'] ?? '');
            $proposed_start_time = trim($_POST['proposed_start_time'] ?? '');
            $proposed_end_time = trim($_POST['proposed_end_time'] ?? '');
            $request_letter_message = trim($_POST['request_letter_message'] ?? '');

            if (empty($proposed_day) || empty($proposed_start_time) || empty($proposed_end_time) || empty($request_letter_message)) {
                $_SESSION['message'] = "Please fill all fields for office hour request.";
                $_SESSION['message_type'] = "danger";
                break;
            }

            // Insert new request with 'pending' status
            $sql = "INSERT INTO office_hours_request (faculty_id, proposed_day, proposed_start_time, proposed_end_time, request_letter_message, status) VALUES (?, ?, ?, ?, ?, 'pending')"; // Corrected table name
            $params = [$faculty_id, $proposed_day, $proposed_start_time, $proposed_end_time, $request_letter_message];
            $types = 'issss';

            if (executePreparedQuery($conn, $sql, $params, $types)) {
                $_SESSION['message'] = "Office hour request submitted successfully for admin approval.";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Failed to submit office hour request. Please try again.";
                $_SESSION['message_type'] = "danger";
            }
            break;

        case 'add_consultation_slot':
            $day_of_week = trim($_POST['consultation_day_of_week'] ?? '');
            $start_time = trim($_POST['consultation_start_time'] ?? '');
            $end_time = trim($_POST['consultation_end_time'] ?? '');

            if (empty($day_of_week) || empty($start_time) || empty($end_time)) {
                $_SESSION['message'] = "Please fill all fields for consultation slot.";
                $_SESSION['message_type'] = "danger";
                break;
            }

            // Insert new consultation slot
            $sql = "INSERT INTO consultation_hours (faculty_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)";
            $params = [$faculty_id, $day_of_week, $start_time, $end_time];
            $types = 'isss';

            if (executePreparedQuery($conn, $sql, $params, $types)) {
                $_SESSION['message'] = "Consultation hour slot added successfully.";
                $_SESSION['message_type'] = "success";
            } else {
                // This might fail due to UNIQUE key constraint (faculty_id, day_of_week, start_time)
                $_SESSION['message'] = "Failed to add consultation slot. Possible duplicate time for the same day.";
                $_SESSION['message_type'] = "danger";
            }
            break;

        case 'edit_consultation_slot':
            $slot_id = filter_var($_POST['slot_id'] ?? null, FILTER_VALIDATE_INT);
            $day_of_week = trim($_POST['edit_consultation_day_of_week'] ?? '');
            $start_time = trim($_POST['edit_consultation_start_time'] ?? '');
            $end_time = trim($_POST['edit_consultation_end_time'] ?? '');
            $is_active = isset($_POST['edit_consultation_is_active']) ? 1 : 0; // Checkbox value

            if ($slot_id && !empty($day_of_week) && !empty($start_time) && !empty($end_time)) {
                // Ensure the faculty owns this slot
                $check_owner_sql = "SELECT faculty_id FROM consultation_hours WHERE id = ?";
                $stmt_check = $conn->prepare($check_owner_sql);
                if($stmt_check) {
                    $stmt_check->bind_param("i", $slot_id);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();
                    $row_check = $result_check->fetch_assoc();
                    $stmt_check->close();

                    if ($row_check && (int)$row_check['faculty_id'] === (int)$faculty_id) {
                        $sql = "UPDATE consultation_hours SET day_of_week = ?, start_time = ?, end_time = ?, is_active = ? WHERE id = ? AND faculty_id = ?";
                        $params = [$day_of_week, $start_time, $end_time, $is_active, $slot_id, $faculty_id];
                        $types = 'sssiii';

                        if (executePreparedQuery($conn, $sql, $params, $types)) {
                            $_SESSION['message'] = "Consultation hour slot updated successfully.";
                            $_SESSION['message_type'] = "success";
                        } else {
                            $_SESSION['message'] = "Failed to update consultation slot. Possible duplicate time for the same day.";
                            $_SESSION['message_type'] = "danger";
                        }
                    } else {
                        $_SESSION['message'] = "Unauthorized: You do not own this consultation slot.";
                        $_SESSION['message_type'] = "danger";
                    }
                } else {
                    $_SESSION['message'] = "Database error checking slot ownership.";
                    $_SESSION['message_type'] = "danger";
                }
            } else {
                $_SESSION['message'] = "Invalid input for editing consultation slot.";
                $_SESSION['message_type'] = "danger";
            }
            break;

        case 'delete_consultation_slot':
            $slot_id = filter_var($_POST['slot_id'] ?? null, FILTER_VALIDATE_INT);

            if ($slot_id) {
                // Ensure the faculty owns this slot before deleting
                $sql = "DELETE FROM consultation_hours WHERE id = ? AND faculty_id = ?";
                if (executePreparedQuery($conn, $sql, [$slot_id, $faculty_id], 'ii')) {
                    if ($conn->affected_rows > 0) { // Check if any row was actually deleted
                        $_SESSION['message'] = "Consultation hour slot deleted successfully!";
                        $_SESSION['message_type'] = "success";
                    } else {
                        $_SESSION['message'] = "Failed to delete consultation slot or you do not own this slot.";
                        $_SESSION['message_type'] = "danger";
                    }
                } else {
                    $_SESSION['message'] = "Failed to delete consultation slot.";
                    $_SESSION['message_type'] = "danger";
                }
            } else {
                $_SESSION['message'] = "Invalid consultation slot ID for deletion.";
                $_SESSION['message_type'] = "danger";
            }
            break;

        default:
            $_SESSION['message'] = "Invalid action for faculty schedule management.";
            $_SESSION['message_type'] = "danger";
            break;
    }

    header("Location: ../../pages/faculty/set_office_consultation.php");
    exit();
}

// For GET requests or when included by the page, fetch data for display
$facultyOfficeHoursRequests = getFacultyOfficeHoursRequests($conn, $faculty_id);
$facultyConsultationHours = getFacultyConsultationHours($conn, $faculty_id);

// Messages from session (these lines appear to be duplicated from the start of the page,
// but they ensure messages set by POST requests are cleared after display on the GET request load)
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message']);
unset($_SESSION['message_type']);
?>