<?php
// Include the auth_check middleware first
require_once __DIR__ . '/../../middleware/auth_check.php';
// Include the function for role checking
require_once __DIR__ . '/../../includes/functions.php'; // Assuming requireRole is here

// Restrict access to faculty and admin
requireRole(['faculty', 'admin']);

// User ID and role are now guaranteed to be in $_SESSION['user']
$currentUserId = $_SESSION['user']['id'];
$currentUserRole = $_SESSION['user']['role'];

// $conn is available globally from db_connect.php via auth_check.php
$classes = [];
$errorMessage = '';

try {
    if ($currentUserRole === 'faculty') {
        // Fetch classes assigned to the current faculty
        $stmt = $conn->prepare("
            SELECT c.class_id, c.class_name, c.class_code, r.room_name, c.semester, c.day_of_week, c.start_time, c.end_time
            FROM classes c
            LEFT JOIN rooms r ON c.room_id = r.room_id
            WHERE c.faculty_id = ?
            ORDER BY c.class_name
        ");
        $stmt->bind_param("i", $currentUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        $classes = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } elseif ($currentUserRole === 'admin') {
        // Admin can view all classes
        $stmt = $conn->prepare("
            SELECT c.class_id, c.class_name, c.class_code, r.room_name, u.name AS faculty_name, c.semester, c.day_of_week, c.start_time, c.end_time
            FROM classes c
            LEFT JOIN rooms r ON c.room_id = r.room_id
            LEFT JOIN users u ON c.faculty_id = u.id
            ORDER BY c.class_name
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $classes = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
} catch (mysqli_sql_exception $e) {
    error_log("Database error fetching classes: " . $e->getMessage());
    $errorMessage = "Could not retrieve class lists. Please try again later.";
    $classes = [];
}

// Include header (adjust path)
include __DIR__ . '/../../templates/header.php';
?>
<div class="container mt-4">
    <h2><?php echo ($currentUserRole === 'faculty') ? 'My Class Lists' : 'All Class Lists'; ?></h2>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
    <?php elseif (empty($classes)): ?>
        <div class="alert alert-info">No classes found.</div>
    <?php else: ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Class Code</th>
                    <th>Class Name</th>
                    <?php if ($currentUserRole === 'admin'): ?>
                    <th>Faculty</th>
                    <?php endif; ?>
                    <th>Assigned Room</th>
                    <th>Schedule</th>
                    <th>Semester</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classes as $class): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($class['class_code']); ?></td>
                        <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                        <?php if ($currentUserRole === 'admin'): ?>
                        <td><?php echo htmlspecialchars($class['faculty_name'] ?? 'N/A'); ?></td>
                        <?php endif; ?>
                        <td><?php echo htmlspecialchars($class['room_name'] ?? 'Not Assigned'); ?></td>
                        <td>
                            <?php if (!empty($class['day_of_week'])): ?>
                                <?php echo htmlspecialchars($class['day_of_week'] . ' ' . date("h:i A", strtotime($class['start_time'])) . ' - ' . date("h:i A", strtotime($class['end_time']))); ?>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($class['semester'] ?? 'N/A'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
// Include footer (adjust path)
include __DIR__ . '/../../templates/footer.php';
// The $conn connection is typically closed in footer.php or at script end if no global variable
// Example: if (isset($conn) && $conn) { $conn->close(); }
?>