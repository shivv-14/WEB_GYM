<?php
class ReviewController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function submitReview($userId, $gymId, $rating, $comment) {
        $stmt = $this->db->prepare("INSERT INTO reviews (user_id, gym_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiis", $userId, $gymId, $rating, $comment);
        return $stmt->execute();
    }

    public function getReviewsByGym($gymId) {
        $stmt = $this->db->prepare("SELECT r.rating, r.comment, r.created_at, u.name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.gym_id = ? ORDER BY r.created_at DESC");
        $stmt->bind_param("i", $gymId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getAverageRating($gymId) {
        $stmt = $this->db->prepare("SELECT AVG(rating) as average_rating FROM reviews WHERE gym_id = ?");
        $stmt->bind_param("i", $gymId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['average_rating'];
    }
}
?>