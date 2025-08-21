<?php
// backend/delete_event.php
session_start();
require_once '../config/db_connect.php';
require_once '../middleware/auth_check.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user']['id'];
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;

    if ($event_id <= 0) {
        $response['message'] = "Invalid event ID.";
        echo json_encode($response);
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM events WHERE id = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $event_id, $user_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = "Event deleted successfully.";
            } else {
                $response['message'] = "Event not found or you don't have permission to delete it.";
            }
        } else {
            $response['message'] = "Failed to delete event: " . $stmt->error;
            error_log("Delete Event Error: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $response['message'] = "Database error (prepare statement for delete): " . $conn->error;
        error_log("Delete Event Prepare Error: " . $conn->error);
    }
} else {
    $response['message'] = "Invalid request method.";
}

echo json_encode($response);
$conn->close();
?>