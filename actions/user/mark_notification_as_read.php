<?php
// CHRONONAV_WEB_DOSS/actions/user/mark_notification_as_read.php
require_once '../../config/db_connect.php';
require_once '../../middleware/auth_check.php';
require_once '../../includes/functions.php';

// Check if a notification ID was provided in the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_message('danger', 'Invalid notification ID.');
    header('Location: ../../pages/user/notifications.php');
    exit;
}

$notification_id = $_GET['id'];
$current_user_id = getCurrentUserId();

// Prepare the SQL statement to update the notification
// Crucially, it checks if the user_id matches to prevent a user from
// marking another user's notifications as read.
$sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    set_message('danger', 'Error preparing the database query.');
    header('Location: ../../pages/user/notifications.php');
    exit;
}

$stmt->bind_param('ii', $notification_id, $current_user_id);
$stmt->execute();
$stmt->close();
$conn->close();

set_message('success', 'Notification marked as read.');
header('Location: ../../pages/user/notifications.php');
exit;
?>