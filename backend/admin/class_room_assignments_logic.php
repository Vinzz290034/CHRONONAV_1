<?php
// CHRONONAV_WEB_UNO/backend/admin/class_room_assignments_logic.php (FINAL CORRECTION - bind_param types)

// Ensure session is started if not already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/db_connect.php'; // Your existing MySQLi connection file

// --- Helper Functions ---

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
        // The error occurs here if types string length != params count
        call_user_func_array([$stmt, 'bind_param'], $bind_names);
    }

    $success = $stmt->execute();
    if ($success === false) {
        error_log("Execute failed: " . $stmt->error . " for SQL: " . $sql);
    }
    $stmt->close();
    return $success;
}

// Function to get all users with 'faculty' role
function getFacultyUsers($conn) {
    $sql = "SELECT id, name, email FROM users WHERE role = 'faculty' AND is_active = 1 ORDER BY name ASC";
    $result = $conn->query($sql);
    $faculty = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $faculty[] = $row;
        }
        $result->free();
    } else {
        error_log("Error fetching faculty users: " . $conn->error);
    }
    return $faculty;
}

// Function to get all rooms
function getAllRooms($conn) {
    // Using your actual room table columns: room_name, capacity
    $sql = "SELECT id, room_name, capacity FROM rooms ORDER BY room_name ASC";
    $result = $conn->query($sql);
    $rooms = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rooms[] = $row;
        }
        $result->free();
    } else {
        error_log("Error fetching rooms: " . $conn->error);
    }
    return $rooms;
}

// Function to get all class offerings (from your 'classes' table)
function getAllClassOfferings($conn) {
    $sql = "SELECT
                c.class_id,
                c.class_name,
                c.class_code,
                c.faculty_id,
                u.name AS faculty_name,
                c.room_id,
                r.room_name,
                c.semester,
                c.day_of_week,
                c.start_time,
                c.end_time
            FROM
                classes c
            LEFT JOIN
                users u ON c.faculty_id = u.id
            LEFT JOIN
                rooms r ON c.room_id = r.id
            ORDER BY
                c.semester DESC, c.class_code ASC, c.day_of_week, c.start_time ASC";

    $result = $conn->query($sql);
    $offerings = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $offerings[] = $row;
        }
        $result->free();
    } else {
        error_log("Error fetching class offerings: " . $conn->error);
    }
    return $offerings;
}

// --- Handle POST Requests ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Ensure only admins can perform these actions
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        $_SESSION['message'] = "Unauthorized access. Admin privileges required.";
        $_SESSION['message_type'] = "danger";
        header("Location: ../../auth/logout.php");
        exit();
    }

    switch ($action) {
        case 'add_class_offering':
            $class_name = trim($_POST['class_name'] ?? '');
            $class_code = trim($_POST['class_code'] ?? '');
            $semester = trim($_POST['semester'] ?? '');
            $faculty_id = filter_var($_POST['faculty_id'] ?? null, FILTER_VALIDATE_INT);
            $room_id = filter_var($_POST['room_id'] ?? null, FILTER_VALIDATE_INT);
            $day_of_week = trim($_POST['day_of_week'] ?? '');
            $start_time = trim($_POST['start_time'] ?? '');
            $end_time = trim($_POST['end_time'] ?? '');

            if (empty($class_name) || empty($class_code) || empty($semester) || !$faculty_id || !$room_id || empty($day_of_week) || empty($start_time) || empty($end_time)) {
                $_SESSION['message'] = "All fields are required to add a class offering.";
                $_SESSION['message_type'] = "danger";
                break;
            }

            $sql = "INSERT INTO classes (class_name, class_code, semester, faculty_id, room_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [$class_name, $class_code, $semester, $faculty_id, $room_id, $day_of_week, $start_time, $end_time];
            $types = 'sssiiiss'; // Corrected types: 3 strings, 2 integers, 3 strings = 8 characters for 8 parameters

            if (executePreparedQuery($conn, $sql, $params, $types)) {
                $_SESSION['message'] = "Class offering added successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Failed to add class offering. A class with that code might already exist (if unique constraint is still there) or other data error.";
                $_SESSION['message_type'] = "danger";
            }
            break;

        case 'edit_class_offering':
            $class_id_to_update = filter_var($_POST['class_id'] ?? null, FILTER_VALIDATE_INT);
            $class_name = trim($_POST['class_name'] ?? '');
            $class_code = trim($_POST['class_code'] ?? '');
            $semester = trim($_POST['semester'] ?? '');
            $faculty_id = filter_var($_POST['faculty_id'] ?? null, FILTER_VALIDATE_INT);
            $room_id = filter_var($_POST['room_id'] ?? null, FILTER_VALIDATE_INT);
            $day_of_week = trim($_POST['day_of_week'] ?? '');
            $start_time = trim($_POST['start_time'] ?? '');
            $end_time = trim($_POST['end_time'] ?? '');

            if ($class_id_to_update && !empty($class_name) && !empty($class_code) && !empty($semester) && $faculty_id && $room_id && !empty($day_of_week) && !empty($start_time) && !empty($end_time)) {
                $sql = "UPDATE classes SET class_name = ?, class_code = ?, semester = ?, faculty_id = ?, room_id = ?, day_of_week = ?, start_time = ?, end_time = ? WHERE class_id = ?";
                $params = [$class_name, $class_code, $semester, $faculty_id, $room_id, $day_of_week, $start_time, $end_time, $class_id_to_update];
                $types = 'sssiiissi'; // Corrected types: 3 strings, 2 integers, 3 strings, 1 integer (for class_id) = 9 characters for 9 parameters

                if (executePreparedQuery($conn, $sql, $params, $types)) {
                    $_SESSION['message'] = "Class offering updated successfully!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Failed to update class offering. Check for data errors.";
                    $_SESSION['message_type'] = "danger";
                }
            } else {
                $_SESSION['message'] = "Invalid input for editing class offering.";
                $_SESSION['message_type'] = "danger";
            }
            break;

        case 'delete_class_offering':
            $class_id_to_delete = filter_var($_POST['class_id'] ?? null, FILTER_VALIDATE_INT);

            if ($class_id_to_delete) {
                $sql = "DELETE FROM classes WHERE class_id = ?";
                if (executePreparedQuery($conn, $sql, [$class_id_to_delete], 'i')) {
                    if ($conn->affected_rows > 0) {
                        $_SESSION['message'] = "Class offering deleted successfully!";
                        $_SESSION['message_type'] = "success";
                    } else {
                        $_SESSION['message'] = "Failed to delete class offering or it does not exist.";
                        $_SESSION['message_type'] = "danger";
                    }
                } else {
                    $_SESSION['message'] = "Failed to delete class offering.";
                    $_SESSION['message_type'] = "danger";
                }
            } else {
                $_SESSION['message'] = "Invalid class ID for deletion.";
                $_SESSION['message_type'] = "danger";
            }
            break;

        default:
            $_SESSION['message'] = "Unknown action.";
            $_SESSION['message_type'] = "danger";
            break;
    }

    header("Location: ../../pages/admin/class_room_assignments.php");
    exit();
}

?>