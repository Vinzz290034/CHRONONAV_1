<?php
// includes/edit_event_handler.php
require_once '../middleware/auth_check.php';
require_once '../config/db_connect.php';
require_once 'functions.php';

requireRole(['admin']); // Ensure only admins can use this handler

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'] ?? null;
    $event_name = trim($_POST['event_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $location = trim($_POST['location'] ?? '');
    $event_type = $_POST['event_type'] ?? 'Other';
    $source_type = $_POST['source_type'] ?? 'public';
    $user_id = $_SESSION['user']['id'];

    if (empty($event_id) || empty($event_name) || empty($start_date)) {
        $_SESSION['message'] = "Event ID, name, and start date are required for editing.";
        $_SESSION['message_type'] = 'danger';
        header("Location: ../pages/admin/calendar.php");
        exit();
    }

    $conn->begin_transaction();
    try {
        if ($source_type === 'public') {
            $stmt = $conn->prepare("UPDATE calendar_events SET event_name = ?, description = ?, start_date = ?, end_date = ?, location = ?, event_type = ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Error preparing public event update: " . $conn->error);
            }
            $stmt->bind_param("ssssssi", $event_name, $description, $start_date, $end_date, $location, $event_type, $event_id);
        } elseif ($source_type === 'personal') {
            $stmt = $conn->prepare("UPDATE user_calendar_events SET event_name = ?, description = ?, start_date = ?, end_date = ?, location = ?, event_type = ? WHERE id = ? AND user_id = ?");
            if (!$stmt) {
                throw new Exception("Error preparing personal event update: " . $conn->error);
            }
            $stmt->bind_param("ssssssii", $event_name, $description, $start_date, $end_date, $location, $event_type, $event_id, $user_id);
        } else {
            throw new Exception("Invalid source type provided.");
        }

        if ($stmt->execute()) {
            $conn->commit();
            if ($stmt->affected_rows > 0) {
                $_SESSION['message'] = "Event updated successfully!";
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = "No changes were made to the event.";
                $_SESSION['message_type'] = 'warning';
            }
        } else {
            throw new Exception("Error executing event update: " . $stmt->error);
        }

        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Edit Event Error: " . $e->getMessage());
        $_SESSION['message'] = "An error occurred while updating the event: " . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }

    header("Location: ../pages/admin/calendar.php");
    exit();
} else {
    header("Location: ../pages/admin/calendar.php");
    exit();
}
?>