<?php
// CHRONONAV_WEBZ/middleware/auth_check_faculty.php
session_start();
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

// Specific check for faculty role (or admin if they can also view faculty profiles)
if ($_SESSION['user']['role'] !== 'faculty' && $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../index.php'); // Redirect to a dashboard or error page
    exit();
}
?>