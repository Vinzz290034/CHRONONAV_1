<?php
// includes/add_event_handler.php
require_once '../middleware/auth_check.php';
require_once '../config/db_connect.php';
require_once 'functions.php';

requireRole(['admin']); // Ensure only admins can use this handler

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_name = trim($_POST['event_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $location = trim($_POST['location'] ?? '');
    $event_type = $_POST['event_type'] ?? 'Other';
    $source_type = $_POST['source_type'] ?? 'public';

    if (empty($event_name) || empty($start_date)) {
        $_SESSION['message'] = "Event name and start date are required.";
        $_SESSION['message_type'] = 'danger';
        header("Location: ../pages/admin/calendar.php");
        exit();
    }

    $conn->begin_transaction();
    try {
        $user_id = $_SESSION['user']['id'];
        
        if ($source_type === 'public') {
            $stmt = $conn->prepare("INSERT INTO calendar_events (event_name, description, start_date, end_date, location, event_type) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Error preparing public event insert: " . $conn->error);
            }
            $stmt->bind_param("ssssss", $event_name, $description, $start_date, $end_date, $location, $event_type);
        } else {
            // This case is unlikely to be used by the 'Add' modal, but is good practice to handle.
            $stmt = $conn->prepare("INSERT INTO user_calendar_events (user_id, event_name, description, start_date, end_date, location, event_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Error preparing personal event insert: " . $conn->error);
            }
            $stmt->bind_param("issssss", $user_id, $event_name, $description, $start_date, $end_date, $location, $event_type);
        }

        if ($stmt->execute()) {
            $conn->commit();
            $_SESSION['message'] = "Event added successfully!";
            $_SESSION['message_type'] = 'success';
        } else {
            throw new Exception("Error executing event insert: " . $stmt->error);
        }

        $stmt->close();

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Add Event Error: " . $e->getMessage());
        $_SESSION['message'] = "An error occurred while adding the event: " . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }

    header("Location: ../pages/admin/calendar.php");
    exit();
} else {
    header("Location: ../pages/admin/calendar.php");
    exit();
}
?>