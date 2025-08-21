<?php
// CHRONONAV_WEBZD/api/update_onboarding_status.php
// This API endpoint handles updating the user's onboarding completion status.

require_once '../config/db_connect.php'; // Adjust path as necessary

header('Content-Type: application/json'); // Set header for JSON response

session_start();

$response = ['success' => false, 'message' => ''];

// 1. Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    $response['message'] = 'Authentication required. User not logged in.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user']['id'];
$status_action = $_POST['status'] ?? ''; // Expected: 'completed', 'skipped', 'pending'

// 2. Validate the received status action
if (!in_array($status_action, ['completed', 'skipped', 'pending'])) {
    $response['message'] = 'Invalid onboarding status action provided.';
    echo json_encode($response);
    exit();
}

// 3. Determine the boolean value for the database
$is_completed_for_db = false; // Default to false (meaning needs onboarding)
if ($status_action === 'completed' || $status_action === 'skipped') {
    $is_completed_for_db = true; // Mark as completed if user finished or skipped
}
// If status_action is 'pending', $is_completed_for_db remains false, effectively restarting

// 4. Update the database
$stmt = $conn->prepare("UPDATE users SET is_onboarding_completed = ? WHERE id = ?");
if ($stmt) {
    // 'i' for integer (boolean is treated as tinyint in MySQL)
    $stmt->bind_param("ii", $is_completed_for_db, $user_id);
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Onboarding status updated successfully to ' . $status_action . '.';

        // IMPORTANT: Update the session variable immediately to reflect the change
        $_SESSION['user']['is_onboarding_completed'] = $is_completed_for_db;

    } else {
        $response['message'] = 'Database update failed: ' . $stmt->error;
        error_log("ChronoNav Onboarding Error: DB update failed for user ID {$user_id}: " . $stmt->error);
    }
    $stmt->close();
} else {
    $response['message'] = 'Failed to prepare database statement: ' . $conn->error;
    error_log("ChronoNav Onboarding Error: Failed to prepare statement: " . $conn->error);
}

echo json_encode($response);
$conn->close();
?>