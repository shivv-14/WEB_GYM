<?php
require_once 'db_connect.php';

class ReviewController {
    public static function getAverageRating($gymId) {
        global $conn;
        $stmt = $conn->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE gym_id = ?");
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $gymId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result['avg_rating'] ? round($result['avg_rating'], 1) : 0;
    }

    public static function getReviewsByGym($gymId) {
        global $conn;
        $stmt = $conn->prepare("SELECT r.*, u.name as user_name 
                                FROM reviews r 
                                LEFT JOIN users u ON r.user_id = u.id 
                                WHERE r.gym_id = ? 
                                ORDER BY r.created_at DESC");
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $gymId);
        $stmt->execute();
        $result = $stmt->get_result();
        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }
        $stmt->close();
        return $reviews;
    }
}
?>