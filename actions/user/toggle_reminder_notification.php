<?php
// actions/user/toggle_reminder_notification.php (Conceptual File)

// Ensure $conn is available and is a mysqli object
/** @var \mysqli $conn */ 

require_once '../../config/db_connect.php'; 
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reminder_id'], $_POST['is_active'])) {
    $user_id = (int)($_SESSION['user']['id'] ?? 0);
    $reminder_id = (int)$_POST['reminder_id'];
    $is_active = (int)$_POST['is_active']; // 1 or 0
    
    if ($user_id === 0 || $reminder_id === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid session or reminder ID.']);
        exit;
    }

    try {
        // 1. Update the reminder's notification status (is_notified column assumed)
        $stmt = $conn->prepare("UPDATE reminders SET is_notified = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("iii", $is_active, $reminder_id, $user_id);
        $stmt->execute();
        $stmt->close();

        // 2. If activating notification, insert a permanent alert into the notifications table
        if ($is_active == 1) {
            // Fetch reminder details to generate a message
            $stmt = $conn->prepare("SELECT title FROM reminders WHERE id = ?");
            $stmt->bind_param("i", $reminder_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $reminder = $result->fetch_assoc();
            $stmt->close();
            
            if ($reminder) {
                $message = "🔔 You activated an alert for: " . $reminder['title'];
                $link = '../user/dashboard.php'; // Link to the dashboard/reminders section
                
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, link, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
                $stmt->bind_param("iss", $user_id, $message, $link);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        error_log("Notification toggle failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database operation failed.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method or parameters.']);
}
?>