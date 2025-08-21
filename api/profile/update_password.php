<?php
session_start();
require_once '../../config/db_connect.php';
require_once '../../classes/User.php';

$userId = $_SESSION['user']['id'];

$current = $_POST['current_password'];
$new = $_POST['new_password'];
$confirm = $_POST['confirm_password'];

$stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!password_verify($current, $row['password'])) {
    echo json_encode(["success" => false, "message" => "Current password is incorrect."]);
    exit;
}

if ($new !== $confirm) {
    echo json_encode(["success" => false, "message" => "New passwords do not match."]);
    exit;
}

$hashed = password_hash($new, PASSWORD_DEFAULT);
$user = new User($conn);
$user->updatePassword($userId, $hashed);
echo json_encode(["success" => true]);
?>
