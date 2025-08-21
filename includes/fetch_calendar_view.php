<?php
// includes/fetch_calendar_view.php
session_start();
require_once '../middleware/auth_check.php';
require_once '../config/db_connect.php';

// Check if the user is logged in and has one of the permitted roles
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'faculty', 'user'])) {
    http_response_code(401);
    echo "Unauthorized access.";
    exit();
}

$view = isset($_GET['view']) ? $_GET['view'] : 'month';
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$selected_timestamp = strtotime($selected_date);

switch ($view) {
    case 'year':
        $selected_year = date('Y', $selected_timestamp);
        include 'views/year_view.php'; // Correct path
        break;
    case 'week':
        include 'views/week_view.php'; // Correct path
        break;
    case 'day':
        include 'views/day_view.php'; // Correct path
        break;
    case 'month':
    default:
        $current_month = date('m', $selected_timestamp);
        $current_year = date('Y', $selected_timestamp);
        $first_day_of_month = strtotime($current_year . '-' . $current_month . '-01');
        $num_days_in_month = date('t', $first_day_of_month);
        $first_day_of_week = date('N', $first_day_of_month);
        break;
}
?>