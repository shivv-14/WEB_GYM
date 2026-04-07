<?php
class Gym {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function getGymByOwnerId($ownerId) {
        $stmt = $this->db->prepare("SELECT * FROM gyms WHERE owner_id = ? LIMIT 1");
        if (!$stmt) return false;
        $stmt->bind_param("i", $ownerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $gym = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        return $gym;
    }
    // ...existing code...

    public function createGym($name, $location, $description, $owner_id) {
        $stmt = $this->db->prepare("INSERT INTO gyms (name, location, description, owner_id) VALUES (?, ?, ?, ?)");
        if (!$stmt) return false;
        $stmt->bind_param("sssi", $name, $location, $description, $owner_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function getGym($id) {
        $stmt = $this->db->prepare("SELECT * FROM gyms WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $gym = $result->fetch_assoc();
        $stmt->close();
        return $gym;
    }

    public function updateGym($id, $name, $location, $description) {
        $stmt = $this->db->prepare("UPDATE gyms SET name = ?, location = ?, description = ? WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("sssi", $name, $location, $description, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function deleteGym($id) {
        $stmt = $this->db->prepare("DELETE FROM gyms WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function uploadGymPhoto($gym_id, $photo_path) {
        $stmt = $this->db->prepare("INSERT INTO gym_photos (gym_id, photo_path) VALUES (?, ?)");
        if (!$stmt) return false;
        $stmt->bind_param("is", $gym_id, $photo_path);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function getGymPhotos($gym_id) {
        $stmt = $this->db->prepare("SELECT * FROM gym_photos WHERE gym_id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("i", $gym_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $photos = [];
        while ($row = $result->fetch_assoc()) {
            $photos[] = $row;
        }
        $stmt->close();
        return $photos;
    }

    public function getAllGyms() {
        $result = $this->db->query("SELECT * FROM gyms");
        $gyms = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $gyms[] = $row;
            }
        }
        return $gyms;
    }

    public function getGymReviews($gym_id) {
        $stmt = $this->db->prepare("SELECT * FROM reviews WHERE gym_id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("i", $gym_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }
        $stmt->close();
        return $reviews;
    }

    public function addGymReview($gym_id, $user_id, $rating, $comment) {
        $stmt = $this->db->prepare("INSERT INTO reviews (gym_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
        if (!$stmt) return false;
        $stmt->bind_param("iiis", $gym_id, $user_id, $rating, $comment);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
?>