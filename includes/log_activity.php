<?php
/**
 * Logs an activity to the database.
 *
 * @param int $user_id The ID of the user performing the activity.
 * @param string $user_name The name of the user.
 * @param string $user_role The role of the user (e.g., 'admin', 'user', 'faculty').
 * @param string $activity_type A category for the activity (e.g., 'login', 'registration', 'update', 'delete', 'view').
 * @param string $description A detailed description of the activity.
 * @return bool True on success, false on failure.
 */
function log_activity($user_id, $user_name, $user_role, $activity_type, $description) {
    // You should already have a PDO connection established.
    // Assuming get_db_connection() exists in db_connect.php and is included.
    global $pdo; // Or call get_db_connection() directly if not globally available

    if (!isset($pdo) || !$pdo instanceof PDO) {
        // If $pdo is not set or not a PDO object, try to get a new connection.
        // This makes log_activity more robust, but might open multiple connections
        // if db_connect.php is not designed for single instance.
        require_once 'db_connect.php'; // Ensure db_connect is available
        $pdo = get_db_connection();
    }

    if (!$pdo) {
        error_log("Failed to get PDO connection for activity logging.");
        return false;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, user_name, user_role, activity_type, description) VALUES (:user_id, :user_name, :user_role, :activity_type, :description)");

        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_name', $user_name, PDO::PARAM_STR);
        $stmt->bindParam(':user_role', $user_role, PDO::PARAM_STR);
        $stmt->bindParam(':activity_type', $activity_type, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);

        return $stmt->execute();

    } catch (PDOException $e) {
        error_log("Error logging activity: " . $e->getMessage());
        return false;
    }
}

// You might also have a function to get recent activities for display, etc.
// function get_recent_activities($limit = 10) { ... }
?>