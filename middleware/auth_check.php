<?php
// CHRONONAV_WEBZD/middleware/auth_check.php

// Start the session if it hasn't been started yet
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is NOT logged in or if the essential 'id' or 'role' are missing from session
if (!isset($_SESSION['user']) || !is_array($_SESSION['user']) || !isset($_SESSION['user']['id']) || !isset($_SESSION['user']['role'])) {
    // If not properly logged in, redirect to login page
    header("Location: ../auth/login.php");
    exit();
}

// --- IMPORTANT: Ensure database connection is established and OPEN ---
// This file assumes db_connect.php defines a global $conn variable.
// We only include it if $conn is not already set, to prevent re-including.
if (!isset($conn)) {
    require_once __DIR__ . '/../config/db_connect.php';
}

$user_id = $_SESSION['user']['id'];

// Prepare a statement to fetch all relevant user data
// This ensures $_SESSION['user'] is always up-to-date with the latest DB info (e.g., is_onboarding_completed)
$stmt = $conn->prepare("SELECT id, name, email, role, course, department, profile_img, student_id, is_onboarding_completed FROM users WHERE id = ?");

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Fetch the user data as an associative array
        $fresh_user_data = $result->fetch_assoc();

        // Overwrite the existing session user data with fresh data from DB
        $_SESSION['user'] = $fresh_user_data;

    } else {
        // User ID from session not found in DB (e.g., user deleted) or multiple entries (error)
        // Invalidate session and redirect to login
        session_unset();
        session_destroy();
        header("Location: ../auth/login.php");
        exit();
    }
    $stmt->close();
} else {
    // Database statement preparation failed (e.g., column name error, syntax error)
    error_log("Auth Check DB Error (Prepare Statement): " . $conn->error);
    // For security, log out the user on critical DB errors
    session_unset();
    session_destroy();
    header("Location: ../auth/logout.php");
    exit();
}

// DO NOT CLOSE $conn HERE! It needs to remain open for other parts of the page.
// The connection will be closed at the end of the entire page request (e.g., in footer.php).
?>