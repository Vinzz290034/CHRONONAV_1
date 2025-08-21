<?php
// pages/admin/delete_public_event.php
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

requireRole(['admin']); // Only admins can delete public events

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_event'])) {
    $event_id = $_POST['event_id'] ?? null;

    if (empty($event_id)) {
        $_SESSION['message'] = "Event ID is required.";
        $_SESSION['message_type'] = 'danger';
    } else {
        $stmt = $conn->prepare("DELETE FROM calendar_events WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $event_id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $_SESSION['message'] = "Public event deleted successfully.";
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = "Event not found or already deleted.";
                    $_SESSION['message_type'] = 'warning';
                }
            } else {
                $_SESSION['message'] = "Error deleting event: " . $stmt->error;
                $_SESSION['message_type'] = 'danger';
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = "Database error: " . $conn->error;
            $_SESSION['message_type'] = 'danger';
        }
    }
} else {
    $_SESSION['message'] = "Invalid request.";
    $_SESSION['message_type'] = 'danger';
}

// Redirect back to calendar.php
header("Location: calendar.php");
exit();
