<?php
session_start();
require_once '../../config/db_connect.php';
require_once '../../classes/User.php';

$userId = $_SESSION['user']['id'];
$userClass = new User($conn);

if ($_FILES['profile_img']) {
    $file = $_FILES['profile_img'];
    $filename = uniqid() . "_" . basename($file['name']);
    $targetPath = "../../uploads/profiles/" . $filename;
    $dbPath = "uploads/profiles/" . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $userClass->updateProfileImage($userId, $dbPath);
        $_SESSION['user']['profile_img'] = $dbPath;
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false]);
    }
}
?>
