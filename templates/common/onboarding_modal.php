<div class="modal fade" id="onboardingModal" tabindex="-1" aria-labelledby="onboardingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="onboardingModalLabel">Welcome to the Dashboard!</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="tour-content">
                    </div>
                <div id="tips-content" class="d-none">
                    </div>
            </div>
            <div class="modal-footer">
                <div class="me-auto">
                    <button type="button" class="btn btn-secondary" id="skip-tour-btn">Skip Tour</button>
                    <button type="button" class="btn btn-warning d-none" id="restart-tour-btn">Restart Tour</button>
                </div>
                <button type="button" class="btn btn-primary" id="prev-step-btn" disabled>Previous</button>
                <button type="button" class="btn btn-primary" id="next-step-btn">Next</button>
                <button type="button" class="btn btn-success d-none" id="finish-tour-btn">Finish</button>
            </div>
        </div>
    </div>
</div>