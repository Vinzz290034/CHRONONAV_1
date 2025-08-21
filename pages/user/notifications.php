<?php
// CHRONONAV_WEB_DOSS/pages/user/notifications.php
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Assuming you have a function to get the current user's ID
$current_user_id = getCurrentUserId();

$page_title = "My Notifications";

$notifications = [];
$sql = "SELECT id, message, link, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
}
$stmt->close();

// Mark notifications as read when the user views them
$update_sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param('i', $current_user_id);
$update_stmt->execute();
$update_stmt->close();

$conn->close();
?>

<title><?php echo $page_title; ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <div class="container mt-4">
        <h2><?php echo $page_title; ?></h2>
        <?php if (empty($notifications)): ?>
            <div class="alert alert-info">You have no new notifications.</div>
        <?php else: ?>
            <ul class="list-group">
                <?php foreach ($notifications as $notification): ?>
                    <li class="list-group-item <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <a href="<?php echo htmlspecialchars($notification['link'] ?? '#'); ?>" class="text-decoration-none text-body">
                                    <div><?php echo htmlspecialchars($notification['message']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($notification['created_at']); ?></small>
                                </a>
                            </div>
                            <?php if (!$notification['is_read']): ?>
                                <a href="../../actions/user/mark_notification_as_read.php?id=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    Mark as Read
                                </a>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>