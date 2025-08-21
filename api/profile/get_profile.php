<?php
session_start();
require_once '../../config/db_connect.php';
require_once '../../classes/User.php';

$user = new User($conn);
$data = $user->getUserById($_SESSION['user']['id']);
echo json_encode($data);
?>
