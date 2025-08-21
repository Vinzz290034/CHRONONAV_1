<?php
// CHRONONAV_WEB_UNO/pages/faculty/view_assigned_rooms.php

require_once __DIR__ . '/../../middleware/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole(['faculty', 'admin']); // Accessible by both faculty and admin

$currentUserId = $_SESSION['user']['id'];
$currentUserRole = $_SESSION['user']['role'];

// $conn is available globally
$assignedRooms = [];
$errorMessage = '';

try {
    if ($currentUserRole === 'faculty') {
        // Fetch rooms assigned to classes taught by the current faculty
        // Note: This query assumes 'day_of_week', 'start_time', 'end_time' are in the 'classes' table.
        $stmt = $conn->prepare("
            SELECT DISTINCT r.id AS room_id, r.room_name, r.capacity, c.class_name, c.class_code, c.day_of_week, c.start_time, c.end_time, c.semester
            FROM rooms r
            JOIN classes c ON r.id = c.room_id
            WHERE c.faculty_id = ?
            ORDER BY r.room_name, c.class_name
        ");
        $stmt->bind_param("i", $currentUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        $assignedRooms = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } elseif ($currentUserRole === 'admin') {
        // Admin can view all assigned rooms and their corresponding classes/faculty
        $stmt = $conn->prepare("
            SELECT r.id AS room_id, r.room_name, r.capacity, c.class_name, c.class_code, u.name AS faculty_name, c.semester, c.day_of_week, c.start_time, c.end_time
            FROM rooms r
            LEFT JOIN classes c ON r.id = c.room_id
            LEFT JOIN users u ON c.faculty_id = u.id
            ORDER BY r.room_name, c.class_name
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $assignedRooms = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
} catch (mysqli_sql_exception $e) {
    error_log("Database error fetching assigned rooms in view_assigned_rooms.php: " . $e->getMessage());
    $errorMessage = "Could not retrieve assigned rooms. Please try again later.";
    $assignedRooms = [];
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="container mt-4">
    <h2><?php echo ($currentUserRole === 'faculty') ? 'My Assigned Rooms' : 'All Assigned Rooms'; ?></h2>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
    <?php elseif (empty($assignedRooms)): ?>
        <div class="alert alert-info">No assigned rooms found.</div>
    <?php else: ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Room Name</th>
                    <th>Capacity</th>
                    <th>Assigned Class</th>
                    <?php if ($currentUserRole === 'admin'): ?>
                    <th>Faculty</th>
                    <th>Semester</th>
                    <?php endif; ?>
                    <th>Schedule</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assignedRooms as $room): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($room['room_name']); ?></td>
                        <td><?php echo htmlspecialchars($room['capacity'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($room['class_name'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($room['class_code'] ?? ''); ?>)</td>
                        <?php if ($currentUserRole === 'admin'): ?>
                        <td><?php echo htmlspecialchars($room['faculty_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($room['semester'] ?? 'N/A'); ?></td>
                        <?php endif; ?>
                        <td>
                            <?php if (!empty($room['day_of_week']) && !empty($room['start_time']) && !empty($room['end_time'])): ?>
                                <?php echo htmlspecialchars($room['day_of_week'] . ' ' . date("h:i A", strtotime($room['start_time'])) . ' - ' . date("h:i A", strtotime($room['end_time']))); ?>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
include __DIR__ . '/../../templates/footer.php';
?>