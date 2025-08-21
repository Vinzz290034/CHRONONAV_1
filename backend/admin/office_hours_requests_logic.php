<?php
// CHRONONAV_WEB_UNO/backend/admin/office_hours_requests_logic.php

// Ensure session is started if not already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/db_connect.php'; // Your existing MySQLi connection file

// --- Helper Functions (Reused) ---

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

// --- Specific Functions for Office Hours Requests ---

/**
 * Fetches all office hour requests from the database.
 * Joins with users table to get faculty name and email.
 */
function getAllOfficeHoursRequests($conn) {
    $sql = "SELECT
                ohr.id,
                ohr.faculty_id,
                u.name AS faculty_name,
                u.email AS faculty_email,
                ohr.proposed_day,
                ohr.proposed_start_time,
                ohr.proposed_end_time,
                ohr.request_letter_message,
                ohr.status,
                ohr.admin_reply_message,
                ohr.requested_at,
                ohr.responded_at,
                ohr.approved_day,
                ohr.approved_start_time,
                ohr.approved_end_time
            FROM
                office_hours_request ohr -- Corrected from office_hours_requests
            JOIN
                users u ON ohr.faculty_id = u.id
            ORDER BY
                ohr.requested_at DESC";

    $result = $conn->query($sql);
    $requests = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
        $result->free();
    } else {
        error_log("Error fetching office hours requests: " . $conn->error);
    }
    return $requests;
}

// --- Handle POST Requests for Admin Actions ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $request_id = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT);

    // Ensure only admins can perform these actions
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        $_SESSION['message'] = "Unauthorized access. Admin privileges required.";
        $_SESSION['message_type'] = "danger";
        header("Location: ../../auth/logout.php"); // Or redirect to an error page
        exit();
    }

    if (!$request_id && $action !== 'add_initial_faculty_oh_slot') { // Most actions require a request_id
        $_SESSION['message'] = "Invalid request ID provided.";
        $_SESSION['message_type'] = "danger";
        header("Location: ../../pages/admin/office_hours_requests.php");
        exit();
    }

    switch ($action) {
        case 'approve_request':
            $admin_reply = trim($_POST['admin_reply'] ?? 'Approved as requested.');
            $approved_day = trim($_POST['approved_day'] ?? ''); // From modal
            $approved_start_time = trim($_POST['approved_start_time'] ?? ''); // From modal
            $approved_end_time = trim($_POST['approved_end_time'] ?? ''); // From modal

            if (empty($approved_day) || empty($approved_start_time) || empty($approved_end_time)) {
                   $_SESSION['message'] = "Approved day and time must be provided.";
                   $_SESSION['message_type'] = "danger";
                   break;
            }

            // Get faculty_id for this request to insert into consultation_hours
            $stmt = $conn->prepare("SELECT faculty_id FROM office_hours_request WHERE id = ?"); // Corrected from office_hours_requests
            if ($stmt) {
                $stmt->bind_param("i", $request_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $request = $result->fetch_assoc();
                $stmt->close();

                if ($request) {
                    $faculty_id = $request['faculty_id'];

                    // 1. Update the request status in office_hours_request
                    $sql_update_request = "UPDATE office_hours_request SET status = 'approved', admin_reply_message = ?, responded_at = NOW(), approved_day = ?, approved_start_time = ?, approved_end_time = ? WHERE id = ?"; // Corrected from office_hours_requests
                    $params_update = [$admin_reply, $approved_day, $approved_start_time, $approved_end_time, $request_id];
                    $types_update = 'ssssi';

                    if (executePreparedQuery($conn, $sql_update_request, $params_update, $types_update)) {
                        // 2. Add/update the approved slot into consultation_hours for faculty
                        // Use INSERT ... ON DUPLICATE KEY UPDATE to handle existing slots
                        $sql_upsert_consultation = "INSERT INTO consultation_hours (faculty_id, day_of_week, start_time, end_time, is_active) VALUES (?, ?, ?, ?, 1)
                                                     ON DUPLICATE KEY UPDATE end_time = VALUES(end_time), is_active = 1"; // Reactivate if it was disabled
                        $params_upsert = [$faculty_id, $approved_day, $approved_start_time, $approved_end_time];
                        $types_upsert = 'isss';

                        if (executePreparedQuery($conn, $sql_upsert_consultation, $params_upsert, $types_upsert)) {
                            $_SESSION['message'] = "Office hour request approved and added to consultation hours!";
                            $_SESSION['message_type'] = "success";
                        } else {
                            $_SESSION['message'] = "Office hour request approved, but failed to update consultation hours.";
                            $_SESSION['message_type'] = "warning";
                        }
                    } else {
                        $_SESSION['message'] = "Failed to approve office hour request.";
                        $_SESSION['message_type'] = "danger";
                    }
                } else {
                    $_SESSION['message'] = "Office hour request not found.";
                    $_SESSION['message_type'] = "danger";
                }
            } else {
                $_SESSION['message'] = "Database error fetching request details for approval.";
                $_SESSION['message_type'] = "danger";
            }
            break;

        case 'reject_request':
            $admin_reply = trim($_POST['admin_reply'] ?? 'Your request has been rejected.');
            $sql = "UPDATE office_hours_request SET status = 'rejected', admin_reply_message = ?, responded_at = NOW() WHERE id = ?"; // Corrected from office_hours_requests
            if (executePreparedQuery($conn, $sql, [$admin_reply, $request_id], 'si')) {
                $_SESSION['message'] = "Office hour request rejected.";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Failed to reject office hour request.";
                $_SESSION['message_type'] = "danger";
            }
            break;

        case 'revise_request': // Admin can also propose a revision
            $admin_reply = trim($_POST['admin_reply'] ?? '');
            $revised_day = trim($_POST['revised_day'] ?? '');
            $revised_start_time = trim($_POST['revised_start_time'] ?? '');
            $revised_end_time = trim($_POST['revised_end_time'] ?? '');

            if (empty($admin_reply) || empty($revised_day) || empty($revised_start_time) || empty($revised_end_time)) {
                $_SESSION['message'] = "All revision fields (reply, day, start, end) are required.";
                $_SESSION['message_type'] = "danger";
                break;
            }

            $sql = "UPDATE office_hours_request SET status = 'revised', admin_reply_message = ?, responded_at = NOW(), approved_day = ?, approved_start_time = ?, approved_end_time = ? WHERE id = ?"; // Corrected from office_hours_requests
            $params = [$admin_reply, $revised_day, $revised_start_time, $revised_end_time, $request_id];
            $types = 'ssssi';

            if (executePreparedQuery($conn, $sql, $params, $types)) {
                $_SESSION['message'] = "Office hour request marked for revision and reply sent.";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Failed to mark request for revision.";
                $_SESSION['message_type'] = "danger";
            }
            break;

        default:
            $_SESSION['message'] = "Invalid action for office hours requests.";
            $_SESSION['message_type'] = "danger";
            break;
    }

    header("Location: ../../pages/admin/office_hours_requests.php");
    exit();
}

// For GET requests or when included by the page, fetch all requests
// This will be used by pages/admin/office_hours_requests.php
$officeHoursRequests = getAllOfficeHoursRequests($conn);
?>