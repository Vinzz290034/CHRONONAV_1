$(document).ready(function() {
    const ocrModal = $('#ocrModal');
    const uploadStep = $('#upload-step');
    const previewStep = $('#preview-step');
    const confirmationStep = $('#confirmation-step');
    const processOcrBtn = $('#processOcrBtn');
    const backToUploadBtn = $('#backToUploadBtn');
    const confirmScheduleBtn = $('#confirmScheduleBtn');
    const studyLoadPdfInput = $('#studyLoadPdf');
    const previewContent = $('#preview-content');
    const ocrAlert = $('#ocr-alert');

    // Reset modal to initial state when it's closed
    ocrModal.on('hidden.bs.modal', function () {
        uploadStep.show();
        previewStep.hide();
        confirmationStep.hide();
        studyLoadPdfInput.val('');
        previewContent.html('<p class="text-center text-muted">Awaiting file upload...</p>');
        ocrAlert.hide();
    });

    // Handle "Process Document" button click
    processOcrBtn.on('click', function() {
        const file = studyLoadPdfInput[0].files[0];
        if (!file) {
            ocrAlert.text('Please select a PDF file to upload.').removeClass('alert-success').addClass('alert-danger').show();
            return;
        }

        const formData = new FormData();
        formData.append('studyLoadPdf', file);

        // Show a processing message and switch steps
        previewContent.html('<p class="text-center text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Extracting data from PDF... Please wait.</p>');
        uploadStep.hide();
        previewStep.show();
        ocrAlert.hide();

        // AJAX call to the server-side PHP script
        $.ajax({
            url: '../../pages/user/process_ocr.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    // Build the preview table from the server response
                    let tableHtml = '<table class="table table-bordered table-striped"><thead><tr><th>Sched. No</th><th>Course No.</th><th>Time</th><th>Days</th><th>Room</th><th>Units</th></tr></thead><tbody>';
                    response.schedule.forEach(item => {
                        tableHtml += `
                            <tr>
                                <td>${item.sched_no}</td>
                                <td>${item.course_no}</td>
                                <td>${item.time}</td>
                                <td>${item.days}</td>
                                <td>${item.room}</td>
                                <td>${item.units}</td>
                            </tr>
                        `;
                    });
                    tableHtml += '</tbody></table>';
                    previewContent.html(tableHtml);

                    confirmScheduleBtn.prop('disabled', false);
                } else {
                    previewContent.html(`<p class="text-center text-danger"><i class="fas fa-exclamation-circle me-2"></i>Error: ${response.error}</p>`);
                    confirmScheduleBtn.prop('disabled', true);
                }
            },
            error: function() {
                previewContent.html('<p class="text-center text-danger"><i class="fas fa-exclamation-circle me-2"></i>An unexpected error occurred. Please try again.</p>');
                confirmScheduleBtn.prop('disabled', true);
            }
        });
    });

    // Handle "Back" button click
    backToUploadBtn.on('click', function() {
        previewStep.hide();
        uploadStep.show();
        confirmScheduleBtn.prop('disabled', false);
    });

    // Handle "Confirm Extracted Schedule" button click
    confirmScheduleBtn.on('click', function() {
        // Here, you would send the confirmed schedule data to another PHP script
        // to save it permanently in the database.
        
        // For this example, we'll just show the confirmation step.
        previewStep.hide();
        confirmationStep.show();
    });
});