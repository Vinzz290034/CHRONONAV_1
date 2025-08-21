<?php
class User {
    private $conn;
    public function __construct($db) {
        $this->conn = $db;
    }

    public function getUserById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }


     // ✅ Add this method
    public function updatePassword($userId, $hashedPassword) {
        $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $userId);
        return $stmt->execute();
    }

    // 🖼️ Update profile image
    public function updateProfileImage($userId, $imagePath) {
        $stmt = $this->conn->prepare("UPDATE users SET profile_img = ? WHERE id = ?");
        $stmt->bind_param("si", $imagePath, $userId);
        return $stmt->execute();
    }

    public function updateUser($id, $name, $email, $course, $department) {
        $stmt = $this->conn->prepare("UPDATE users SET name = ?, email = ?, course = ?, department = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $email, $course, $department, $id);
        return $stmt->execute();
    }
}
?>
