<?php
// pages/user/process_ocr.php

header('Content-Type: application/json');

// Check for file upload
if (!isset($_FILES['studyLoadPdf']) || $_FILES['studyLoadPdf']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error.']);
    exit;
}

$uploadDir = '../../uploads/';
// Create the upload directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$uploadedFile = $uploadDir . uniqid() . '-' . basename($_FILES['studyLoadPdf']['name']);

// Move the uploaded file to a temporary directory
if (!move_uploaded_file($_FILES['studyLoadPdf']['tmp_name'], $uploadedFile)) {
    echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file.']);
    exit;
}

// === OCR Processing Logic with Tesseract ===
// This part is conceptual. You need to have Tesseract installed on your server
// and configured to be callable from the command line.

// Path to Tesseract executable (adjust if necessary)
$tesseractPath = 'tesseract';

// Output file path (without extension)
$outputFile = $uploadDir . uniqid();

// Command to run Tesseract. The `-l eng` flag specifies the language.
$command = "{$tesseractPath} " . escapeshellarg($uploadedFile) . " " . escapeshellarg($outputFile);

// Execute the command
exec($command, $output, $return_var);

if ($return_var !== 0) {
    // Clean up temporary files
    if (file_exists($uploadedFile)) unlink($uploadedFile);
    if (file_exists($outputFile . '.txt')) unlink($outputFile . '.txt');

    echo json_encode(['success' => false, 'error' => 'Tesseract command failed. Check server logs.']);
    exit;
}

$extractedText = file_get_contents($outputFile . '.txt');
if ($extractedText === false) {
    // Clean up temporary files
    if (file_exists($uploadedFile)) unlink($uploadedFile);
    if (file_exists($outputFile . '.txt')) unlink($outputFile . '.txt');

    echo json_encode(['success' => false, 'error' => 'Failed to read OCR output.']);
    exit;
}

// Clean up temporary files after processing
if (file_exists($uploadedFile)) unlink($uploadedFile);
if (file_exists($outputFile . '.txt')) unlink($outputFile . '.txt');


// === Data Parsing Logic ===
// Use regular expressions to find patterns like "SCHED. NO.", "TIME", etc.
$lines = explode("\n", $extractedText);
$schedule = [];
$headerFound = false;

// Regex patterns to match the schedule data based on the provided image
$headerPattern = '/SCHED\.\s*NO\.\s*COURSE\s*NO\.\s*TIME\s*DAYS\s*ROOM\s*UNITS/';

// This regex pattern is a guess based on the image provided and may need adjustment.
// It looks for a sequence of data points in a line:
// 1. Sched. No. (e.g., 12617)
// 2. Course No. (e.g., LIT 11) - Allows for course numbers with spaces and "LAB"
// 3. Time (e.g., 7:30 - 9:00 PM) - Matches various time formats
// 4. Days (e.g., MWF) - Matches common day codes
// 5. Room (e.g., 523) - Matches room numbers/codes
// 6. Units (e.g., 3) - Matches a single digit
$dataPattern = '/^\s*(\d+)\s+([\w\s-]+)\s+([\d:-]+\s*[AP]M)\s+([A-Z]+)\s+([A-Z\d\s]+)\s+(\d+)/';

foreach ($lines as $line) {
    if (!$headerFound) {
        if (preg_match($headerPattern, $line)) {
            $headerFound = true;
            continue;
        }
    } else {
        if (preg_match($dataPattern, trim($line), $matches)) {
            $schedule[] = [
                'sched_no' => trim($matches[1]),
                'course_no' => trim($matches[2]),
                'time' => trim($matches[3]),
                'days' => trim($matches[4]),
                'room' => trim($matches[5]),
                'units' => trim($matches[6])
            ];
        }
    }
}

if (!empty($schedule)) {
    echo json_encode(['success' => true, 'schedule' => $schedule]);
} else {
    echo json_encode(['success' => false, 'error' => 'Could not find schedule data in the document. The document format may be unsupported.']);
}
?>