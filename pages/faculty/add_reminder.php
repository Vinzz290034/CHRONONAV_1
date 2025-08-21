<?php
// pages/user/add_reminder.php
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php'; // Assuming requireRole function is here

// Ensure only users can access this page
requireRole(['faculty']); // Use the requireRole function to restrict access to 'user' role only

$user = $_SESSION['user']; // Get basic user data from session
$user_id = $user['id'];

// --- Fetch fresh user data for display in header and profile sections ---
// This is crucial for the profile picture and name in the header dropdown
$stmt_user_data = $conn->prepare("SELECT name, email, profile_img, role FROM users WHERE id = ?");
if ($stmt_user_data) {
    $stmt_user_data->bind_param("i", $user_id);
    $stmt_user_data->execute();
    $result_user_data = $stmt_user_data->get_result();
    if ($result_user_data->num_rows > 0) {
        $user_from_db = $result_user_data->fetch_assoc();
        $_SESSION['user'] = array_merge($_SESSION['user'], $user_from_db); // Update session with fresh data
        $user = $_SESSION['user']; // Use the updated $user array for display
    } else {
        // Handle case where user might have been deleted from DB but session persists
        error_log("Security Alert: User ID {$user_id} in session not found in database for add_reminder (faculty).");
        session_destroy();
        header('Location: ../../auth/login.php?error=user_not_found');
        exit();
    }
    $stmt_user_data->close();
} else {
    error_log("Database query preparation failed for add_reminder (faculty): " . $conn->error);
    // Optionally redirect or show a user-friendly error
}

// Prepare variables for header display
$display_username = htmlspecialchars($user['name'] ?? 'Guest');
$display_user_role = htmlspecialchars(ucfirst($user['role'] ?? 'faculty'));

// Determine the correct profile image source path for the header
$display_profile_img = htmlspecialchars($user['profile_img'] ?? 'uploads/profiles/default-avatar.png');
$profile_img_src = (strpos($display_profile_img, 'uploads/') === 0) ? '../../' . $display_profile_img : $display_profile_img;


$message = '';
$error = '';

$reminder_id = null;
$title = '';
$description = '';
$due_date = date('Y-m-d'); // Default to today's date for new reminders
$due_time = '';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $reminder_id = (int)$_GET['id'];
    $page_title = "Edit Reminder";

    $stmt = $conn->prepare("SELECT id, title, description, due_date, due_time FROM reminders WHERE id = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $reminder_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $reminder_data = $result->fetch_assoc();
            $title = htmlspecialchars($reminder_data['title']);
            $description = htmlspecialchars($reminder_data['description']);
            $due_date = htmlspecialchars($reminder_data['due_date']);
            $due_time = htmlspecialchars($reminder_data['due_time']);
        } else {
            $_SESSION['message'] = "Reminder not found or you don't have permission to edit it.";
            $_SESSION['message_type'] = "danger";
            header("Location: schedule.php");
            exit();
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Database error fetching reminder for edit: " . $conn->error;
        $_SESSION['message_type'] = "danger";
        header("Location: schedule.php");
        exit();
    }
} else {
    $page_title = "Add New Reminder";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reminder_id_post = $_POST['reminder_id'] ?? null;
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $due_date = $_POST['due_date'] ?? '';
    $due_time = $_POST['due_time'] ?? '';

    if (empty($title) || empty($due_date)) {
        $error = "Title and Due Date are required.";
        // Store error in session to display after redirect or on current page if not redirecting
        $_SESSION['message'] = $error;
        $_SESSION['message_type'] = "danger";
    } else {
        if ($reminder_id_post && is_numeric($reminder_id_post)) {
            $stmt = $conn->prepare("UPDATE reminders SET title = ?, description = ?, due_date = ?, due_time = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
            if ($stmt) {
                $stmt->bind_param("ssssii", $title, $description, $due_date, $due_time, $reminder_id_post, $user_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Reminder updated successfully!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $error = "Error updating reminder: " . $stmt->error;
                    $_SESSION['message_type'] = "danger";
                }
                $stmt->close();
            } else {
                $error = "Database preparation failed for update: " . $conn->error;
                $_SESSION['message_type'] = "danger";
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO reminders (user_id, title, description, due_date, due_time) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("issss", $user_id, $title, $description, $due_date, $due_time);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Reminder added successfully!";
                    $_SESSION['message_type'] = "success";
                    // Clear form fields on successful addition for new entry
                    $title = $description = '';
                    $due_date = date('Y-m-d');
                    $due_time = '';
                } else {
                    $error = "Error adding reminder: " . $stmt->error;
                    $_SESSION['message_type'] = "danger";
                }
                $stmt->close();
            } else {
                $error = "Database preparation failed for insert: " . $conn->error;
                $_SESSION['message_type'] = "danger";
            }
        }
        // Redirect after POST, regardless of success or failure, to prevent re-submission
        header("Location: schedule.php");
        exit();
    }
}

$current_page = "schedule"; // For active sidebar link in sidenav_user.php (since add/edit is part of schedule)

// Dynamic header/sidenav inclusion based on role
$header_path = '../../templates/faculty/header_faculty.php'; // Default for user
$sidenav_path = '../../templates/faculty/sidenav_faculty.php'; // Default for user

if (isset($user['role'])) {
    switch ($user['role']) {
        case 'admin':
            $header_path = '../../templates/admin/header_admin.php';
            $sidenav_path = '../../templates/admin/sidenav_admin.php';
            break;
        case 'faculty':
            $header_path = '../../templates/faculty/header_faculty.php';
            $sidenav_path = '../../templates/faculty/sidenav_faculty.php';
            break;
        // 'user' role uses the defaults
    }
}

require_once $header_path;
?>

<div class="d-flex" id="wrapper">
    <?php require_once $sidenav_path; ?>

    <div class="main-dashboard-content-wrapper" id="page-content-wrapper">
        <div class="main-dashboard-content">
            <link rel="stylesheet" href="../../assets/css/faculty_css/add_reminder.css">

            <?php
            // Display session messages (e.g., from successful update/add redirects)
            if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?= htmlspecialchars($_SESSION['message_type']) ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php
                unset($_SESSION['message']); // Clear message after displaying
                unset($_SESSION['message_type']);
            endif;
            ?>
            <?php if (!empty($error)): // Display current page errors ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card reminder-form-card p-4">
                <h2 class="form-title text-center mb-4"><?= htmlspecialchars($page_title) ?></h2>
                <form method="POST">
                    <?php if ($reminder_id): ?>
                        <input type="hidden" name="reminder_id" value="<?= htmlspecialchars($reminder_id) ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="title" class="form-label">Reminder Title</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($title) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($description) ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" value="<?= htmlspecialchars($due_date) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="due_time" class="form-label">Due Time (Optional)</label>
                            <input type="time" class="form-control" id="due_time" name="due_time" value="<?= htmlspecialchars($due_time) ?>">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        <a href="schedule.php" class="btn btn-secondary me-3">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <?= ($reminder_id) ? 'Update Reminder' : 'Add Reminder' ?>
                        </button>
                    </div>
                </form>
            </div>

            <div class="app-version text-center text-muted mt-4">
                App Version 1.0.0 · © 2023 ChronoNav
            </div>

        </div>
    </div>
</div>

<?php
// Ensure this is the last include in your main page file
require_once '../../templates/footer.php';
?>

<style>

</style>