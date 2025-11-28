<?php
// CHRONONAV_WEB_DOSS/pages/user/save_schedule.php

session_start();
header('Content-Type: application/json');
require_once '../../middleware/auth_check.php'; // Ensures user is logged in
require_once '../../includes/db_connect.php'; // Get $pdo connection

$user_id = $_SESSION['user']['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $user_id === 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid request or user not authenticated.']);
    exit();
}

$schedule_json = $_POST['schedule'] ?? '';

if (empty($schedule_json)) {
    echo json_encode(['success' => false, 'error' => 'No schedule data received.']);
    exit();
}

try {
    // Decode the JSON data into a PHP array
    $schedule_data = json_decode($schedule_json, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($schedule_data)) {
        echo json_encode(['success' => false, 'error' => 'Invalid schedule data format.']);
        exit();
    }
    
    $pdo = get_db_connection();
    $pdo->beginTransaction();

    // 1. Clear any existing schedule for this user (UPDATE/OVERWRITE)
    $stmt_delete = $pdo->prepare("DELETE FROM user_schedule WHERE user_id = ?");
    $stmt_delete->execute([$user_id]);

    // 2. Insert the new schedule data
    $sql = "INSERT INTO user_schedule (user_id, sched_no, course_no, time, days, room, units) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert = $pdo->prepare($sql);

    $insert_count = 0;
    foreach ($schedule_data as $item) {
        // Basic sanitization/validation before insertion
        $sched_no   = $item['sched_no'] ?? null;
        $course_no  = $item['course_no'] ?? null;
        $time       = $item['time'] ?? null;
        $days       = $item['days'] ?? null;
        $room       = $item['room'] ?? null;
        $units      = $item['units'] ?? 0;
        
        // Skip if critical data is missing (e.g., a row failed OCR)
        if (empty($course_no) || empty($time) || empty($days) || empty($room)) {
            continue;
        }

        $stmt_insert->execute([
            $user_id, 
            $sched_no, 
            $course_no, 
            $time, 
            $days, 
            $room, 
            $units
        ]);
        $insert_count++;
    }

    $pdo->commit();

    if ($insert_count > 0) {
        // Successful save
        echo json_encode(['success' => true, 'message' => "Successfully saved {$insert_count} schedule entries."]);
    } else {
        // No entries were valid
        echo json_encode(['success' => false, 'error' => 'No valid schedule entries were found to save.']);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Schedule Save Error for user {$user_id}: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: Failed to save schedule.']);
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An unexpected server error occurred.']);
}
?>