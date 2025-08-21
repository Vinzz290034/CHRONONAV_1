<?php
// CHRONONAV_WEB_UNO/pages/faculty/delete_office_hours.php

require_once __DIR__ . '/../../middleware/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole(['faculty']); // Only faculty can delete their own office hours

$currentFacultyId = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['oh_id'])) {
    $ohIdToDelete = $_POST['oh_id'];

    try {
        // Prepare and execute the delete statement, ensuring faculty_id matches
        $stmt = $conn->prepare("DELETE FROM office_hours WHERE oh_id = ? AND faculty_id = ?");
        $stmt->bind_param("ii", $ohIdToDelete, $currentFacultyId);
        $stmt->execute();
        $stmt->close();

        if ($conn->affected_rows > 0) {
            $_SESSION['message'] = "Office hours deleted successfully.";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Office hours not found or you don't have permission to delete it.";
            $_SESSION['message_type'] = "danger";
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Database error deleting office hours in delete_office_hours.php: " . $e->getMessage());
        $_SESSION['message'] = "Error deleting office hours. Please try again.";
        $_SESSION['message_type'] = "danger";
    }
} else {
    $_SESSION['message'] = "Invalid request to delete office hours.";
    $_SESSION['message_type'] = "danger";
}

// Redirect back to the office hours page
header("Location: set_office_hours.php");
exit();
?>