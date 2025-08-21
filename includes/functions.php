<?php
// CHRONONAV_WEB_DOSS/includes/functions.php


// Note: Ensure session_start() is called at the very top of all entry point files
// (e.g., in auth_check.php or a common header file) before any output is sent.

/**
 * Checks if a user is currently logged in.
 * Relies on the session variable 'user' being set by auth_check.php.
 *
 * @return bool True if logged in, false otherwise.
 */
function is_logged_in() {
    return isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['id']);
}

/**
 * Requires the current user to have one of the specified roles.
 * If the user does not have an allowed role, they are redirected to an access denied page.
 * This function assumes auth_check.php has already run and populated $_SESSION['user'].
 *
 * @param array $allowedRoles An array of roles (e.g., ['faculty', 'admin']).
 */
function requireRole($allowedRoles = []) {
    // If auth_check.php somehow failed or not included, ensure basic login check
    if (!is_logged_in()) {
        header("Location: /auth/login.php"); // Adjust path if necessary
        exit();
    }

    $currentUserRole = $_SESSION['user']['role'];

    if (!in_array($currentUserRole, $allowedRoles)) {
        // Redirect to an access denied page
        // Assuming /pages/access_denied.php exists
        header("Location: /pages/access_denied.php"); // Adjust path if necessary
        exit();
    }
}

/**
 * Sets a one-time message in the session for display on the next page load.
 *
 * @param string $type The type of message (e.g., 'success', 'danger', 'info', 'warning').
 * @param string $message The message text.
 */
function set_message($type, $message) {
    // Make sure the session is started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}


/**
 * Displays a one-time message stored in the session.
 * Messages are typically set using $_SESSION['message'] and $_SESSION['message_type'].
 */
function display_message() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['message']) && $_SESSION['message'] != '') {
        $message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info'; // Default to info
        $message = htmlspecialchars($_SESSION['message']); // Sanitize message

        echo '<div class="alert alert-' . $message_type . ' alert-dismissible fade show" role="alert">';
        echo $message;
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';

        // Clear the message from session after displaying
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
}

/**
 * Retrieves the ID of the currently logged-in user.
 * Assumes the user's data is stored in $_SESSION['user'].
 *
 * @return int|null The user's ID or null if not logged in.
 */
function getCurrentUserId() {
    if (is_logged_in()) {
        return $_SESSION['user']['id'];
    }
    return null;
}


?>