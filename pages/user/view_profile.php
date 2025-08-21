<?php
 // CHRONONAV_WEB_DOSS/pages/user/view_profile.php

 // Start the session at the very beginning of the script
 session_start();

 // Include necessary files
 require_once '../../middleware/auth_check.php';
 require_once '../../config/db_connect.php';
 require_once '../../includes/functions.php';

 // Check if the user is logged in and has the correct role
 requireRole(['user']);

 $user_id = $_SESSION['user']['id'];

 // Fetch the user's complete profile data from the database
 $stmt = $conn->prepare("SELECT name, email, role, department, student_id, profile_img FROM users WHERE id = ?");
 $user_data_full = [];

 if ($stmt) {
     $stmt->bind_param("i", $user_id);
     $stmt->execute();
     $result = $stmt->get_result();

     if ($result->num_rows > 0) {
         $user_data_full = $result->fetch_assoc();
         // Update session data with the fresh info from the database
         $_SESSION['user'] = array_merge($_SESSION['user'], $user_data_full);
     } else {
         // Handle case where user is not found in the database
         session_destroy();
         header('Location: ../../auth/login.php?error=user_not_found');
         exit();
     }
     $stmt->close();
 } else {
     // Log a database connection error
     error_log("Database query preparation failed for view_profile (user): " . $conn->error);
 }

 // Get the user data from the session for display
 $user = $_SESSION['user'];

 // Prepare variables for display, ensuring they are safe for HTML output
 $display_name = htmlspecialchars($user['name'] ?? 'N/A');
 $display_email = htmlspecialchars($user['email'] ?? 'N/A');
 $display_role = htmlspecialchars(ucfirst($user['role'] ?? 'N/A'));
 $display_department = htmlspecialchars($user['department'] ?? 'N/A');
 $display_student_id = htmlspecialchars($user['student_id'] ?? 'N/A');

 // Determine the correct path for the profile image
 $profile_img = $user['profile_img'] ?? 'uploads/profiles/default-avatar.png';
 $profile_img_src = (strpos($profile_img, 'uploads/') === 0) ? '../../' . htmlspecialchars($profile_img) : htmlspecialchars($profile_img);

 // Variables for page header and sidebar
 $page_title = "My Profile";
 $current_page = "profile";

 // Get and clear any session messages
 $message = $_SESSION['message'] ?? '';
 $message_type = $_SESSION['message_type'] ?? '';
 unset($_SESSION['message']);
 unset($_SESSION['message_type']);

 ?>
 <?php require_once '../../templates/user/header_user.php'; ?>
 <?php require_once '../../templates/user/sidenav_user.php'; ?>

 <link rel="stylesheet" href="../../assets/css/user_css/user_view_profiles.css">

 <div class="main-content-wrapper">
     <div class="main-dashboard-content">
         <div class="profile-container card">
             <?php if ($message): ?>
                 <div class="alert alert-<?= $message_type ?> alert-dismissible fade show m-3" role="alert">
                     <?= htmlspecialchars($message) ?>
                     <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                 </div>
             <?php endif; ?>

             <div class="profile-header text-center py-4">
                 <img src="<?= $profile_img_src ?>" alt="<?= $display_name ?>'s Profile Picture" class="profile-avatar mb-3">
                 <h4 class="mb-1"><?= $display_name ?></h4>
                 <button class="btn btn-light-grey btn-sm px-4 mt-2" data-bs-toggle="modal" data-bs-target="#editProfileModal">Edit</button>
             </div>

             <div class="profile-details-section p-4">
                 <h5 class="section-title mb-3 text-center">Profile Details</h5>
                 <div class="row g-3 profile-details-grid">
                     <div class="col-md-6">
                         <div class="detail-item">
                             <small class="text-muted">Full Name</small>
                             <p class="mb-0 profile-value"><?= $display_name ?></p>
                         </div>
                     </div>
                     <div class="col-md-6">
                         <div class="detail-item">
                             <small class="text-muted">Email</small>
                             <p class="mb-0 profile-value"><?= $display_email ?></p>
                         </div>
                     </div>
                     <?php if (($user['role'] ?? '') === 'user'): ?>
                         <div class="col-md-6">
                             <div class="detail-item">
                                 <small class="text-muted">Student ID</small>
                                 <p class="mb-0 profile-value"><?= $display_student_id ?></p>
                             </div>
                         </div>
                         <div class="col-md-6">
                             <div class="detail-item">
                                 <small class="text-muted">Department</small>
                                 <p class="mb-0 profile-value"><?= $display_department ?></p>
                             </div>
                         </div>
                     <?php endif; ?>
                     <div class="col-md-6">
                         <div class="detail-item">
                             <small class="text-muted">Role</small>
                             <p class="mb-0 profile-value"><?= $display_role ?></p>
                         </div>
                     </div>
                 </div>
             </div>

             <div class="app-preferences-section p-4 border-top">
                 <h5 class="section-title mb-3 text-center">App Preferences</h5>
                 <div class="list-group list-group-flush preferences-list-group">
                     <div class="list-group-item d-flex justify-content-between align-items-center">
                         Notifications
                         <div class="form-check form-switch">
                             <input class="form-check-input" type="checkbox" id="notificationsToggle" checked>
                         </div>
                     </div>
                     <div class="list-group-item d-flex justify-content-between align-items-center">
                         Accessibility Mode
                         <div class="form-check form-switch">
                             <input class="form-check-input" type="checkbox" id="accessibilityToggle">
                         </div>
                     </div>
                     <div class="list-group-item d-flex justify-content-between align-items-center">
                         Voice Navigation
                         <div class="form-check form-switch">
                             <input class="form-check-input" type="checkbox" id="voiceNavToggle">
                         </div>
                     </div>
                     <div class="list-group-item d-flex justify-content-between align-items-center">
                         Font Size
                         <span>Medium</span>
                     </div>
                     <div class="list-group-item d-flex justify-content-between align-items-center">
                         Theme
                         <span>Light</span>
                     </div>
                 </div>
             </div>

             <div class="account-management-section p-4 border-top">
                 <h5 class="section-title mb-3 text-center">Account Management</h5>
                 <div class="list-group list-group-flush management-list-group">
                     <a href="settings.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                         Account Setting <i class="fas fa-arrow-right"></i>
                     </a>
                     <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-bs-toggle="modal" data-bs-target="#feedbackModal">
                         Feedback & Suggestion <i class="fas fa-message"></i>
                     </button>
                     <a href="announcements.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                         Announcement <i class="fas fa-bullhorn"></i>
                     </a>
                 </div>
             </div>
         </div>

         <div class="app-version text-center text-muted mt-4">
             App Version 1.0.0 · © 2025 ChronoNav
         </div>
     </div>
 </div>

 <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
     <div class="modal-dialog">
         <div class="modal-content">
             <form action="../../includes/user_edit_profile_handler.php" method="POST" enctype="multipart/form-data">
                 <div class="modal-header">
                     <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                 </div>
                 <div class="modal-body">
                     <div class="text-center mb-3">
                         <img src="<?= $profile_img_src ?>" alt="Profile Picture" class="profile-avatar mb-2" id="profileImagePreview">
                         <label for="profile_img" class="form-label d-block text-primary">Change Profile Picture</label>
                         <input type="file" class="form-control" id="profile_img" name="profile_img" accept="image/*">
                     </div>
                     <div class="mb-3">
                         <label for="edit_name" class="form-label">Full Name</label>
                         <input type="text" class="form-control" id="edit_name" name="name" value="<?= $display_name ?>" required>
                     </div>
                     <div class="mb-3">
                         <label for="edit_email" class="form-label">Email address</label>
                         <input type="email" class="form-control" id="edit_email" name="email" value="<?= $display_email ?>" required>
                     </div>
                     <div class="mb-3">
                         <label for="edit_department" class="form-label">Department</label>
                         <input type="text" class="form-control" id="edit_department" name="department" value="<?= $display_department ?>">
                     </div>
                     <div class="mb-3">
                         <label for="edit_student_id" class="form-label">Student ID</label>
                         <input type="text" class="form-control" id="edit_student_id" name="student_id" value="<?= $display_student_id ?>">
                     </div>
                 </div>
                 <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                     <button type="submit" class="btn btn-primary">Save Changes</button>
                 </div>
             </form>
         </div>
     </div>
 </div>

<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../../includes/user_feedback_handler.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="feedbackModalLabel">Feedback & Suggestion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="feedback_type" class="form-label">Type</label>
                        <select class="form-select" id="feedback_type" name="feedback_type" required>
                            <option value="">Select type</option>
                            <option value="suggestion">Suggestion</option>
                            <option value="bug_report">Bug Report</option>
                            <option value="feature_request">Feature Request</option>
                            <option value="general_feedback">General Feedback</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="feedback_subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="feedback_subject" name="subject" required>
                    </div>
                    <div class="mb-3">
                        <label for="feedback_message" class="form-label">Message</label>
                        <textarea class="form-control" id="feedback_message" name="message" rows="5" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="feedback_rating" class="form-label">Rating (1-5) <span class="text-muted">(Optional)</span></label>
                        <select class="form-select" id="feedback_rating" name="rating">
                            <option value="">Optional</option>
                            <option value="1">1 - Poor</option>
                            <option value="2">2 - Fair</option>
                            <option value="3">3 - Average</option>
                            <option value="4">4 - Good</option>
                            <option value="5">5 - Excellent</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

 <script>
     // Script to show a preview of the new profile image
     document.getElementById('profile_img').addEventListener('change', function(e) {
         const [file] = e.target.files;
         if (file) {
             document.getElementById('profileImagePreview').src = URL.createObjectURL(file);
         }
     });

     // Populate modal with current data when it's shown
     const editProfileModal = document.getElementById('editProfileModal');
     editProfileModal.addEventListener('show.bs.modal', function(event) {
         const nameInput = document.getElementById('edit_name');
         const emailInput = document.getElementById('edit_email');
         const departmentInput = document.getElementById('edit_department');
         const studentIdInput = document.getElementById('edit_student_id');
         const profileImagePreview = document.getElementById('profileImagePreview');

         nameInput.value = "<?= $display_name ?>";
         emailInput.value = "<?= $display_email ?>";
         departmentInput.value = "<?= $display_department ?>";
         studentIdInput.value = "<?= $display_student_id ?>";
         profileImagePreview.src = "<?= $profile_img_src ?>";
     });
 </script>

 <?php require_once '../../templates/footer.php'; ?>