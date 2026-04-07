<?php
require_once 'db_connect.php';

if (!isset($_POST['user_id']) || !isset($_POST['gym_id'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$user_id = (int)$_POST['user_id'];
$gym_id = (int)$_POST['gym_id'];

// Fetch user details
$stmt = $conn->prepare("SELECT name, email, profile_pic FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['error' => 'User not found']);
    exit;
}

// Fetch attendance history
$stmt = $conn->prepare("SELECT visit_date FROM visits WHERE gym_id = ? AND user_id = ? ORDER BY visit_date DESC");
$stmt->bind_param("ii", $gym_id, $user_id);
$stmt->execute();
$visits = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch past training plans
$stmt = $conn->prepare("SELECT diet_plan, training_split, sent_at FROM plans WHERE gym_id = ? AND user_id = ? ORDER BY sent_at DESC");
$stmt->bind_param("ii", $gym_id, $user_id);
$stmt->execute();
$plans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$response = [
    'name' => $user['name'],
    'email' => $user['email'],
    'profile_pic' => $user['profile_pic'] ?? 'Uploads/default_profile.jpg',
    'visits' => $visits,
    'plans' => $plans
];

echo json_encode($response);
?>