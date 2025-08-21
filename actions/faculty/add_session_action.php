<?php
// CHRONONAV_WEB_UNO/actions/faculty/add_session_action.php
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php'; // For requireRole()

requireRole(['faculty']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = $_POST['class_id'] ?? 0;
    $session_date = $_POST['session_date'] ?? '';
    $actual_start_time = $_POST['actual_start_time'] ?? '';
    $actual_end_time = $_POST['actual_end_time'] ?? '';
    $room_id = $_POST['room_id'] ?? null;
    $notes = $_POST['notes'] ?? '';

    $recorded_by_user_id = $_SESSION['user']['id'];

    if (empty($class_id) || empty($session_date) || empty($actual_start_time) || empty($actual_end_time)) {
        $_SESSION['message'] = "Please fill in all required fields (Class, Date, Start Time, End Time).";
        $_SESSION['message_type'] = "danger";
        header("Location: ../../pages/faculty/add_session.php?class_id=" . $class_id);
        exit();
    }

    $stmt_verify = $conn->prepare("SELECT COUNT(*) FROM classes WHERE class_id = ? AND faculty_id = ?");
    $stmt_verify->bind_param("ii", $class_id, $recorded_by_user_id);
    $stmt_verify->execute();
    $stmt_verify->bind_result($count);
    $stmt_verify->fetch();
    $stmt_verify->close();

    if ($count == 0) {
        $_SESSION['message'] = "You are not authorized to add sessions for this class.";
        $_SESSION['message_type'] = "danger";
        header("Location: ../../pages/faculty/my_classes.php");
        exit();
    }

    $stmt = $conn->prepare("
        INSERT INTO class_sessions (class_id, session_date, actual_start_time, actual_end_time, room_id, notes, recorded_by_user_id)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $room_id_bind = ($room_id === '' || $room_id === null) ? null : (int)$room_id;

    $stmt->bind_param("isssisi", $class_id, $session_date, $actual_start_time, $actual_end_time, $room_id_bind, $notes, $recorded_by_user_id);

    // --- START: MODIFIED CODE BLOCK WITH TRY-CATCH ---
    try {
        if ($stmt->execute()) {
            $_SESSION['message'] = "Class session added successfully. You can now record attendance for this session.";
            $_SESSION['message_type'] = "success";
            header("Location: ../../pages/faculty/attendance_logs.php?class_id=" . $class_id);
            exit();
        } else {
            // This 'else' block might not be reached if mysqli_sql_exception is thrown directly
            $_SESSION['message'] = "An unexpected error occurred: " . $stmt->error;
            $_SESSION['message_type'] = "danger";
            header("Location: ../../pages/faculty/add_session.php?class_id=" . $class_id);
            exit();
        }
    } catch (mysqli_sql_exception $e) {
        // Catch the specific MySQLi exception
        if ($e->getCode() == 1062) { // MySQL error code for Duplicate entry for key
            $_SESSION['message'] = "A session for this class at this date and time already exists. Please choose a different date or time.";
            $_SESSION['message_type'] = "warning";
        } else {
            // For any other SQL errors
            $_SESSION['message'] = "Database error: " . $e->getMessage();
            $_SESSION['message_type'] = "danger";
        }
        header("Location: ../../pages/faculty/add_session.php?class_id=" . $class_id);
        exit();
    } finally {
        // Ensure statement is closed regardless of success or failure
        $stmt->close();
    }
    // --- END: MODIFIED CODE BLOCK WITH TRY-CATCH ---

} else {
    $_SESSION['message'] = "Invalid request method.";
    $_SESSION['message_type'] = "danger";
    header("Location: ../../pages/faculty/my_classes.php");
    exit();
}