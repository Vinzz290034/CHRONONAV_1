-- Finalized Database Schema for CHRONONAV_WEB_DOSS (Complete System)

-- --- 1. Users Table (Core table, must be created first) ---
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'faculty', 'admin') NOT NULL DEFAULT 'user',
    course VARCHAR(100) DEFAULT NULL,
    department VARCHAR(100) DEFAULT NULL,
    profile_img VARCHAR(255) NOT NULL DEFAULT 'uploads/profiles/default-avatar.png',
    is_onboarding_completed BOOLEAN DEFAULT FALSE, -- <-- NEWLY ADDED COLUMN FOR ONBOARDING MODULE
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add 'student_id' column to the 'users' table if it doesn't exist.
-- This ALTER statement is good if you're updating an existing database.
-- If running this complete script from scratch, the column can also be added directly above.
ALTER TABLE users ADD COLUMN IF NOT EXISTS student_id VARCHAR(50) UNIQUE NULL;


-- Insert default admin and user accounts (Crucial for foreign keys in other tables)
-- REPLACE THESE HASHED PASSWORDS WITH YOUR OWN GENERATED ONES USING PHP's password_hash()!
-- Example: password_hash('your_secret_password', PASSWORD_DEFAULT)
-- Note: 'is_onboarding_completed' will default to FALSE for new inserts as per schema.
INSERT IGNORE INTO users (id, name, email, password, role, course, department, student_id) VALUES
(1, 'Chrono Admin', 'admin@chrononav.com', '$2y$10$j/t1N3F4W5X6Y7Z8A9B0C1D2E3F4G5H6I7J8K9L0M1N2O3P4Q5R6S7T8U9V0W1X2', 'admin', NULL, 'IT Department', NULL), /* Placeholder hash for a strong password */
(2, 'John Student', 'john.doe@chrononav.com', '$2y$10$a1B2C3D4E5F6G7H8I9J0K1L2M3N4O5P6Q7R8S9T0U1V2W3X4Y5Z6A7B8C9D0E1F2', 'user', 'BSIT', NULL, 'S001'),   /* Placeholder hash */
(3, 'Jane Faculty', 'jane.faculty@chrononav.com', '$2y$10$x9Y8Z7A6B5C4D3E2F1G0H9I8J7K6L5M4N3O2P1Q0R9S8T7U6V5W4X3Y2Z1A0B9C8', 'faculty', NULL, 'Computer Science', NULL); /* Placeholder hash */


-- --- 2. Rooms Table (New module: Building Room Manager dependency) ---
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_name VARCHAR(100) NOT NULL UNIQUE,
    capacity INT NULL,
    room_type ENUM('Classroom', 'Laboratory', 'Lecture Hall', 'Office', 'Other') DEFAULT 'Classroom',
    equipment TEXT NULL, /* e.g., 'Projector, Whiteboard, AC' */
    location_description TEXT NULL, /* e.g., '3rd Floor, Main Building' */
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Optional: Add some dummy data for rooms
INSERT IGNORE INTO rooms (id, room_name, capacity, room_type, equipment, location_description) VALUES
(1, 'Room 201', 50, 'Classroom', 'Projector, Whiteboard, AC', '2nd Floor, Science Building'),
(2, 'Lab 305', 30, 'Laboratory', 'Computers, Microscopes', '3rd Floor, Engineering Building'),
(3, 'Auditorium A', 200, 'Lecture Hall', 'Projector, Sound System', 'Ground Floor, Admin Building'),
(4, 'Faculty Office 1', 5, 'Office', 'Desk, Chair', '4th Floor, Main Building');


-- --- 3. Schedules Table (Modified to link to 'rooms' table) ---
-- Drop and recreate to ensure 'room_id' foreign key is correctly applied if table existed with 'room' column.
DROP TABLE IF EXISTS schedules;
CREATE TABLE IF NOT EXISTS schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT, /* Can be NULL if schedule is purely faculty-assigned and not directly user-linked initially */
    faculty_id INT,
    room_id INT NULL, /* NEW: Foreign key to rooms table */
    title VARCHAR(255) NOT NULL,
    description TEXT,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    academic_year VARCHAR(50),
    semester ENUM('First Semester', 'Second Semester', 'Summer', 'Midyear'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL /* NEW: Foreign key constraint */
);

-- Insert dummy schedules, now referencing room_id from the 'rooms' table
INSERT IGNORE INTO schedules (user_id, faculty_id, room_id, title, description, day_of_week, start_time, end_time, academic_year, semester) VALUES
(2, 3, 1, 'Introduction to Psychology', 'Core Psych course', 'Monday', '10:00:00', '11:00:00', '2024-2025', 'First Semester'), /* user_id 2, faculty_id 3, room_id 1 (Room 201) */
(2, 3, 2, 'Calculus I', 'Math for Engineers', 'Tuesday', '11:30:00', '12:30:00', '2024-2025', 'First Semester'), /* user_id 2, faculty_id 3, room_id 2 (Lab 305) */
(2, 3, NULL, 'English Literature', 'Reading and Analysis', 'Wednesday', '01:00:00', '02:00:00', '2024-2025', 'First Semester'); /* user_id 2, faculty_id 3, no room assigned yet (NULL) */


-- --- 4. Login Attempts Table (New module: System Logs & Activity) ---
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL, /* NULL for failed attempts where user_id is unknown */
    username VARCHAR(255) NOT NULL, /* The username attempted */
    ip_address VARCHAR(45) NOT NULL,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('success', 'failed') NOT NULL,
    user_agent TEXT NULL, /* Optional: Store browser/OS info */
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL /* Set NULL if user is deleted */
);


-- --- 5. Events Table ---
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_type ENUM('one_time', 'recurring') NOT NULL,
    start_date DATE,
    end_date DATE,
    -- For recurring events (e.g., 'Monday', 'Tuesday')
    day_of_week VARCHAR(10),
    start_time TIME NOT NULL,
    end_time TIME,
    location VARCHAR(255),
    -- For reminders, can be NULL for schedules
    is_completed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


-- --- 6. Reminders Table ---
CREATE TABLE IF NOT EXISTS reminders (
    reminder_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    due_date DATE NOT NULL,
    due_time TIME,
    is_completed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


-- --- 7. Academic Calendar Events Table ---
CREATE TABLE IF NOT EXISTS academic_calendar_events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    event_type ENUM('Holiday', 'Exam', 'Enrollment', 'Deadline', 'Other') DEFAULT 'Other',
    created_by_user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);


-- --- 8. Feedback Table ---
CREATE TABLE IF NOT EXISTS feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT, /* NULL if anonymous feedback is allowed */
    feedback_type ENUM('Bug Report', 'Feature Request', 'General Feedback', 'Suggestion') NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5), /* For app rating, nullable if not required for all feedback types */
    status ENUM('New', 'In Progress', 'Resolved', 'Archived') DEFAULT 'New',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);


-- --- 9. FAQs Table ---
CREATE TABLE IF NOT EXISTS faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT IGNORE INTO faqs (id, question, answer) VALUES
(1, 'How do I reset my password?', 'You can reset your password by clicking on the "Forgot Password" link on the login page and following the instructions.'),
(2, 'Where can I find my class schedule?', 'Your class schedule is available on your dashboard under the "Upcoming Classes" section after you upload your study load PDF.'),
(3, 'How do I add my study load?', 'Go to your dashboard, click the "Add" button under "Add Study Load", and upload your schedule PDF.'),
(4, 'Is ChronoNav available on mobile?', 'Yes, ChronoNav is designed to be responsive and can be accessed from any mobile browser.'),
(5, 'Who do I contact for technical issues?', 'If you encounter technical issues, please open a support ticket via the Help & Support Center.');


-- --- 10. Tickets Table ---
CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open', 'in progress', 'closed') DEFAULT 'open',
    admin_reply TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT IGNORE INTO tickets (id, user_id, subject, message, status) VALUES
(1, 2, 'Login Issue', 'I cannot log in to my account. It says invalid credentials.', 'open'),
(2, 2, 'Schedule Not Displaying', 'My uploaded PDF schedule is not showing up in my upcoming classes.', 'open'),
(3, 1, 'Admin Test Ticket', 'This is a test ticket submitted by an admin for testing purposes.', 'closed');


CREATE TABLE ticket_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NULL, -- NULL if it's an admin reply (or you could have an admin_id column)
    admin_id INT NULL, -- NULL if it's a user reply
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE -- Assuming admins are also in 'users' table. If not, adjust.
);

-- Optional: Add an index for faster lookups by ticket_id
CREATE INDEX idx_ticket_id ON ticket_replies (ticket_id);

-- --- 11. Announcements Table ---
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT IGNORE INTO announcements (id, user_id, title, content) VALUES
(1, 1, 'Welcome to the New Semester!', 'Dear students, welcome back to another exciting semester! We wish you all the best in your studies.'),
(2, 1, 'Reminder: Midterm Exams Next Week', 'Just a friendly reminder that midterm examinations will be held next week, from October 14th to 18th. Please review your notes and schedules carefully.'),
(3, 1, 'Campus Event: Annual Sports Fest', 'Get ready for our Annual Sports Fest, scheduled for November 5th and 6th. Sign-ups for various sports are now open at the Student Affairs Office!');


CREATE TABLE calendar_events (
    id INT(11) NOT NULL AUTO_INCREMENT,
    event_name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    location VARCHAR(255) DEFAULT NULL,
    event_type VARCHAR(50) DEFAULT 'Other',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);


CREATE TABLE classes (
    class_id INT(11) NOT NULL AUTO_INCREMENT,
    class_name VARCHAR(255) NOT NULL,
    class_code VARCHAR(50) NOT NULL,
    faculty_id INT(11) NOT NULL,
    room_id INT(11) NULL,
    semester VARCHAR(50) NULL,
    day_of_week VARCHAR(10) NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (class_id)
);

CREATE TABLE consultation_hours (
    id INT(11) NOT NULL AUTO_INCREMENT,
    faculty_id INT(11) NOT NULL,
    day_of_week VARCHAR(20) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

CREATE TABLE office_hours (
    oh_id INT(11) NOT NULL AUTO_INCREMENT,
    faculty_id INT(11) NOT NULL,
    day_of_week VARCHAR(10) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    location VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (oh_id)
);



CREATE TABLE office_hours_request (
    id INT(11) NOT NULL AUTO_INCREMENT,
    faculty_id INT(11) NOT NULL,
    proposed_day VARCHAR(20) NOT NULL,
    proposed_start_time TIME NOT NULL,
    proposed_end_time TIME NOT NULL,
    request_letter_message TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'revised') DEFAULT 'pending',
    admin_reply_message TEXT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     responded_at DATETIME NULL,
    approved_day VARCHAR(20) NULL,
    approved_start_time TIME NULL,
    approved_end_time TIME NULL,
    PRIMARY KEY (id)
);

CREATE TABLE user_calendar_events (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    calendar_event_id INT(11) DEFAULT NULL,
    event_name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    location VARCHAR(255) DEFAULT NULL,
    event_type VARCHAR(50) DEFAULT NULL, -- Assuming 'Other' default means NULL or a placeholder
    is_personal TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    -- If 'calendar_event_id' refers to another table, you'd add another FOREIGN KEY constraint here.
    -- For example: FOREIGN KEY (calendar_event_id) REFERENCES calendar_events_master(id)
);

CREATE TABLE `class_enrollments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `class_id` INT(11) NOT NULL,
  `student_id` INT(11) NOT NULL,
  `enrollment_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `status` VARCHAR(50) DEFAULT 'Enrolled',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);



CREATE TABLE `class_sessions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `class_id` INT(11) NOT NULL,
  `session_date` DATE NOT NULL,
  `actual_start_time` TIME DEFAULT NULL,
  `actual_end_time` TIME DEFAULT NULL,
  `room_id` INT(11) DEFAULT NULL,
  `recorded_by_user_id` INT(11) NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`recorded_by_user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
);


CREATE TABLE `class_students` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `class_id` INT(11) NOT NULL,
  `student_id` INT(11) NOT NULL,
  `enrollment_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);


CREATE TABLE `office_hours` (
  `oh_id` INT(11) NOT NULL AUTO_INCREMENT,
  `faculty_id` INT(11) NOT NULL,
  `day_of_week` VARCHAR(10) NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`oh_id`),
  FOREIGN KEY (`faculty_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);


CREATE TABLE `notifications` (
    `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT(11) NOT NULL,
    `message` TEXT NOT NULL,
    `link` VARCHAR(255) DEFAULT NULL,
    `is_read` BOOLEAN NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE `notification_preferences` (
    `user_id` INT(11) NOT NULL PRIMARY KEY,
    `email_notifications` BOOLEAN NOT NULL DEFAULT 1,
    `sms_notifications` BOOLEAN NOT NULL DEFAULT 0,
    `in_app_notifications` BOOLEAN NOT NULL DEFAULT 1,
    `reminder_days_before` INT(11) NOT NULL DEFAULT 1,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);


CREATE TABLE audit_logs (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    action VARCHAR(255) NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    details TEXT,
    IP_address VARCHAR(45),
    user_agent TEXT,
    -- Add a foreign key constraint to link with your users table
    -- Make sure your 'users' table and 'id' column exist and are correctly named
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Sample Data for audit_logs table

-- Admin User (assuming user_id = 1 is an admin)
INSERT INTO audit_logs (user_id, action, timestamp, details, IP_address, user_agent) VALUES
(1, 'Admin Login', '2025-07-28 08:30:00', 'Administrator user logged in successfully.', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36');

INSERT INTO audit_logs (user_id, action, timestamp, details, IP_address, user_agent) VALUES
(1, 'Class Created', '2025-07-28 09:15:22', 'New class "History 101" (HIST101) added by admin.', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36');

INSERT INTO audit_logs (user_id, action, timestamp, details, IP_address, user_agent) VALUES
(1, 'User Role Updated', '2025-07-28 10:05:40', 'Changed role of User ID 5 from Student to Faculty.', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36');

INSERT INTO audit_logs (user_id, action, timestamp, details, IP_address, user_agent) VALUES
(1, 'Room Updated', '2025-07-29 14:30:10', 'Updated capacity of Room 101 to 50.', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15');

-- Faculty User (assuming user_id = 2 is a faculty)
INSERT INTO audit_logs (user_id, action, timestamp, details, IP_address, user_agent) VALUES
(2, 'Faculty Login', '2025-07-29 09:00:15', 'Faculty user "Jane Doe" logged in.', '192.168.1.150', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Firefox/128.0');

INSERT INTO audit_logs (user_id, action, timestamp, details, IP_address, user_agent) VALUES
(2, 'Attendance Recorded', '2025-07-29 10:00:30', 'Recorded attendance for session ID 123 (Class MATH201).', '192.168.1.150', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Firefox/128.0');

INSERT INTO audit_logs (user_id, action, timestamp, details, IP_address, user_agent) VALUES
(2, 'Class Session Created', '2025-07-30 11:45:00', 'Scheduled new session for Class CSC301 on 2025-08-05.', '192.168.1.151', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36');

-- Student User (assuming user_id = 3 is a student)
INSERT INTO audit_logs (user_id, action, timestamp, details, IP_address, user_agent) VALUES
(3, 'Student Login', '2025-07-30 07:45:10', 'Student user "John Smith" logged in.', '192.168.1.200', 'Mozilla/5.0 (Linux; Android 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Mobile Safari/537.36');

INSERT INTO audit_logs (user_id, action, timestamp, details, IP_address, user_agent) VALUES
(3, 'Feedback Submitted', '2025-07-30 08:00:20', 'Submitted General Feedback: "App is very helpful."', '192.168.1.200', 'Mozilla/5.0 (Linux; Android 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Mobile Safari/537.36');

-- Example of a more recent action (using CURRENT_TIMESTAMP)
INSERT INTO audit_logs (user_id, action, details, IP_address, user_agent) VALUES
(1, 'Configuration Update', 'Updated system settings for attendance threshold.', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36');



CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    device VARCHAR(100),
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE attendance_records (
    id INT(11) NOT NULL AUTO_INCREMENT,
    session_id INT(11) NOT NULL,
    student_id INT(11) NOT NULL,
    status ENUM('Present','Absent','Late','Excused','Tardy') NOT NULL DEFAULT 'Absent',
    time_in TIME DEFAULT NULL,
    time_out TIME DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    recorded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (session_id) REFERENCES class_sessions(id),
    FOREIGN KEY (student_id) REFERENCES users(id)
);


