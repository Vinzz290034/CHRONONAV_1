<?php
// backend/add_edit_event.php
session_start();
require_once '../config/db_connect.php';
require_once '../middleware/auth_check.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user']['id'];
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $eventType = $_POST['event_type']; // 'one_time' or 'recurring'
    $startDate = $_POST['start_date']; // For one_time
    $dayOfWeek = isset($_POST['day_of_week']) ? $_POST['day_of_week'] : null; // For recurring
    $startTime = $_POST['start_time'];
    $endTime = $_POST['end_time'];
    $location = trim($_POST['location']);
    $isCompleted = isset($_POST['is_completed']) ? 1 : 0; // For one_time reminders

    // Basic validation
    if (empty($title) || empty($startTime) || (empty($startDate) && $eventType === 'one_time') || (empty($dayOfWeek) && $eventType === 'recurring')) {
        $response['message'] = "Please fill in all required fields.";
        echo json_encode($response);
        exit();
    }

    if ($event_id > 0) {
        // --- EDIT existing event ---
        $stmt = $conn->prepare("UPDATE events SET title = ?, description = ?, event_type = ?, start_date = ?, day_of_week = ?, start_time = ?, end_time = ?, location = ?, is_completed = ? WHERE id = ? AND user_id = ?");
        if ($stmt) {
            // Set NULL for start_date/day_of_week based on event_type
            $finalStartDate = ($eventType === 'one_time') ? $startDate : null;
            $finalDayOfWeek = ($eventType === 'recurring') ? $dayOfWeek : null;
            $finalIsCompleted = ($eventType === 'one_time') ? $isCompleted : null; // Only reminders have completion status

            $stmt->bind_param("ssisssisiii", $title, $description, $eventType, $finalStartDate, $finalDayOfWeek, $startTime, $endTime, $location, $finalIsCompleted, $event_id, $user_id);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Event updated successfully.";
            } else {
                $response['message'] = "Failed to update event: " . $stmt->error;
                error_log("Edit Event Error: " . $stmt->error);
            }
            $stmt->close();
        } else {
            $response['message'] = "Database error (prepare statement for update): " . $conn->error;
            error_log("Edit Event Prepare Error: " . $conn->error);
        }
    } else {
        // --- ADD new event ---
        $stmt = $conn->prepare("INSERT INTO events (user_id, title, description, event_type, start_date, day_of_week, start_time, end_time, location, is_completed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $finalStartDate = ($eventType === 'one_time') ? $startDate : null;
            $finalDayOfWeek = ($eventType === 'recurring') ? $dayOfWeek : null;
            $finalIsCompleted = ($eventType === 'one_time') ? $isCompleted : null;

            $stmt->bind_param("issssisssi", $user_id, $title, $description, $eventType, $finalStartDate, $finalDayOfWeek, $startTime, $endTime, $location, $finalIsCompleted);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Event added successfully.";
                $response['new_event_id'] = $conn->insert_id;
            } else {
                $response['message'] = "Failed to add event: " . $stmt->error;
                error_log("Add Event Error: " . $stmt->error);
            }
            $stmt->close();
        } else {
            $response['message'] = "Database error (prepare statement for insert): " . $conn->error;
            error_log("Add Event Prepare Error: " . $conn->error);
        }
    }
} else {
    $response['message'] = "Invalid request method.";
}

echo json_encode($response);
$conn->close();
?>