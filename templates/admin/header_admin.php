<?php
// CHRONONAV_WEB_DOSS/templates/admin/header_admin.php
// This file assumes $user (session data), $page_title, and $current_page are set in the including script.

// Initialize variables to avoid 'Undefined variable' warnings if not set by the including page
$display_user_name = htmlspecialchars($display_username ?? ($user['name'] ?? 'Admin User'));
$user_role = htmlspecialchars(ucfirst($display_user_role ?? ($user['role'] ?? 'admin')));

$default_profile_pic_path = '../../uploads/profiles/default-avatar.png'; // Path to a generic default avatar
$profile_pic_src = $default_profile_pic_path; // Default to generic avatar

// Handle profile image from session or function like getProfileDropdownData
if (isset($profile_img_src) && !empty($profile_img_src)) {
    $profile_pic_src = $profile_img_src;
} else if (isset($user) && is_array($user) && !empty($user['profile_img'])) {
    $user_profile_pic_filename = $user['profile_img'];
    $potential_profile_pic_path = '../../' . $user_profile_pic_filename;

    if (file_exists($potential_profile_pic_path) && $user_profile_pic_filename !== 'uploads/profiles/default-avatar.png') {
        $profile_pic_src = $potential_profile_pic_path;
    }
}

// Path to ChronoNav logos
$chrononav_main_logo_path = '../../assets/img/chrononav_logo.jpg';
$chrononav_dropdown_logo_path = '../../assets/images/chrononav_logo_small.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Dashboard') ?> - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin_css/dark_mode.css">
    <link rel="stylesheet" href="../../assets/css/admin_css/header_admin.css"> 
</head>
<body>
    <script src="../../assets/js/dark_mode.js" defer></script>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="../../pages/admin/dashboard.php">
                <img src="<?= $chrononav_main_logo_path ?>" alt="ChronoNav Logo" height="30" class="d-inline-block align-text-top me-2">
                Chrononav
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" href="../../pages/admin/settings.php" title="Settings">
                            <span class="navbar-icon-circle me-2"><i class="fas fa-cog"></i></span>
                            <span class="d-lg-none">Settings</span>
                        </a>
                    </li>
                    
                    <li class="nav-item dropdown navbar-profile-dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="" id="navbarDropdownMenuLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?= $profile_pic_src ?>" alt="Profile Picture" class="navbar-profile-img me-2">
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownMenuLink">
                            <li>
                                <h6 class="dropdown-header">
                                    <?= $display_user_name ?> (<?= $user_role ?>)
                                </h6>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../../pages/admin/view_profile.php"><i class="fas fa-user-circle me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <script src="../../assets/js/jquery.min.js"></script>
    <script src="../../assets/js/script.js"></script>
</body>
</html>
