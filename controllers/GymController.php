<?php
class GymController {
    private $gymModel;
    private $reviewModel;
    private $membershipModel;
    private $notificationModel;

    public function __construct() {
        require_once '../models/Gym.php';
        require_once '../models/Review.php';
        require_once '../models/Membership.php';
        require_once '../models/Notification.php';

        $this->gymModel = new Gym();
        $this->reviewModel = new Review();
        $this->membershipModel = new Membership();
        $this->notificationModel = new Notification();
    }

    public function addGym($data) {
        // Validate and add gym to the database
        return $this->gymModel->create($data);
    }

    public function editGym($id, $data) {
        // Validate and update gym details in the database
        return $this->gymModel->update($id, $data);
    }

    public function deleteGym($id) {
        // Delete gym from the database
        return $this->gymModel->delete($id);
    }

    public function getGym($id) {
        // Retrieve gym details by ID
        return $this->gymModel->find($id);
    }

    public function listGyms() {
        // Retrieve all gyms
        return $this->gymModel->getAll();
    }

    public function uploadGymPhoto($gymId, $photo) {
        // Handle photo upload for the gym
        return $this->gymModel->uploadPhoto($gymId, $photo);
    }

    public function submitReview($data) {
        // Validate and submit a review for a gym
        return $this->reviewModel->create($data);
    }

    public function getReviews($gymId) {
        // Retrieve reviews for a specific gym
        return $this->reviewModel->getByGymId($gymId);
    }

    public function getMembershipPricing() {
        // Retrieve membership pricing options
        return $this->membershipModel->getAll();
    }

    public function sendNotification($userId, $message) {
        // Send email notification to a user
        return $this->notificationModel->send($userId, $message);
    }
}
?>