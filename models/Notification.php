<?php
class Notification {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function createNotification($userId, $message) {
        $stmt = $this->db->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("is", $userId, $message);
        return $stmt->execute();
    }

    public function getUserNotifications($userId) {
        $stmt = $this->db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function deleteNotification($notificationId) {
        $stmt = $this->db->prepare("DELETE FROM notifications WHERE id = ?");
        $stmt->bind_param("i", $notificationId);
        return $stmt->execute();
    }
}
?>