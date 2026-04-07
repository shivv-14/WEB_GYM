<?php
ob_start();
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

require_once '../db_connect.php';

require_once '../ReviewController.php';


$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$notifications = [];
$error = '';

if ($role === 'gym_member') {
    // Member notifications: payments, plans, etc.
    $stmt = $conn->prepare("SELECT p.gym_name, p.membership_type, p.payment_status, p.joining_date, p.renewal_date FROM payments p WHERE p.user_id = ? ORDER BY p.joining_date DESC");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        $stmt->close();
    }
} elseif ($role === 'gym_owner') {
    // Owner notifications: new reviews, payments, memberships
    if (isset($_SESSION['gym_id'])) {
        $gym_id = $_SESSION['gym_id'];
        $stmt = $conn->prepare("SELECT r.id, r.comment, r.rating, r.created_at, u.name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.gym_id = ? ORDER BY r.created_at DESC LIMIT 10");
        if ($stmt) {
            $stmt->bind_param("i", $gym_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $notifications[] = $row;
            }
            $stmt->close();
        }
        
        // Recent payments
        $stmt = $conn->prepare("SELECT u.name, p.membership_type, p.amount_paid FROM payments p JOIN users u ON p.user_id = u.id WHERE p.gym_name = (SELECT gym_name FROM gyms WHERE id = ?) AND p.payment_status = 'approved' AND p.joining_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY p.joining_date DESC LIMIT 5");
        if ($stmt) {
            $stmt->bind_param("i", $gym_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $notifications[] = ['new_member' => true, 'name' => $row['name'], 'type' => $row['membership_type'], 'amount' => $row['amount_paid']];
            }
            $stmt->close();
        }
    }
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Website - Notifications</title>
    <style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

:root {
    --primary: #4B0082;
    --secondary: #FFC107;
    --dark: #2C2A29;
    --light: #f8f9fa;
    --success: #28a745;
    --warning: #ffc107;
    --danger: #dc3545;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    background-color: var(--light);
    min-height: 100vh;
    padding: 1rem;
}

.container {
    background: white;
    border-radius: 20px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    padding: 2rem;
    max-width: 800px;
    margin: 0 auto;
}

.header {
    text-align: center;
    margin-bottom: 2rem;
}

.header h1 {
    color: var(--primary);
    font-size: 2rem;
    font-weight: 600;
}

.notifications-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.notification {
    padding: 1.5rem;
    border-radius: 12px;
    border-left: 5px solid var(--primary);
    background: #f8f9ff;
}

.notification.new-member {
    border-left-color: var(--success);
    background: #f0fff4;
}

.notification.review {
    border-left-color: var(--warning);
    background: #fff8e1;
}

.notification.payment {
    border-left-color: var(--success);
    background: #f0fff4;
}

.notification h4 {
    color: var(--primary);
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.notification p {
    color: var(--dark);
    margin-bottom: 0.3rem;
}

.timestamp {
    font-size: 0.85rem;
    color: #666;
}

.empty {
    text-align: center;
    color: #666;
    padding: 3rem;
    font-size: 1.1rem;
}

.error {
    background: #ffebee;
    color: #d32f2f;
    border-left: 5px solid #d32f2f;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .container {
        padding: 1.5rem;
        margin: 0.5rem;
    }
}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Notifications</h1>
            <p>Welcome, <?php echo htmlspecialchars($user_name); ?>!</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (empty($notifications)): ?>
            <div class="empty">
                No notifications at the moment. 
                <br>
                <small>Check back later for updates on payments, reviews, and more!</small>
            </div>
        <?php else: ?>
            <div class="notifications-list">
                <?php foreach ($notifications as $notif): ?>
                    <?php if (isset($notif['new_member'])): ?>
                        <div class="notification new-member">
                            <h4>New Member Joined</h4>
                            <p><strong><?php echo htmlspecialchars($notif['name']); ?></strong> joined with <?php echo htmlspecialchars($notif['type']); ?> membership (₹<?php echo $notif['amount']; ?>)</p>
                        </div>
                    <?php elseif (isset($notif['id']) && isset($notif['comment'])): ?>
                        <div class="notification review">
                            <h4>New Review</h4>
                            <p><strong><?php echo htmlspecialchars($notif['name']); ?></strong></p>
                            <p><?php echo nl2br(htmlspecialchars($notif['comment'])); ?></p>
                            <p><strong>Rating:</strong> <?php echo $notif['rating']; ?>/5</p>
                            <div class="timestamp"><?php echo date('d M Y H:i', strtotime($notif['created_at'])); ?></div>
                        </div>
                    <?php else: ?>
                        <div class="notification payment">
                            <h4>Payment Update</h4>
                            <p><strong>Gym:</strong> <?php echo htmlspecialchars($notif['gym_name']); ?></p>
                            <p><strong>Membership:</strong> <?php echo htmlspecialchars($notif['membership_type']); ?></p>
                            <p><strong>Status:</strong> <span style="color:<?php echo $notif['payment_status'] === 'approved' ? 'var(--success)' : 'var(--danger)'; ?>"><?php echo htmlspecialchars($notif['payment_status']); ?></span></p>
                            <div class="timestamp"><?php echo date('d M Y', strtotime($notif['joining_date'])); ?></div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>

