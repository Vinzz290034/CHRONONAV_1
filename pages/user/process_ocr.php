<?php
// CHRONONAV_WEB_DOSS/pages/user/process_ocr.php

session_start();
header('Content-Type: application/json');
require_once '../../middleware/auth_check.php';

$user_id = $_SESSION['user']['id'] ?? 0;
$response = ['success' => false, 'error' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $user_id === 0) {
    http_response_code(403);
    $response['error'] = 'Invalid request or user not authenticated.';
    echo json_encode($response);
    exit();
}

if (!isset($_FILES['studyLoadPdf']) || $_FILES['studyLoadPdf']['error'] !== UPLOAD_ERR_OK) {
    $response['error'] = 'Error uploading file. Please ensure the file is a valid PDF and under the size limit.';
    echo json_encode($response);
    exit();
}

$file = $_FILES['studyLoadPdf'];
$temp_dir = '../../uploads/temp_ocr/'; // Directory to store temporary files
$uploaded_filename = uniqid('ocr_', true) . '_' . $user_id . '.pdf';
$target_path = $temp_dir . $uploaded_filename;

// 1. Create temporary directory if it doesn't exist
if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0777, true);
}

// 2. Move uploaded file to a safe, temporary location
if (!move_uploaded_file($file['tmp_name'], $target_path)) {
    $response['error'] = 'Failed to move uploaded file on the server.';
    echo json_encode($response);
    exit();
}


// --- START: TESSERACT OCR & EXTRACTION SIMULATION ---

/* * NOTE: In a live environment with Tesseract installed on the server, 
 * the code here would execute shell commands like:
 * * $tesseract_output = shell_exec("tesseract {$target_path} stdout -l eng pdf");
 * // Followed by complex logic to parse $tesseract_output into the array structure below.
*/

// SIMULATED EXTRACTION (Using data from the user's sample image)
// This array structure is CRITICAL for the dashboard's JavaScript to work correctly.
$extracted_schedule = [
    [
        'sched_no'  => '12617',
        'course_no' => 'LIT 11',
        'time'      => '3:30 PM - 4:30 PM',
        'days'      => 'MWF',
        'room'      => '523',
        'units'     => 3.0,
        'instructor' => '' 
    ],
    [
        'sched_no'  => '12625',
        'course_no' => 'IT-FROLEAN',
        'time'      => '4:30 PM - 5:30 PM',
        'days'      => 'MWF',
        'room'      => '530A',
        'units'     => 3.0,
        'instructor' => ''
    ],
    [
        'sched_no'  => '12641',
        'course_no' => 'IT-BLAI LAB',
        'time'      => '7:01 PM - 8:01 PM',
        'days'      => 'TTH',
        'room'      => '540',
        'units'     => 3.0, // Assign unit value to one entry for the course
        'instructor' => ''
    ],
    [
        'sched_no'  => '12641',
        'course_no' => 'IT-BLAI LEC',
        'time'      => '8:01 PM - 9:31 PM',
        'days'      => 'TTH',
        'room'      => '526',
        'units'     => 0.0, // Unit value is zero for the second entry (lecture part)
        'instructor' => ''
    ],
];

// 3. SIMULATED PDF GENERATION FOR DOWNLOAD LINK
// In a real scenario, you would use a PHP library (like FPDF or TCPDF) 
// to create a PDF document from the $extracted_schedule data, save it 
// to a web-accessible folder, and get the relative URL.

$extracted_pdf_filename = "extracted_schedule_" . $user_id . "_" . time() . ".pdf";
// The URL must be relative to the document root for the browser to access it.
$extracted_pdf_url = "../../uploads/temp_schedules/" . $extracted_pdf_filename; 

// Note: For this feature to actually work, you must ensure the directory 
// '../../uploads/temp_schedules/' exists and has write permissions (0777).

// --- END: TESSERACT OCR & EXTRACTION SIMULATION ---


if (empty($extracted_schedule)) {
    // Clean up the uploaded file if extraction failed
    unlink($target_path); 
    $response['error'] = 'OCR failed to extract any schedule data. Please try another PDF or manually enter your schedule.';
    echo json_encode($response);
    exit();
}

// 4. Final Success Response
// We don't unlink the file here, as the user might want to download it in step 2.
$response = [
    'success' => true,
    'message' => 'Document processed successfully!',
    'schedule' => $extracted_schedule,
    'extracted_pdf_url' => $extracted_pdf_url // This provides the download link to the front-end
];

echo json_encode($response);
?>