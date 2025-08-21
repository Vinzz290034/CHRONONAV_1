<?php
// CHRONONAV_WEBZ/pages/user/clear_cache.php
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';

// Check if the user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

$user_id = $_SESSION['user']['id'];
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_cache_confirm'])) {
    // --- CONCEPTUAL: Implement actual cache clearing logic here ---
    // This is highly dependent on how your "cache" is implemented.
    // Examples:

    // 1. If you store user-specific temporary data in session (e.g., last search, filters):
    //    unset($_SESSION['user_search_history']);
    //    unset($_SESSION['last_viewed_event_id']);

    // 2. If you have a simple file-based cache for user data:
    //    $cache_file = '../../cache/user_' . $user_id . '_temp_data.json';
    //    if (file_exists($cache_file)) {
    //        unlink($cache_file);
    //    }

    // 3. For a more robust caching system (like Memcached/Redis), you'd interact with it:
    //    $memcached->delete('user_dashboard_data_' . $user_id);
    //    $redis->del('user:preferences:' . $user_id);

    // For this example, let's just simulate the action.
    // In a real application, replace this with actual cache invalidation.
    // For now, we'll assume a "success" state.
    $message = 'Temporary cached data has been cleared.';
    $message_type = 'success';
    // You might also force a redirect back to profile to reflect changes, or reload data.
    // header('Location: profile.php?message=' . urlencode($message) . '&type=' . $message_type);
    // exit();

}

$page_title = "Clear Cached Data";
$current_page = "profile"; // Keep profile as active in sidenav
?>

<?php require_once '../../templates/header.php'; ?>
<?php require_once '../../templates/sidenav.php'; ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/admin_css/clear_cache.css">

<div class="main-dashboard-content">
    <div class="card p-4 clear-cache-card">
        <div class="text-center mb-4">
            <i class="fas fa-redo-alt text-info" style="font-size: 3rem;"></i>
            <h4 class="mt-3">Clear Temporary Data</h4>
            <p class="text-muted">This action will remove temporary data stored by the application to improve performance. It might require you to re-fetch some content.</p>
            <p class="fw-bold">Your personal information will NOT be affected.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>" role="alert">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if (empty($message) || $message_type === 'danger'): // Only show form if no success message or if there was an error ?>
        <form action="clear_cache.php" method="POST">
            <input type="hidden" name="clear_cache_confirm" value="1">
            <button type="submit" class="btn btn-primary btn-block">Clear Data Now</button>
            <a href="view_profile.php" class="btn btn-secondary btn-block mt-3">Cancel</a>
        </form>
        <?php else: ?>
            <div class="d-grid gap-2">
                <a href="view_profile.php" class="btn btn-primary">Back to Profile</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>


  