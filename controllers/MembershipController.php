<?php
class MembershipController {
    private $membershipModel;

    public function __construct() {
        require_once '../models/Membership.php';
        $this->membershipModel = new Membership();
    }

    public function displayMembershipPricing() {
        $pricingOptions = $this->membershipModel->getMembershipPricing();
        include '../views/membership_pricing.php';
    }

    public function addMembershipPricing($data) {
        if ($this->validateMembershipData($data)) {
            $this->membershipModel->addMembership($data);
            header("Location: membership_pricing.php?success=Membership added successfully.");
        } else {
            header("Location: membership_pricing.php?error=Invalid data.");
        }
    }

    public function updateMembershipPricing($id, $data) {
        if ($this->validateMembershipData($data)) {
            $this->membershipModel->updateMembership($id, $data);
            header("Location: membership_pricing.php?success=Membership updated successfully.");
        } else {
            header("Location: membership_pricing.php?error=Invalid data.");
        }
    }

    public function deleteMembershipPricing($id) {
        $this->membershipModel->deleteMembership($id);
        header("Location: membership_pricing.php?success=Membership deleted successfully.");
    }

    private function validateMembershipData($data) {
        return isset($data['name']) && !empty($data['name']) &&
               isset($data['price']) && is_numeric($data['price']) &&
               isset($data['duration']) && !empty($data['duration']);
    }
}
?>