<?php
// CHRONONAV_WEB_UNO/pages/faculty/schedule_view.php

?>
<?php
// --- Include the Faculty-specific Header ---
// This includes Bootstrap CSS, Font Awesome CSS, and top_navbar.css
// It also sets up the top navigation bar with user profile dropdown.
require_once '../../templates/faculty/header_faculty.php';
?>

<?php
// --- Include the Faculty-specific Sidenav ---
// This includes sidenavs.css and sets up the left navigation sidebar.
require_once '../../templates/faculty/sidenav_faculty.php';
?>

    <div class="wrapper">
        <div class="main-content-wrapper">
            <div class="top-right-buttons">
                <button class="btn btn-info btn-sm" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>

            <div class="container-fluid py-4">
                <div class="calendar-container">
                    <div class="calendar-header">
                        <button id="prevMonth"><i class="fas fa-chevron-left"></i></button>
                        <h3 id="currentMonthYear">July 2025</h3>
                        <button id="nextMonth"><i class="fas fa-chevron-right"></i></button>
                    </div>
                    <div class="calendar-weekdays">
                        <div>Sun</div>
                        <div>Mon</div>
                        <div>Tue</div>
                        <div>Wed</div>
                        <div>Thu</div>
                        <div>Fri</div>
                        <div>Sat</div>
                    </div>
                    <div id="calendarDays" class="calendar-days">
                        </div>
                </div>

                <div class="events-list-container mt-4">
                    <h4 id="eventsListTitle">Events for July 12, 2025</h4>
                    <div id="eventsListContent">
                        <div class="no-events-message">No events for this day.</div>
                    </div>
                </div>

                <div class="action-buttons">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
                        <i class="fas fa-plus"></i> Add Event for This Day
                    </button>
                    <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#calendarEventModal">
                        <i class="fas fa-calendar-plus"></i> Calendar Event
                    </button>
                </div>

                <?php
                // Display session messages if any
                if (isset($_SESSION['message'])) {
                    echo '<div class="alert alert-' . htmlspecialchars($_SESSION['message_type']) . ' alert-dismissible fade show" role="alert">';
                    echo htmlspecialchars($_SESSION['message']);
                    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    echo '</div>';
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                }
                ?>

            </div>

            <?php include '../../templates/footer.php'; ?>
        </div>
    </div>

    <div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addEventModalLabel">Add Schedule for <span id="modalSelectedDate"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST">
                        <input type="hidden" name="action" value="add_schedule">
                        <input type="hidden" id="eventDate" name="event_date"> <div class="mb-3">
                            <label for="title" class="form-label">Course Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="roomId" class="form-label">Room</label>
                            <select class="form-select" id="roomId" name="room_id" required>
                                <option value="">Select Room</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?= htmlspecialchars($room['id']) ?>">
                                        <?= htmlspecialchars($room['room_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="startTime" class="form-label">Start Time</label>
                            <input type="time" class="form-control" id="startTime" name="start_time" required>
                        </div>
                        <div class="mb-3">
                            <label for="endTime" class="form-label">End Time</label>
                            <input type="time" class="form-control" id="endTime" name="end_time" required>
                        </div>
                         <div class="mb-3">
                            <label for="academicYear" class="form-label">Academic Year</label>
                            <input type="text" class="form-control" id="academicYear" name="academic_year" placeholder="e.g., 2024-2025" required>
                        </div>
                        <div class="mb-3">
                            <label for="semester" class="form-label">Semester</label>
                            <select class="form-select" id="semester" name="semester" required>
                                <option value="">Select Semester</option>
                                <option value="First Semester">First Semester</option>
                                <option value="Second Semester">Second Semester</option>
                                <option value="Summer">Summer</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Schedule</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="calendarEventModal" tabindex="-1" aria-labelledby="calendarEventModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="calendarEventModalLabel">Add General Schedule/Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="alert alert-info">Note: This will add a recurring schedule entry based on the chosen day.</p>
                    <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST">
                        <input type="hidden" name="action" value="add_schedule">
                        <div class="mb-3">
                            <label for="calEventDate" class="form-label">Date (to determine Day of Week)</label>
                            <input type="date" class="form-control" id="calEventDate" name="event_date" required>
                        </div>
                         <div class="mb-3">
                            <label for="calTitle" class="form-label">Event Title</label>
                            <input type="text" class="form-control" id="calTitle" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="calDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="calDescription" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="calRoomId" class="form-label">Room</label>
                            <select class="form-select" id="calRoomId" name="room_id" required>
                                <option value="">Select Room</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?= htmlspecialchars($room['id']) ?>">
                                        <?= htmlspecialchars($room['room_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="calStartTime" class="form-label">Start Time</label>
                            <input type="time" class="form-control" id="calStartTime" name="start_time" required>
                        </div>
                        <div class="mb-3">
                            <label for="calEndTime" class="form-label">End Time</label>
                            <input type="time" class="form-control" id="calEndTime" name="end_time" required>
                        </div>
                         <div class="mb-3">
                            <label for="calAcademicYear" class="form-label">Academic Year</label>
                            <input type="text" class="form-control" id="calAcademicYear" name="academic_year" placeholder="e.g., 2024-2025" required>
                        </div>
                        <div class="mb-3">
                            <label for="calSemester" class="form-label">Semester</label>
                            <select class="form-select" id="calSemester" name="semester" required>
                                <option value="">Select Semester</option>
                                <option value="First Semester">First Semester</option>
                                <option value="Second Semester">Second Semester</option>
                                <option value="Summer">Summer</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Event</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editEventModalLabel">Edit Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST">
                        <input type="hidden" name="action" value="edit_schedule">
                        <input type="hidden" id="editScheduleId" name="schedule_id">

                        <div class="mb-3">
                            <label for="editTitle" class="form-label">Course Title</label>
                            <input type="text" class="form-control" id="editTitle" name="edit_title" required>
                        </div>
                        <div class="mb-3">
                            <label for="editDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editDescription" name="edit_description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="editRoomId" class="form-label">Room</label>
                            <select class="form-select" id="editRoomId" name="edit_room_id" required>
                                <option value="">Select Room</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?= htmlspecialchars($room['id']) ?>">
                                        <?= htmlspecialchars($room['room_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editDayOfWeek" class="form-label">Day of Week</label>
                            <select class="form-select" id="editDayOfWeek" name="edit_day_of_week" required>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editStartTime" class="form-label">Start Time</label>
                            <input type="time" class="form-control" id="editStartTime" name="edit_start_time" required>
                        </div>
                        <div class="mb-3">
                            <label for="editEndTime" class="form-label">End Time</label>
                            <input type="time" class="form-control" id="editEndTime" name="edit_end_time" required>
                        </div>
                         <div class="mb-3">
                            <label for="editAcademicYear" class="form-label">Academic Year</label>
                            <input type="text" class="form-control" id="editAcademicYear" name="edit_academic_year" required>
                        </div>
                        <div class="mb-3">
                            <label for="editSemester" class="form-label">Semester</label>
                            <select class="form-select" id="editSemester" name="edit_semester" required>
                                <option value="First Semester">First Semester</option>
                                <option value="Second Semester">Second Semester</option>
                                <option value="Summer">Summer</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/script.js"></script>

    <script>
        // PHP data available in JavaScript
        // Note: The `allFacultySchedules` now includes `room_name` due to the JOIN in the PHP query.
        const allFacultySchedules = <?php echo $schedules_json; ?>;
        const facultyId = <?php echo $faculty_id; ?>;

        let currentMonth = new Date().getMonth();
        let currentYear = new Date().getFullYear();
        let selectedDayElement = null; // To keep track of the currently selected day div
        let selectedDate = new Date(); // Stores the currently selected full date object

        const monthNames = [
            "January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"
        ];
        const dayNames = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

        const calendarDaysEl = document.getElementById('calendarDays');
        const currentMonthYearEl = document.getElementById('currentMonthYear');
        const eventsListTitleEl = document.getElementById('eventsListTitle');
        const eventsListContentEl = document.getElementById('eventsListContent');
        const modalSelectedDateEl = document.getElementById('modalSelectedDate');
        const eventDateInputEl = document.getElementById('eventDate'); // For 'Add Event' modal date


        function renderCalendar(month, year) {
            calendarDaysEl.innerHTML = ''; // Clear previous days
            currentMonthYearEl.textContent = `${monthNames[month]} ${year}`;

            const firstDayOfMonth = new Date(year, month, 1).getDay(); // 0 for Sunday, 1 for Monday, etc.
            const daysInMonth = new Date(year, month + 1, 0).getDate();

            // Render empty days for the start of the month
            for (let i = 0; i < firstDayOfMonth; i++) {
                const emptyDay = document.createElement('div');
                emptyDay.classList.add('empty-day');
                calendarDaysEl.appendChild(emptyDay);
            }

            // Render actual days
            for (let day = 1; day <= daysInMonth; day++) {
                const dayEl = document.createElement('div');
                dayEl.textContent = day;
                dayEl.dataset.date = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                dayEl.classList.add('calendar-day');

                // Check for current day
                const today = new Date();
                if (day === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
                    dayEl.classList.add('current-day');
                }

                // Check for schedules on this day (simplified logic for recurring schedules)
                const currentDayOfWeek = dayNames[new Date(year, month, day).getDay()];
                const hasSchedule = allFacultySchedules.some(schedule => schedule.day_of_week === currentDayOfWeek);
                if (hasSchedule) {
                    dayEl.classList.add('has-event');
                    const indicator = document.createElement('span');
                    indicator.classList.add('calendar-event-indicator');
                    dayEl.appendChild(indicator);
                }

                dayEl.addEventListener('click', () => selectDay(dayEl, day, month, year));
                calendarDaysEl.appendChild(dayEl);
            }

            // After rendering, ensure the currently selected date is highlighted
            const selectedDayString = `${selectedDate.getFullYear()}-${String(selectedDate.getMonth() + 1).padStart(2, '0')}-${String(selectedDate.getDate()).padStart(2, '0')}`;
            const previouslySelectedEl = document.querySelector(`[data-date="${selectedDayString}"]`);
            if (previouslySelectedEl && previouslySelectedEl.parentElement === calendarDaysEl) {
                selectDay(previouslySelectedEl, selectedDate.getDate(), selectedDate.getMonth(), selectedDate.getFullYear(), false); // Don't re-render events
            } else {
                // If selected date is not in current view, or no date selected, default to today or 1st of month
                const today = new Date();
                if (currentMonth === today.getMonth() && currentYear === today.getFullYear()) {
                    const todayEl = document.querySelector('.current-day');
                    if (todayEl) {
                        selectDay(todayEl, today.getDate(), today.getMonth(), today.getFullYear(), true);
                    }
                } else {
                    const firstDayEl = document.querySelector('.calendar-day:not(.empty-day)');
                    if (firstDayEl) {
                        selectDay(firstDayEl, parseInt(firstDayEl.textContent), month, year, true);
                    }
                }
            }
        }

        function selectDay(dayEl, day, month, year, renderEvents = true) {
            // Remove 'selected-day' from previous selection
            if (selectedDayElement) {
                selectedDayElement.classList.remove('selected-day');
            }
            // Add 'selected-day' to current selection
            dayEl.classList.add('selected-day');
            selectedDayElement = dayEl;

            selectedDate = new Date(year, month, day); // Update the global selectedDate

            const formattedDate = selectedDate.toLocaleDateString('en-US', {
                month: 'long',
                day: 'numeric',
                year: 'numeric'
            });

            eventsListTitleEl.textContent = `Events for ${formattedDate}`;

            if (renderEvents) {
                displayEventsForSelectedDay(selectedDate);
            }
        }

        function displayEventsForSelectedDay(date) {
            eventsListContentEl.innerHTML = ''; // Clear previous events

            const dayOfWeekName = dayNames[date.getDay()]; // e.g., "Saturday"

            let eventsFound = false;

            // Filter schedules for the selected day of the week
            const relevantSchedules = allFacultySchedules.filter(schedule => schedule.day_of_week === dayOfWeekName);

            if (relevantSchedules.length > 0) {
                relevantSchedules.forEach(event => {
                    const eventDiv = document.createElement('div');
                    eventDiv.classList.add('event-item');
                    eventDiv.innerHTML = `
                        <div>
                            <strong>${htmlspecialchars(event.title)}</strong><br>
                            <small>${htmlspecialchars(event.description)}</small><br>
                            <small>Year: ${htmlspecialchars(event.academic_year)}, Semester: ${htmlspecialchars(event.semester)}</small>
                        </div>
                        <div>
                            ${formatTime(event.start_time)} - ${formatTime(event.end_time)}<br>
                            Room: ${htmlspecialchars(event.room_name)}
                        </div>
                        <div class="event-actions">
                            <button class="btn btn-sm btn-info edit-btn"
                                data-bs-toggle="modal" data-bs-target="#editEventModal"
                                data-id="${event.schedule_id}"
                                data-title="${htmlspecialchars(event.title)}"
                                data-description="${htmlspecialchars(event.description)}"
                                data-room-id="${htmlspecialchars(event.room_id)}"
                                data-day-of-week="${htmlspecialchars(event.day_of_week)}"
                                data-start-time="${htmlspecialchars(event.start_time)}"
                                data-end-time="${htmlspecialchars(event.end_time)}"
                                data-academic-year="${htmlspecialchars(event.academic_year)}"
                                data-semester="${htmlspecialchars(event.semester)}">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this schedule?');">
                                <input type="hidden" name="action" value="delete_schedule">
                                <input type="hidden" name="schedule_id" value="${event.schedule_id}">
                                <button type="submit" class="btn btn-sm btn-danger delete-btn">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </button>
                            </form>
                        </div>
                    `;
                    eventsListContentEl.appendChild(eventDiv);
                    eventsFound = true;
                });
            }

            if (!eventsFound) {
                eventsListContentEl.innerHTML = '<div class="no-events-message">No regular schedules for this day.</div>';
            }
        }

        function formatTime(timeString) {
            const [hours, minutes] = timeString.split(':');
            const date = new Date();
            date.setHours(parseInt(hours));
            date.setMinutes(parseInt(minutes));
            return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
        }

        // Simple HTML Escaping for data attributes
        function htmlspecialchars(str) {
            if (typeof str != 'string') return str; // handle non-string values
            return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }


        document.getElementById('prevMonth').addEventListener('click', () => {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            renderCalendar(currentMonth, currentYear);
        });

        document.getElementById('nextMonth').addEventListener('click', () => {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            renderCalendar(currentMonth, currentYear);
        });

        // Initialize calendar on page load
        document.addEventListener('DOMContentLoaded', () => {
            renderCalendar(currentMonth, currentYear);
            // Default select today's date if it's the current month, otherwise the first day
            const today = new Date();
            if (currentMonth === today.getMonth() && currentYear === today.getFullYear()) {
                const todayEl = document.querySelector('.current-day');
                if (todayEl) {
                    selectDay(todayEl, today.getDate(), today.getMonth(), today.getFullYear(), true);
                }
            } else {
                 const firstDayEl = document.querySelector('.calendar-day:not(.empty-day)');
                 if(firstDayEl) {
                    selectDay(firstDayEl, parseInt(firstDayEl.textContent), currentMonth, currentYear, true);
                 }
            }
        });

        // Event listener for when the add event modal is shown
        var addEventModal = document.getElementById('addEventModal')
        addEventModal.addEventListener('show.bs.modal', function (event) {
            // Update the date in the modal title and hidden input
            modalSelectedDateEl.textContent = selectedDate.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            eventDateInputEl.value = selectedDate.toISOString().slice(0, 10);
        });

        // Event listener for when the edit event modal is shown
        var editEventModal = document.getElementById('editEventModal');
        editEventModal.addEventListener('show.bs.modal', function (event) {
            // Button that triggered the modal
            var button = event.relatedTarget;

            // Extract info from data-bs-* attributes
            var id = button.getAttribute('data-id');
            var title = button.getAttribute('data-title');
            var description = button.getAttribute('data-description');
            var roomId = button.getAttribute('data-room-id'); // This is the ID now
            var dayOfWeek = button.getAttribute('data-day-of-week');
            var startTime = button.getAttribute('data-start-time');
            var endTime = button.getAttribute('data-end-time');
            var academicYear = button.getAttribute('data-academic-year');
            var semester = button.getAttribute('data-semester');


            // Update the modal's content
            var modalTitle = editEventModal.querySelector('.modal-title');
            var modalBodyInputId = editEventModal.querySelector('#editScheduleId');
            var modalBodyInputTitle = editEventModal.querySelector('#editTitle');
            var modalBodyInputDescription = editEventModal.querySelector('#editDescription');
            var modalBodySelectRoomId = editEventModal.querySelector('#editRoomId'); // Changed to select
            var modalBodySelectDayOfWeek = editEventModal.querySelector('#editDayOfWeek');
            var modalBodyInputStartTime = editEventModal.querySelector('#editStartTime');
            var modalBodyInputEndTime = editEventModal.querySelector('#editEndTime');
            var modalBodyInputAcademicYear = editEventModal.querySelector('#editAcademicYear');
            var modalBodySelectSemester = editEventModal.querySelector('#editSemester');

            modalTitle.textContent = 'Edit Schedule (ID: ' + id + ')';
            modalBodyInputId.value = id;
            modalBodyInputTitle.value = title;
            modalBodyInputDescription.value = description;
            modalBodySelectRoomId.value = roomId; // Set value for dropdown
            modalBodySelectDayOfWeek.value = dayOfWeek;
            modalBodyInputStartTime.value = startTime;
            modalBodyInputEndTime.value = endTime;
            modalBodyInputAcademicYear.value = academicYear;
            modalBodySelectSemester.value = semester;
        });

    </script>
