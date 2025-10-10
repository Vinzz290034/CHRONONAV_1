<?php
// CHRONONAV_WEB_DOSS/templates/faculty/header_faculty.php
// This file assumes $user (session data), $page_title, and $current_page are set in the including script.

// Initialize variables to avoid 'Undefined variable' warnings if not set by the including page
$display_user_name = htmlspecialchars($display_username ?? ($user['name'] ?? 'Faculty Member'));
$user_role = htmlspecialchars(ucfirst($display_user_role ?? ($user['role'] ?? 'faculty')));

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
$chrononav_dropdown_logo_path = '../../assets/images/chrononav_logo_small.png'; // Small logo for dropdown header
// Ensure these paths are correct and images exist!
?>

<?php
// CHRONONAV_WEB_DOSS/templates/faculty/header_faculty.php
// This file assumes $user (session data), $page_title, and $current_page are set in the including script.

// ... (existing PHP code) ...
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= $page_title ?? 'ChronoNav - Faculty' ?>
    </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Favicon -->
    <link rel=" icon" type="image/x-icon"
        href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png">

    <!-- <link rel="stylesheet" href="../../assets/css/faculty_css/header_faculty.css"> -->

    <script>
        // Check localStorage for dark mode preference and apply immediately
        if (localStorage.getItem('darkMode') === 'enabled') {
            document.body.classList.add('dark-mode');
        }
    </script>
</head>

<body>
    <style>
        .custom-header {
            position: sticky;
        }

        /* Additional styles from the first design - Matching user header exactly */
        .custom-header {
            font-family: "Space Grotesk", "Noto Sans", sans-serif;
            background-color: #fff;
            border-bottom: 1px solid #eaedf1;
            padding: 0.75rem 2.5rem;
            position: sticky;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .custom-header {
            position: sticky;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .settings-btn {
            background-color: #eaedf1;
            border: none;
            border-radius: 50%;
            color: #101518;
            width: 40px;
            height: 40px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .settings-btn:hover {
            background-color: #d8dce1;
            color: #101518;
        }

        /* Profile dropdown specific styles */
        .navbar-profile-dropdown .nav-link {
            padding: 0;
            border: none;
            background: transparent;
        }

        .navbar-profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid transparent;
            transition: border-color 0.2s ease;
        }

        .navbar-profile-dropdown .nav-link:hover .navbar-profile-img {
            border-color: #eaedf1;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo-icon {
            width: 1.5rem;
            height: 1.5rem;
            color: #101518;
        }

        .logo-text {
            font-weight: 700;
            font-size: 1.25rem;
            color: #101518;
            margin-bottom: 0;
            text-decoration: none;
        }

        .logo-text:hover {
            color: #101518;
        }

        .header-right-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* Dropdown menu styling */
        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 0.75rem;
            padding: 0.5rem 0;
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
        }

        .dropdown-item i {
            width: 20px;
            text-align: center;
            margin-right: 0.5rem;
        }

        /* Dark mode adjustments */
        .dark-mode .custom-header {
            background-color: #121212;
            border-bottom-color: #2d3748;
        }

        .dark-mode .logo-text {
            color: #f8f9fa;
        }

        .dark-mode .logo-icon {
            color: #f8f9fa;
        }

        .dark-mode .settings-btn {
            background-color: #2d3748;
            color: #f8f9fa;
        }

        .dark-mode .settings-btn:hover {
            background-color: #3a4556;
            color: #f8f9fa;
        }

        .dark-mode .navbar-profile-dropdown .nav-link:hover .navbar-profile-img {
            border-color: #4a5568;
        }

        .dark-mode .dropdown-menu {
            background-color: #2d3748;
            border: 1px solid #4a5568;
        }

        .dark-mode .dropdown-item {
            color: #f8f9fa;
        }

        .dark-mode .dropdown-item:hover {
            background-color: #4a5568;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .custom-header {
                padding-left: 1rem;
                padding-right: 1rem;
            }

            .header-right-section {
                gap: 0.75rem;
            }
        }

        @media (max-width: 576px) {
            .header-right-section {
                gap: 0.5rem;
            }
        }
    </style>

    <script src="../../assets/js/dark_mode.js" defer></script>

    <!-- Header section using <header> tag -->
    <header class="custom-header shadow-sm">
        <!-- Logo Section -->
        <a href="../../pages/faculty/dashboard.php"
            class="d-flex align-items-center gap-2 link-offset-2 link-underline link-underline-opacity-0">
            <div class="d-flex align-items-center gap-2">
                <div style="width: 2.5rem; height: 2.5rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100%" height="100%">
                        <image
                            href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png"
                            x="0" y="0" width="100" height="100" />
                    </svg>
                </div>
                <h2 class="mb-0 text-black-50 fw-bold fs-5">ChronoNav</h2>
            </div>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Right Section with Settings and Profile -->
        <div class="header-right-section">

            <!-- Settings Button -->
            <a href="../../pages/faculty/settings.php" class="settings-btn rounded-3" title="Settings">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                    viewBox="0 0 256 256">
                    <path
                        d="M128,80a48,48,0,1,0,48,48A48.05,48.05,0,0,0,128,80Zm0,80a32,32,0,1,1,32-32A32,32,0,0,1,128,160Zm88-29.84q.06-2.16,0-4.32l14.92-18.64a8,8,0,0,0,1.48-7.06,107.21,107.21,0,0,0-10.88-26.25,8,8,0,0,0-6-3.93l-23.72-2.64q-1.48-1.56-3-3L186,40.54a8,8,0,0,0-3.94-6,107.71,107.71,0,0,0-26.25-10.87,8,8,0,0,0-7.06,1.49L130.16,40Q128,40,125.84,40L107.2,25.11a8,8,0,0,0-7.06-1.48A107.6,107.6,0,0,0,73.89,34.51a8,8,0,0,0-3.93,6L67.32,64.27q-1.56,1.49-3,3L40.54,70a8,8,0,0,0-6,3.94,107.71,107.71,0,0,0-10.87,26.25,8,8,0,0,0,1.49,7.06L40,125.84Q40,128,40,130.16L25.11,148.8a8,8,0,0,0-1.48,7.06,107.21,107.21,0,0,0,10.88,26.25,8,8,0,0,0,6,3.93l23.72,2.64q1.49,1.56,3,3L70,215.46a8,8,0,0,0,3.94,6,107.71,107.71,0,0,0,26.25,10.87,8,8,0,0,0,7.06-1.49L125.84,216q2.16.06,4.32,0l18.64,14.92a8,8,0,0,0,7.06,1.48,107.21,107.21,0,0,0,26.25-10.88,8,8,0,0,0,3.93-6l2.64-23.72q1.56-1.48,3-3L215.46,186a8,8,0,0,0,6-3.94,107.71,107.71,0,0,0,10.87-26.25,8,8,0,0,0-1.49-7.06Zm-16.1-6.5a73.93,73.93,0,0,1,0,8.68,8,8,0,0,0,1.74,5.48l14.19,17.73a91.57,91.57,0,0,1-6.23,15L187,173.11a8,8,0,0,0-5.1,2.64,74.11,74.11,0,0,1-6.14,6.14,8,8,0,0,0-2.64,5.1l-2.51,22.58a91.32,91.32,0,0,1-15,6.23l-17.74-14.19a8,8,0,0,0-5-1.75h-.48a73.93,73.93,0,0,1-8.68,0,8,8,0,0,0-5.48,1.74L100.45,215.8a91.57,91.57,0,0,1-15-6.23L82.89,187a8,8,0,0,0-2.64-5.1,74.11,74.11,0,0,1-6.14-6.14,8,8,0,0,0-5.1-2.64L46.43,170.6a91.32,91.32,0,0,1-6.23-15l14.19-17.74a8,8,0,0,0,1.74-5.48,73.93,73.93,0,0,1,0-8.68,8,8,0,0,0-1.74-5.48L40.2,100.45a91.57,91.57,0,0,1,6.23-15L69,82.89a8,8,0,0,0,5.1-2.64,74.11,74.11,0,0,1,6.14-6.14A8,8,0,0,0,82.89,69L85.4,46.43a91.32,91.32,0,0,1,15-6.23l17.74,14.19a8,8,0,0,0,5.48,1.74,73.93,73.93,0,0,1,8.68,0,8,8,0,0,0,5.48-1.74L155.55,40.2a91.57,91.57,0,0,1,15,6.23L173.11,69a8,8,0,0,0,2.64,5.1,74.11,74.11,0,0,1,6.14,6.14,8,8,0,0,0,5.1,2.64l22.58,2.51a91.32,91.32,0,0,1,6.23,15l-14.19,17.74A8,8,0,0,0,199.87,123.66Z" />
                </svg>
            </a>

            <!-- Profile Dropdown - Using the exact same structure as user header -->
            <li class="nav-item dropdown navbar-profile-dropdown" style="list-style: none;">
                <a class="nav-link align-items-center" href="#" id="navbarDropdownMenuLink" role="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="<?= $profile_pic_src ?>" alt="" class="navbar-profile-img"
                        style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;">
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownMenuLink">
                    <li>
                        <h6 class="dropdown-header">
                            <?= $display_user_name ?> (
                            <?= $user_role ?>)
                        </h6>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="../../pages/faculty/view_profile.php"><i
                                class="fas fa-user-circle me-2"></i>Profile</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="../../pages/faculty/support_center.php"><i
                                class="fas fa-user-circle me-2"></i>Support and Ask question</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="../../pages/faculty/announcements.php"><i
                                class="fas fa-user-circle me-2"></i>Campus Announcement</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="../../auth/logout.php"><i
                                class="fas fa-sign-out-alt me-2"></i>Logout</a>
                    </li>
                </ul>
            </li>
        </div>
    </header>

    <script src="../../assets/js/jquery.min.js"></script>
    <script src="../../assets/js/script.js"></script>
</body>

</html>