<?php
// CHRONONAV_WEB_DOSS/templates/user/header_user.php
// This file assumes $user (session data), $page_title, and $current_page are set in the including script.

// Initialize variables to avoid 'Undefined variable' warnings if not set by the including page
$display_user_name = htmlspecialchars($display_username ?? ($user['name'] ?? 'Student User'));
$user_role = htmlspecialchars(ucfirst($display_user_role ?? ($user['role'] ?? 'user')));

$default_profile_pic_path = '../../uploads/profiles/default-avatar.png'; // Path to a generic default avatar
$profile_pic_src = $default_profile_pic_path; // Default to generic avatar

// The getProfileDropdownData function (from the main page like dashboard or view_profile)
// should set $profile_img_src, $display_username, and $display_user_role.
// If those are not set, fallback to session data or generic defaults.
if (isset($profile_img_src) && !empty($profile_img_src)) {
    $profile_pic_src = $profile_img_src; // Use the path derived from getProfileDropdownData
} else if (isset($user) && is_array($user) && !empty($user['profile_img'])) {
    // Fallback to session data if getProfileDropdownData wasn't used or didn't provide it
    $user_profile_pic_filename = $user['profile_img'];
    $potential_profile_pic_path = '../../' . $user_profile_pic_filename; // Adjust path for header context

    if (file_exists($potential_profile_pic_path) && $user_profile_pic_filename !== 'uploads/profiles/default-avatar.png') {
        $profile_pic_src = $potential_profile_pic_path;
    }
    // Else, it remains default_profile_pic_path
}

// Path to your ChronoNav logos for the navbar and dropdown
$chrononav_main_logo_path = '../../assets/img/chrononav_logo.jpg'; // Main logo for navbar brand
// Ensure this path is correct and the image exists!
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'ChronoNav - Student' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/user_css/header_user.css">
    <link rel="stylesheet" href="../../assets/css/user_css/dark_mode.css">

    <script>
        // Check localStorage for dark mode preference and apply immediately
        if (localStorage.getItem('darkMode') === 'enabled') {
            document.body.classList.add('dark-mode');
        }
    </script>
</head>
<body>
    <script src="../../assets/js/dark_mode.js" defer></script>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="../../pages/user/dashboard.php">
                <img src="<?= $chrononav_main_logo_path ?>" alt="" height="30" class="d-inline-block align-text-top me-2">
                ChronoNav
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" href="../../pages/user/settings.php" title="Settings">
                            <span class="navbar-icon-circle me-2"> <i class="fas fa-cog"></i> </span>
                            <span class="d-lg-none">Settings</span>
                        </a>
                    </li>
                    
                    <li class="nav-item dropdown navbar-profile-dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdownMenuLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?= $profile_pic_src ?>" alt="Profile Picture" class="navbar-profile-img me-2">
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownMenuLink">
                            <li><h6 class="dropdown-header"><?= $display_user_name ?> (<?= $user_role ?>)</h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../../pages/user/view_profile.php"><i class="fas fa-user-circle me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../../pages/user/support_center.php"><i class="fas fa-user-circle me-2"></i>Support and Ask question</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../../pages/user/announcements.php"><i class="fas fa-user-circle me-2"></i>Campus Announcement</a></li>
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
