<?php
ob_start();
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../db_connect.php';
require_once '../PHPMailer/PHPMailer.php';
require_once '../PHPMailer/SMTP.php';
require_once '../PHPMailer/Exception.php';
require_once '../ReviewController.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$user_email = $_SESSION['email'] ?? '';
$notifications = [];
$error = '';

// ────────────────────────────────────────────────
//   NOTIFICATIONS LOGIC (same as your original)
// ────────────────────────────────────────────────
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
    // Owner notifications: new reviews, recent approved payments
    $gym = null;
    $stmt = $conn->prepare("SELECT * FROM gyms WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $gym = $result->fetch_assoc();
    }
    $stmt->close();

    if ($gym) {
        // Recent reviews
        $stmt = $conn->prepare("SELECT r.id, r.comment, r.rating, r.created_at, u.name 
                                FROM reviews r 
                                JOIN users u ON r.user_id = u.id 
                                WHERE r.gym_id = ? 
                                ORDER BY r.created_at DESC LIMIT 10");
        $stmt->bind_param("i", $gym['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        $stmt->close();

        // Recent approved payments (last 7 days)
        $stmt = $conn->prepare("SELECT u.name, p.membership_type, p.amount_paid 
                                FROM payments p 
                                JOIN users u ON p.user_id = u.id 
                                WHERE p.gym_name = ? 
                                AND p.payment_status = 'approved' 
                                AND p.joining_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                                ORDER BY p.joining_date DESC LIMIT 5");
        $stmt->bind_param("s", $gym['gym_name']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $notifications[] = ['new_member' => true, 'name' => $row['name'], 'type' => $row['membership_type'], 'amount' => $row['amount_paid']];
        }
        $stmt->close();
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
            overflow-x: hidden;
            background-image:
                radial-gradient(circle at 10% 20%, rgba(75, 0, 130, 0.05) 0%, rgba(75, 0, 130, 0.05) 90%),
                radial-gradient(circle at 90% 80%, rgba(255, 193, 7, 0.05) 0%, rgba(255, 193, 7, 0.05) 90%);
        }

        .floating-logo {
            position: absolute;
            top: 30px;
            animation: float 6s ease-in-out infinite;
            filter: drop-shadow(0 10px 5px rgba(0,0,0,0.1));
            z-index: 10;
        }

        .floating-logo img {
            height: 120px;
            width: auto;
            object-fit: contain;
        }

        .navbar {
            background-color: var(--primary);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
        }

        .navbar .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #FFFFFF;
        }

        .navbar .menu-toggle {
            font-size: 1.5rem;
            color: #FFFFFF;
            cursor: pointer;
        }

        .sidebar {
            width: 250px;
            background-color: var(--primary);
            color: #FFFFFF;
            position: fixed;
            top: 60px;
            bottom: 0;
            left: -250px;
            transition: left 0.3s ease;
            padding: 1rem;
            z-index: 999;
        }

        .sidebar.active {
            left: 0;
        }

        .sidebar .profile-circle {
            background-color: var(--secondary);
            color: var(--primary);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 2rem;
            margin: 0 auto 1rem;
            cursor: pointer;
        }

        .sidebar a {
            display: block;
            padding: 0.75rem 1rem;
            color: #FFFFFF;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .sidebar a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .content {
            margin-left: 0;
            transition: margin-left 0.3s ease;
            width: 100%;
            padding: 2rem;
        }

        .content.shifted {
            margin-left: 250px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            color: var(--primary);
            font-size: 2.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .welcome-message {
            color: var(--dark);
            font-size: 1.25rem;
            font-weight: 500;
        }

        .notifications-list {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .notification {
            padding: 1.4rem;
            border-radius: 12px;
            border-left: 5px solid var(--primary);
            background: #f8f9ff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .notification.new-member {
            border-left-color: #28a745;
            background: #f0fff4;
        }

        .notification.review {
            border-left-color: #ffc107;
            background: #fff8e1;
        }

        .notification.payment {
            border-left-color: #17a2b8;
            background: #e9f7ff;
        }

        .notification h4 {
            color: var(--primary);
            margin-bottom: 0.6rem;
            font-weight: 600;
            font-size: 1.15rem;
        }

        .notification p {
            color: var(--dark);
            margin-bottom: 0.4rem;
            line-height: 1.5;
        }

        .timestamp {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.6rem;
        }

        .empty {
            text-align: center;
            color: #666;
            padding: 4rem 1rem;
            font-size: 1.15rem;
            background: #f8f9fa;
            border-radius: 12px;
        }

        .error {
            background: #ffebee;
            color: #c62828;
            border-left: 5px solid #d32f2f;
            padding: 1.2rem;
            border-radius: 8px;
            margin-bottom: 1.8rem;
            text-align: center;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @media (max-width: 768px) {
            .content.shifted {
                margin-left: 0;
            }
            .sidebar {
                width: 220px;
            }
            .container {
                padding: 1.6rem;
            }
        }
    </style>
</head>
<body>

    <div class="floating-logo">
        <img src="../gymlogo.png" alt="Gym Logo">
    </div>

    <nav class="navbar">
        <div class="menu-toggle" onclick="toggleSidebar()">☰</div>
        <div class="logo">Gym Website</div>
    </nav>

    <div class="sidebar" id="sidebar">
        <div class="profile-circle" onclick="window.location.href='profile.php'" style="cursor:pointer;">
            <?php 
            $user_pic = $_SESSION['profile_pic'] ?? '';
            if ($user_pic): ?>
                <img src="../<?php echo htmlspecialchars($user_pic); ?>" alt="Profile" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
            <?php else: ?>
                <?php echo htmlspecialchars(strtoupper(substr($user_name, 0, 1))); ?>
            <?php endif; ?>
        </div>

        <?php if ($role === 'gym_owner'): ?>
            <a href="overview.php">Overview</a>
            <a href="gym_owner_dashboard.php">Dashboard</a>
        <?php endif; ?>

        <a href="notifications.php">Notifications</a>

        <?php if ($role === 'gym_member'): ?>
            <a href="my_membership.php">My Membership</a>
            <a href="plans.php">Diet & Training Plans</a>
        <?php endif; ?>

        <a href="../logout.php">Logout</a>
    </div>

    <div class="content" id="content">
        <section class="container">
            <div class="header">
                <h1>Notifications</h1>
                <p class="welcome-message">Welcome back, <?php echo htmlspecialchars($user_name); ?>!</p>
            </div>

            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (empty($notifications)): ?>
                <div class="empty">
                    No notifications at the moment.<br>
                    <small>Check back later for updates on payments, reviews, memberships and more!</small>
                </div>
            <?php else: ?>
                <div class="notifications-list">
                    <?php foreach ($notifications as $notif): ?>
                        <?php if (isset($notif['new_member'])): ?>
                            <div class="notification new-member">
                                <h4>New Member Joined</h4>
                                <p><strong><?php echo htmlspecialchars($notif['name']); ?></strong> joined with <?php echo htmlspecialchars($notif['type']); ?> membership (₹<?php echo number_format($notif['amount'], 2); ?>)</p>
                            </div>
                        <?php elseif (isset($notif['id']) && isset($notif['comment'])): ?>
                            <div class="notification review">
                                <h4>New Review</h4>
                                <p><strong><?php echo htmlspecialchars($notif['name']); ?></strong></p>
                                <p><?php echo nl2br(htmlspecialchars($notif['comment'])); ?></p>
                                <p><strong>Rating:</strong> <?php echo $notif['rating']; ?>/5</p>
                                <div class="timestamp"><?php echo date('d M Y • H:i', strtotime($notif['created_at'])); ?></div>
                            </div>
                        <?php else: ?>
                            <div class="notification payment">
                                <h4>Payment Update</h4>
                                <p><strong>Gym:</strong> <?php echo htmlspecialchars($notif['gym_name']); ?></p>
                                <p><strong>Membership:</strong> <?php echo htmlspecialchars($notif['membership_type']); ?></p>
                                <p><strong>Status:</strong> 
                                    <span style="color:<?php echo $notif['payment_status'] === 'approved' ? '#28a745' : '#dc3545'; ?>; font-weight:600;">
                                        <?php echo ucfirst($notif['payment_status']); ?>
                                    </span>
                                </p>
                                <div class="timestamp"><?php echo date('d M Y', strtotime($notif['joining_date'])); ?></div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('content').classList.toggle('shifted');
        }
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>