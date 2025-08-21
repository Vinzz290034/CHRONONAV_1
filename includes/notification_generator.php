<?php
// CHRONONAV_WEB_DOSS/includes/notification_generator.php

function generateNotification($conn, $user_id, $message, $link = null) {
    // Check if the user has in-app notifications enabled
    $sql_pref = "SELECT in_app_notifications FROM notification_preferences WHERE user_id = ?";
    $stmt_pref = $conn->prepare($sql_pref);
    $stmt_pref->bind_param('i', $user_id);
    $stmt_pref->execute();
    $result_pref = $stmt_pref->get_result();
    
    $in_app_enabled = true;
    if ($result_pref->num_rows > 0) {
        $prefs = $result_pref->fetch_assoc();
        $in_app_enabled = (bool)$prefs['in_app_notifications'];
    }
    $stmt_pref->close();

    if (!$in_app_enabled) {
        return false; // Do not generate in-app notification if user has disabled it
    }

    $user_id = $conn->real_escape_string($user_id);
    $message = $conn->real_escape_string($message);
    $link = $conn->real_escape_string($link);

    $sql = "INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        error_log("Error preparing notification insertion: " . $conn->error);
        return false;
    }

    $stmt->bind_param('iss', $user_id, $message, $link);
    $success = $stmt->execute();
    $stmt->close();
    
    if (!$success) {
        error_log("Error executing notification insertion: " . $stmt->error);
    }

    return $success;
}

// You can add other functions here for sending email/SMS notifications based on preferences
function sendEmailNotification($conn, $user_id, $message) {
    // Logic to get user's email from the 'users' table
    // Logic to send email using a library like PHPMailer
}

function sendSmsNotification($conn, $user_id, $message) {
    // Logic to get user's phone number
    // Logic to send SMS using a service like Twilio
}
?>