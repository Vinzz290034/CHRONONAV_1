<?php
// CHRONONAV_WEBZD/includes/onboarding_module.php

// This file is designed to be included on any page where you want the onboarding
// module to be active, typically your main dashboard pages (user, faculty, admin).

// Ensure session is started and user is logged in
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    // If no user is logged in, there's no onboarding to manage.
    return;
}

require_once __DIR__ . '/../config/db_connect.php'; // Adjust path if your db_connect.php is elsewhere

$user_id = $_SESSION['user']['id'];
$user_role = $_SESSION['user']['role'];

// Fetch user's current onboarding status from the database
$onboarding_status_db = false;
$stmt = $conn->prepare("SELECT is_onboarding_completed FROM users WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($onboarding_status_db_value);
    $stmt->fetch();
    $stmt->close();
    $onboarding_status_db = (bool) $onboarding_status_db_value; // Convert to boolean
} else {
    error_log("ChronoNav Onboarding Error: Failed to prepare onboarding status statement: " . $conn->error);
}

// Determine if the "tour" should auto-show on page load.
// It will auto-show if the user has NOT completed onboarding yet.
$should_auto_show_tour = !$onboarding_status_db;

// This JavaScript variable will be used by the frontend script to decide whether to open the modal automatically.
echo "<script>const shouldAutoShowOnboardingTour = " . ($should_auto_show_tour ? 'true' : 'false') . ";</script>";

?>

<div class="modal fade" id="onboardingTourModal" tabindex="-1" aria-labelledby="onboardingTourModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="onboardingTourModalLabel">Welcome to ChronoNav!</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Step-by-Step Tour:</h6>
                <div id="onboardingCarousel" class="carousel slide" data-bs-interval="false">
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <h5>1. Your Dashboard Overview</h5>
                            <p>This is your central hub! Quickly see your upcoming schedules, reminders, and important announcements at a glance.</p>
                            </div>
                        <div class="carousel-item">
                            <h5>2. Managing Schedules & Classes</h5>
                            <p>Navigate to the 'Schedules' section in the sidebar. Here you can view your detailed class schedules, or for faculty/admin, manage course timings and room assignments.</p>
                            </div>
                        <div class="carousel-item">
                            <h5>3. Setting Personal Reminders</h5>
                            <p>Use the 'Reminders' module to set personal alerts for assignments, meetings, and any other important tasks. Stay organized and never miss a deadline!</p>
                             </div>
                        <div class="carousel-item">
                            <h5>4. Global Academic Calendar</h5>
                            <p>Check the 'Academic Calendar' for university-wide events, holidays, exam periods, and important deadlines. Plan your academic year effectively!</p>
                        </div>
                        <?php if ($user_role === 'admin' || $user_role === 'faculty'): ?>
                            <div class="carousel-item">
                                <h5>5. Admin/Faculty Exclusive Tools</h5>
                                <p>As an <?= ucfirst($user_role) ?>, you have privileged access to features like managing user accounts, allocating rooms, publishing announcements, and handling support tickets.</p>
                            </div>
                        <?php endif; ?>
                        <div class="carousel-item">
                            <h5>6. Feedback & Support</h5>
                            <p>We value your input! Use the 'Feedback' module to report bugs, suggest new features, or just share your general thoughts. For specific issues, create a 'Ticket' for our support team.</p>
                        </div>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#onboardingCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#onboardingCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="onboardingSkipTourBtn">Skip Tour</button>
                <button type="button" class="btn btn-primary" id="onboardingFinishTourBtn" style="display:none;">Finish Tour</button>
                 <button type="button" class="btn btn-primary" id="onboardingNextTourBtn">Next</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="onboardingTipsModal" tabindex="-1" aria-labelledby="onboardingTipsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="onboardingTipsModalLabel">Quick Tips for ChronoNav</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul>
                    <li>**Stay Organized:** Regularly check your Dashboard for upcoming events and schedules.</li>
                    <li>**Utilize Reminders:** Set reminders for assignments, meetings, or any personal tasks.</li>
                    <li>**Check Academic Calendar:** Always be aware of important university dates like holidays and exam periods.</li>
                    <li>**Provide Feedback:** Your suggestions help us improve! Use the Feedback module to share your thoughts.</li>
                    <li>**For Faculty/Admin:** Keep an eye on the Announcements for important campus-wide updates.</li>
                    <li>**Security:** Always log out when you're done, especially on shared computers.</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const onboardingTourModal = new bootstrap.Modal(document.getElementById('onboardingTourModal'), {
        backdrop: 'static', // Prevent closing by clicking outside
        keyboard: false // Prevent closing with ESC key
    });
    const onboardingTipsModal = new bootstrap.Modal(document.getElementById('onboardingTipsModal'));
    const onboardingCarousel = document.getElementById('onboardingCarousel'); // Get the carousel element

    const onboardingSkipTourBtn = document.getElementById('onboardingSkipTourBtn');
    const onboardingFinishTourBtn = document.getElementById('onboardingFinishTourBtn');
    const onboardingNextTourBtn = document.getElementById('onboardingNextTourBtn'); // New: Next button for carousel

    // Function to update onboarding status via AJAX
    function updateOnboardingStatus(status) {
        fetch('/CHRONONAV_WEBZD/api/update_onboarding_status.php', { // Adjust this path if your api folder is named differently
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'status=' + status
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Onboarding status updated to:', status);
                // Optionally, if status becomes 'completed' or 'skipped', you might
                // want to remove the restart button or refresh the page.
            } else {
                console.error('Failed to update onboarding status:', data.message);
                alert('Error updating onboarding status: ' + data.message); // User feedback
            }
        })
        .catch(error => {
            console.error('Error sending AJAX for onboarding status:', error);
            alert('Network error while updating onboarding status.'); // User feedback
        });
    }

    // Auto-show tour if needed (controlled by PHP variable `shouldAutoShowOnboardingTour`)
    if (shouldAutoShowOnboardingTour) {
        onboardingTourModal.show();
    }

    // Event listeners for tour buttons within the modal footer
    if (onboardingSkipTourBtn) {
        onboardingSkipTourBtn.addEventListener('click', function() {
            updateOnboardingStatus('skipped');
            onboardingTourModal.hide();
        });
    }

    if (onboardingFinishTourBtn) {
        onboardingFinishTourBtn.addEventListener('click', function() {
            updateOnboardingStatus('completed');
            onboardingTourModal.hide();
        });
    }

    if (onboardingNextTourBtn) {
        onboardingNextTourBtn.addEventListener('click', function() {
            // Manually advance the carousel to the next slide
            const carouselInstance = bootstrap.Carousel.getInstance(onboardingCarousel);
            if (carouselInstance) {
                carouselInstance.next();
            }
        });
    }

    // Carousel slide event listener to toggle "Next" and "Finish" buttons
    if (onboardingCarousel) {
        onboardingCarousel.addEventListener('slid.bs.carousel', function () {
            const activeItem = this.querySelector('.carousel-item.active');
            const isLastSlide = !activeItem.nextElementSibling; // Check if it's the last item

            if (isLastSlide) {
                onboardingNextTourBtn.style.display = 'none'; // Hide Next
                onboardingFinishTourBtn.style.display = 'inline-block'; // Show Finish
            } else {
                onboardingNextTourBtn.style.display = 'inline-block'; // Show Next
                onboardingFinishTourBtn.style.display = 'none'; // Hide Finish
            }
        });
        // Trigger the slide event once on load to set initial button state
        onboardingCarousel.dispatchEvent(new Event('slid.bs.carousel'));
    }


    // Event listeners for buttons you'll place on your dashboard/main page
    const viewTourBtn = document.getElementById('viewTourBtn');
    const viewTipsBtn = document.getElementById('viewTipsBtn');
    const restartOnboardingBtn = document.getElementById('restartOnboardingBtn');

    if (viewTourBtn) {
        viewTourBtn.addEventListener('click', function() {
            // Reset carousel to first slide before showing
            const carouselInstance = bootstrap.Carousel.getInstance(onboardingCarousel);
            if (carouselInstance) {
                carouselInstance.to(0); // Go to the first slide (index 0)
            }
            onboardingTourModal.show();
        });
    }

    if (viewTipsBtn) {
        viewTipsBtn.addEventListener('click', function() {
            onboardingTipsModal.show();
        });
    }

    if (restartOnboardingBtn) {
        restartOnboardingBtn.addEventListener('click', function() {
            if (confirm("Are you sure you want to restart the onboarding tour? This will mark it as incomplete in your profile.")) {
                updateOnboardingStatus('pending'); // Reset status to false (pending)
                // Give a slight delay before showing the modal to ensure DB update is processed
                setTimeout(() => {
                    // Reset carousel to first slide before showing
                    const carouselInstance = bootstrap.Carousel.getInstance(onboardingCarousel);
                    if (carouselInstance) {
                        carouselInstance.to(0); // Go to the first slide (index 0)
                    }
                    onboardingTourModal.show();
                }, 300); // 300ms delay
            }
        });
    }
});
</script>