// This file will contain your custom JavaScript for ChronoNav.

// JavaScript to populate the Edit Event Modal (moved from calendar.php)
$('#editEventModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget); // Button that triggered the modal
    var id = button.data('id');
    var eventName = button.data('event-name');
    var description = button.data('description');
    var startDate = button.data('start-date');
    var startTime = button.data('start-time');
    var endDate = button.data('end-date');
    var endTime = button.data('end-time');
    var location = button.data('location');
    var eventType = button.data('event-type'); // Get event type

    var modal = $(this);
    modal.find('#edit_event_id').val(id);
    modal.find('#edit_event_name').val(eventName);
    modal.find('#edit_description').val(description);
    modal.find('#edit_start_date').val(startDate);
    modal.find('#edit_start_time').val(startTime);
    modal.find('#edit_end_date').val(endDate);
    modal.find('#edit_end_time').val(endTime);
    modal.find('#edit_location').val(location);
    modal.find('#edit_event_type').val(eventType); // Set event type
});

// You can add other custom scripts for your site in this file as well
// For example, if you have any other JavaScript behavior.
// (e.g., if navigation_viewer.js or profile.js also had inline content that could be consolidated here,
// or other general scripts not specific to a single page).