<?php
session_start();
require_once '../../config/db_connect.php';
require_once '../../classes/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_SESSION['user']['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $course = $_POST['course']; // Will now always be set
    $department = $_POST['department']; // Will now always be set

    $user = new User($conn);
    $result = $user->updateUser($id, $name, $email, $course, $department);

    // Also update session
    if ($result) {
        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['email'] = $email;
        $_SESSION['user']['course'] = $course;
        $_SESSION['user']['department'] = $department;
    }

    echo json_encode(["success" => $result]);
    exit;
}
?>