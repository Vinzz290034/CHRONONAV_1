<?php
/**
 * Logs a user action into the audit_log table.
 *
 * @param mysqli $conn The database connection object.
 * @param int $user_id The ID of the user performing the action.
 * @param string $action A brief, descriptive name for the action (e.g., 'Profile Updated').
 * @param string $details A more detailed description of the action.
 * @return void
 */
function log_audit_action($conn, $user_id, $action, $details) {
    // Escape or sanitize details to prevent SQL injection in case the string isn't controlled
    $escaped_details = htmlspecialchars($details, ENT_QUOTES, 'UTF-8');
    
    $stmt = $conn->prepare("INSERT INTO audit_log (user_id, action, details) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iss", $user_id, $action, $escaped_details);
        $stmt->execute();
        $stmt->close();
    } else {
        error_log("Failed to prepare audit log statement: " . $conn->error);
    }
}