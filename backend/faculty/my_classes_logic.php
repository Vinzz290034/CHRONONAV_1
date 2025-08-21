<?php
// CHRONONAV_WEB_UNO/backend/faculty/my_classes_logic.php (CORRECTED - Reintroduced semester, removed year)

// Ensure session is started if not already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/db_connect.php'; // Your existing MySQLi connection file

// --- Helper Functions ---

// Function to get class offerings assigned to a specific faculty (from your 'classes' table)
function getFacultyClassOfferings($conn, $facultyId) {
    // Corrected SQL: Reintroduced c.semester, but kept year removed
    $sql = "SELECT
                c.class_id,
                c.class_name,
                c.class_code,
                c.semester,
                c.day_of_week,
                c.start_time,
                c.end_time,
                r.room_name
            FROM
                classes c
            LEFT JOIN
                rooms r ON c.room_id = r.id
            WHERE
                c.faculty_id = ?
            ORDER BY
                c.semester DESC, c.day_of_week, c.start_time ASC";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed for getFacultyClassOfferings: " . $conn->error);
        return [];
    }

    $stmt->bind_param('i', $facultyId);
    $stmt->execute();
    $result = $stmt->get_result();

    $assignments = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $assignments[] = $row;
        }
        $result->free();
    } else {
        error_log("Error fetching faculty class offerings: " . $stmt->error);
    }
    $stmt->close();
    return $assignments;
}

// No POST requests are handled directly by this file, it's purely for fetching data.

?>