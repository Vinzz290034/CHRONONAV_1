// CHRONONAV_WEB_UNO/assets/js/onboarding_tour.js

document.addEventListener('DOMContentLoaded', function() {
    // Modal instance
    const onboardingModal = new bootstrap.Modal(document.getElementById('onboardingModal'));

    // Modal Content and Buttons
    const tourContent = document.getElementById('tour-content');
    const tipsContent = document.getElementById('tips-content');
    const prevBtn = document.getElementById('prev-step-btn');
    const nextBtn = document.getElementById('next-step-btn');
    const finishBtn = document.getElementById('finish-tour-btn');
    const skipBtn = document.getElementById('skip-tour-btn');
    const restartBtn = document.getElementById('restart-tour-btn');

    // Dashboard Buttons
    const viewTourBtn = document.getElementById('viewTourBtn');
    const viewTipsBtn = document.getElementById('viewTipsBtn');
    const restartOnboardingBtn = document.getElementById('restartOnboardingBtn');

    let currentStep = 0;
    let tourData = [];
    let isTourActive = true;

    // Fetch tour data from the embedded JSON script tag
    function fetchTourData() {
        const tourDataElement = document.getElementById('tour-data');
        if (tourDataElement) {
            try {
                tourData = JSON.parse(tourDataElement.textContent);
            } catch (e) {
                console.error("Failed to parse onboarding data:", e);
                tourData = [];
            }
        }

        if (tourData.length > 0) {
            // Check for a user-specific flag here to determine if the tour should auto-start
            // For now, it will only auto-start if the user hasn't seen it yet
            // This logic needs to be implemented on the backend.
            const hasSeenOnboarding = localStorage.getItem('hasSeenOnboarding') === 'true';
            if (!hasSeenOnboarding) {
                // Auto-start tour on first visit
                startTour();
                localStorage.setItem('hasSeenOnboarding', 'true');
            }
        } else {
            console.log("No onboarding data found for this role.");
        }
    }

    // --- Core Onboarding Functions ---
    function startTour() {
        isTourActive = true;
        currentStep = 0;
        showStep(currentStep);
        onboardingModal.show();
        tourContent.classList.remove('d-none');
        tipsContent.classList.add('d-none');
        updateButtons();
    }

    function showStep(stepIndex) {
        if (stepIndex >= 0 && stepIndex < tourData.length) {
            const step = tourData[stepIndex];
            tourContent.innerHTML = `
                <h3>Step ${stepIndex + 1}: ${step.title}</h3>
                <p>${step.content}</p>
            `;
            updateButtons();
        }
    }

    function showTips() {
        isTourActive = false;
        tipsContent.innerHTML = `
            <h3>Quick Tips</h3>
            ${tourData.map(step => `<p><strong>${step.title}:</strong> ${step.content}</p>`).join('')}
        `;
        tourContent.classList.add('d-none');
        tipsContent.classList.remove('d-none');
        updateButtons();
    }
    
    function finishTour() {
        onboardingModal.hide();
        // Here you would typically make an AJAX call to mark the tour as complete
        // in the database for the current user.
        alert("Onboarding tour finished!");
    }

    function updateButtons() {
        if (isTourActive) {
            // Tour mode
            prevBtn.classList.toggle('d-none', currentStep === 0);
            nextBtn.classList.toggle('d-none', currentStep === tourData.length - 1);
            finishBtn.classList.toggle('d-none', currentStep !== tourData.length - 1);
            skipBtn.classList.remove('d-none');
            restartBtn.classList.add('d-none');
        } else {
            // Tips mode
            prevBtn.classList.add('d-none');
            nextBtn.classList.add('d-none');
            finishBtn.classList.add('d-none');
            skipBtn.classList.add('d-none');
            restartBtn.classList.remove('d-none');
        }
    }

    // --- Event Listeners for Modal Controls ---
    nextBtn.addEventListener('click', () => {
        if (currentStep < tourData.length - 1) {
            currentStep++;
            showStep(currentStep);
        }
    });

    prevBtn.addEventListener('click', () => {
        if (currentStep > 0) {
            currentStep--;
            showStep(currentStep);
        }
    });

    skipBtn.addEventListener('click', showTips);
    restartBtn.addEventListener('click', startTour);
    finishBtn.addEventListener('click', finishTour);

    // --- Event Listeners for Dashboard Buttons (THIS IS THE NEW PART) ---
    if (viewTourBtn) {
        viewTourBtn.addEventListener('click', () => {
            if (tourData.length > 0) {
                startTour();
            } else {
                alert("No tour steps available for your role.");
            }
        });
    }

    if (viewTipsBtn) {
        viewTipsBtn.addEventListener('click', () => {
            if (tourData.length > 0) {
                showTips();
                onboardingModal.show();
            } else {
                alert("No tips available for your role.");
            }
        });
    }

    if (restartOnboardingBtn) {
        restartOnboardingBtn.addEventListener('click', () => {
            if (tourData.length > 0) {
                startTour();
            } else {
                alert("No onboarding steps to restart.");
            }
        });
    }

    // Initial fetch of data when the page loads
    fetchTourData();
});