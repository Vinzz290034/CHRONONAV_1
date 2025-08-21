<?php
// pages/user/save_admin_event.php
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';

// A more robust role check using a function (assuming you have one, or just use the direct check below)
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') {
    $_SESSION['message'] = "Access denied.";
    $_SESSION['message_type'] = "danger";
    header("Location: ../../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_event'])) {
    $calendar_event_id = $_POST['calendar_event_id'] ?? null;
    $current_user_id = $_SESSION['user']['id'];

    if (empty($calendar_event_id)) {
        $_SESSION['message'] = "Invalid event to save.";
        $_SESSION['message_type'] = "danger";
        header("Location: calendar.php");
        exit();
    }

    // First, check if the event is an actual admin event
    $stmt_check = $conn->prepare("SELECT event_name, description, start_date, end_date, location, event_type FROM calendar_events WHERE id = ?");
    if (!$stmt_check) {
        $_SESSION['message'] = "Database error preparing statement: " . $conn->error;
        $_SESSION['message_type'] = "danger";
        header("Location: calendar.php");
        exit();
    }
    $stmt_check->bind_param("i", $calendar_event_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $admin_event = $result_check->fetch_assoc();
    $stmt_check->close();

    if (!$admin_event) {
        $_SESSION['message'] = "Public event not found.";
        $_SESSION['message_type'] = "danger";
        header("Location: calendar.php");
        exit();
    }

    // Check if the user has already saved this event
    $stmt_exists = $conn->prepare("SELECT id FROM user_calendar_events WHERE user_id = ? AND calendar_event_id = ?");
    if (!$stmt_exists) {
        $_SESSION['message'] = "Database error preparing check: " . $conn->error;
        $_SESSION['message_type'] = "danger";
        header("Location: calendar.php");
        exit();
    }
    $stmt_exists->bind_param("ii", $current_user_id, $calendar_event_id);
    $stmt_exists->execute();
    $stmt_exists->store_result();
    if ($stmt_exists->num_rows > 0) {
        $_SESSION['message'] = "You have already added this event to your calendar.";
        $_SESSION['message_type'] = "info";
        $stmt_exists->close();
        header("Location: calendar.php");
        exit();
    }
    $stmt_exists->close();

    // Insert a copy/link of the admin event into the user's personal calendar table
    // FIX: Changed the query to use a placeholder for `is_personal`
    $stmt_insert = $conn->prepare("INSERT INTO user_calendar_events (user_id, calendar_event_id, event_name, description, start_date, end_date, location, event_type, is_personal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt_insert) {
        $is_personal_value = 0; // 0 for FALSE, as it's a copied admin event

        // FIX: Changed the type string and added the `is_personal` parameter
        $stmt_insert->bind_param(
            "iissssssi",
            $current_user_id,
            $calendar_event_id,
            $admin_event['event_name'],
            $admin_event['description'],
            $admin_event['start_date'],
            $admin_event['end_date'],
            $admin_event['location'],
            $admin_event['event_type'],
            $is_personal_value
        );

        if ($stmt_insert->execute()) {
            $_SESSION['message'] = "Event added to your calendar!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error adding event to your calendar: " . $stmt_insert->error;
            $_SESSION['message_type'] = "danger";
        }
        $stmt_insert->close();
    } else {
        $_SESSION['message'] = "Database error preparing save event: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    }

    header("Location: calendar.php");
    exit();
} else {
    $_SESSION['message'] = "Invalid request.";
    $_SESSION['message_type'] = "danger";
    header("Location: calendar.php");
    exit();
}