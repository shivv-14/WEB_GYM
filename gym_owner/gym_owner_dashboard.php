<?php
ob_start();
session_start();
require_once '../db_connect.php';
require_once '../PHPMailer/PHPMailer.php';
require_once '../PHPMailer/SMTP.php';
require_once '../PHPMailer/Exception.php';
require_once '../ReviewController.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gym_owner') {
    header("Location: ../login.php");
    exit();
}

if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed. Please check db_connect.php configuration.");
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$user_email = $_SESSION['email'];
$error = '';
$success = '';

// Fetch gym details
$stmt = $conn->prepare("SELECT * FROM gyms WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$gym = $result->num_rows > 0 ? $result->fetch_assoc() : null;
$stmt->close();

// Handle payment approval or rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_action'])) {
    $payment_id = $_POST['payment_id'] ?? '';
    $action = $_POST['payment_action'] ?? '';

    if (empty($payment_id) || !in_array($action, ['approve', 'reject'])) {
        $error = "Invalid action or payment ID.";
    } else {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $stmt = $conn->prepare("UPDATE payments SET payment_status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $payment_id);
        if ($stmt->execute()) {
            $success = "Payment $status successfully.";
            $stmt_payment = $conn->prepare("SELECT p.*, u.email AS user_email, u.name AS user_name 
                                            FROM payments p 
                                            JOIN users u ON p.user_id = u.id 
                                            WHERE p.id = ?");
            $stmt_payment->bind_param("i", $payment_id);
            $stmt_payment->execute();
            $payment = $stmt_payment->get_result()->fetch_assoc();
            $stmt_payment->close();

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'mywork1430@gmail.com';
                $mail->Password = 'xzsr dwfl lyrm fnwd';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;
                $mail->SMTPDebug = 0;

                $mail->setFrom('mywork1430@gmail.com', 'Gym Website');
                $mail->addAddress($payment['user_email'], $payment['user_name']);
                $mail->isHTML(true);
                $mail->Subject = "Membership Payment $status";
                $mail->Body = "
                    <h1>Membership Payment Update</h1>
                    <p>Your payment for <strong>" . htmlspecialchars($payment['gym_name']) . "</strong> has been $status.</p>
                    <p><strong>Membership Type:</strong> " . htmlspecialchars($payment['membership_type']) . "</p>
                    <p><strong>Amount Paid:</strong> ₹" . htmlspecialchars($payment['amount_paid']) . "</p>
                    <p><strong>Payment Mode:</strong> " . htmlspecialchars($payment['payment_mode']) . "</p>
                    <p><strong>Joining Date:</strong> " . htmlspecialchars($payment['joining_date']) . "</p>
                    <p><strong>Renewal Date:</strong> " . htmlspecialchars($payment['renewal_date']) . "</p>
                    <p>" . ($status === 'approved' ? 'Welcome to the gym! You can now start your fitness journey.' : 'Please contact support for further details.') . "</p>";

                $mail->send();
                $success .= " Email notification sent.";
            } catch (Exception $e) {
                $error .= " Failed to send email. Error: {$mail->ErrorInfo}";
            }
        } else {
            $error = "Error updating payment status: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle review response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond_review'])) {
    $review_id = $_POST['review_id'] ?? '';
    $response = trim($_POST['response'] ?? '');

    if (empty($review_id) || empty($response)) {
        $error = "Response is required.";
    } else {
        $stmt = $conn->prepare("UPDATE reviews SET response = ? WHERE id = ?");
        $stmt->bind_param("si", $response, $review_id);
        if ($stmt->execute()) {
            $success = "Response added to review successfully.";
        } else {
            $error = "Error adding response: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle offer creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_offer']) && $gym) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $discount_percentage = (float)($_POST['discount_percentage'] ?? 0);
    $valid_until = $_POST['valid_until'] ?? '';

    if (empty($title) || empty($valid_until)) {
        $error = "Title and validity date are required.";
    } elseif ($discount_percentage < 0 || $discount_percentage > 100) {
        $error = "Discount percentage must be between 0 and 100.";
    } else {
        $stmt = $conn->prepare("INSERT INTO offers (gym_id, title, description, discount_percentage, valid_until) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issds", $gym['id'], $title, $description, $discount_percentage, $valid_until);
        if ($stmt->execute()) {
            $success = "Offer created successfully.";
        } else {
            $error = "Error creating offer: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle offer deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_offer']) && $gym) {
    $offer_id = $_POST['offer_id'] ?? '';
    $stmt = $conn->prepare("DELETE FROM offers WHERE id = ? AND gym_id = ?");
    $stmt->bind_param("ii", $offer_id, $gym['id']);
    if ($stmt->execute()) {
        $success = "Offer deleted successfully.";
    } else {
        $error = "Error deleting offer: " . $stmt->error;
    }
    $stmt->close();
}

// Handle trainer creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_trainer']) && $gym) {
    $name = trim($_POST['name'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $contact = trim($_POST['contact'] ?? '');

    if (empty($name)) {
        $error = "Trainer name is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO trainers (gym_id, name, specialization, contact) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $gym['id'], $name, $specialization, $contact);
        if ($stmt->execute()) {
            $success = "Trainer added successfully.";
        } else {
            $error = "Error adding trainer: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle trainer deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_trainer']) && $gym) {
    $trainer_id = $_POST['trainer_id'] ?? '';
    $stmt = $conn->prepare("DELETE FROM trainers WHERE id = ? AND gym_id = ?");
    $stmt->bind_param("ii", $trainer_id, $gym['id']);
    if ($stmt->execute()) {
        $success = "Trainer deleted successfully.";
    } else {
        $error = "Error deleting trainer: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch pending payments
$payments = [];
if ($gym) {
    $stmt = $conn->prepare("SELECT p.*, u.email AS user_email, u.name AS user_name 
                            FROM payments p 
                            JOIN users u ON p.user_id = u.id 
                            WHERE p.gym_name = ? AND p.payment_status = 'pending'");
    $stmt->bind_param("s", $gym['gym_name']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
    $stmt->close();
}

// Fetch reviews
$reviews = $gym ? ReviewController::getReviewsByGym($gym['id']) : [];

// Fetch offers
$offers = [];
if ($gym) {
    $stmt = $conn->prepare("SELECT * FROM offers WHERE gym_id = ? ORDER BY valid_until DESC");
    $stmt->bind_param("i", $gym['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $offers[] = $row;
    }
    $stmt->close();
}

// Fetch trainers
$trainers = [];
if ($gym) {
    $stmt = $conn->prepare("SELECT * FROM trainers WHERE gym_id = ?");
    $stmt->bind_param("i", $gym['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $trainers[] = $row;
    }
    $stmt->close();
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Website - Gym Owner Dashboard</title>
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
            z-index: 1000;
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

        .form-group {
            margin-bottom: 2.5rem;
        }

        .form-group h3 {
            color: var(--primary);
            margin-bottom: 1.2rem;
            font-weight: 600;
            font-size: 1.5rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.85rem;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(75, 0, 130, 0.12);
        }

        .btn {
            padding: 0.8rem 1.6rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            background: var(--primary);
            color: #FFFFFF;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #3a0065;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            font-size: 0.95rem;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 1rem;
            text-align: left;
        }

        th {
            background-color: var(--primary);
            color: #FFFFFF;
            font-weight: 600;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .success-message {
            background: #e6ffed;
            color: #006400;
            border-left: 5px solid #28a745;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            border-left: 5px solid #d32f2f;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 620px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .close {
            color: #aaa;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
        }

        .profile-pic {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 1.2rem;
            display: block;
            border: 4px solid var(--primary);
        }

        .summary-table {
            width: 100%;
            margin-top: 1rem;
            border-collapse: collapse;
        }

        .summary-table th,
        .summary-table td {
            padding: 0.8rem;
            border: 1px solid #eee;
        }

        .summary-table th {
            background: var(--primary);
            color: white;
        }

        .clickable-name {
            color: var(--primary);
            cursor: pointer;
            text-decoration: underline;
        }

        .clickable-name:hover {
            color: #3a0065;
        }

        @media (max-width: 768px) {
            .content.shifted {
                margin-left: 0;
            }
            .sidebar {
                width: 220px;
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
        <a href="overview.php">Overview</a>
        <a href="gym_owner_dashboard.php">Dashboard</a>
        <a href="notifications.php">Notifications</a>


        <a href="../logout.php">Logout</a>
    </div>

    <div class="content" id="content">
        <section class="container">
            <div class="header">
                <h1>Dashboard</h1>
                <p class="welcome-message">Welcome back, <?php echo htmlspecialchars($user_name); ?>!</p>
            </div>

            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Pending Payments -->
            <div class="form-group">
                <h3>Pending Payment Requests</h3>
                <?php if (empty($payments)): ?>
                    <p>No pending payment requests.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Member Name</th>
                                <th>Phone</th>
                                <th>Membership Type</th>
                                <th>Amount</th>
                                <th>Payment Mode</th>
                                <th>Joining Date</th>
                                <th>Renewal Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td>
                                        <span class="clickable-name" onclick="openPendingMemberModal(<?php echo $payment['user_id']; ?>, '<?php echo addslashes($payment['user_name']); ?>', <?php echo $gym['id']; ?>)">
                                            <?php echo htmlspecialchars($payment['user_name']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($payment['phone'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($payment['membership_type']); ?></td>
                                    <td>₹<?php echo htmlspecialchars($payment['amount_paid']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['payment_mode']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['joining_date']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['renewal_date']); ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                            <button type="submit" name="payment_action" value="approve" class="btn" style="background:#28a745;margin-right:0.6rem;">Approve</button>
                                            <button type="submit" name="payment_action" value="reject" class="btn" style="background:#dc3545;" onclick="return confirm('Are you sure you want to reject this payment?');">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Reviews -->
            <div class="form-group">
                <h3>Reviews</h3>
                <?php if (empty($reviews)): ?>
                    <p>No reviews yet.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Rating</th>
                                <th>Comment</th>
                                <th>Date</th>
                                <th>Response</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reviews as $review): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($review['name']); ?></td>
                                    <td><?php echo $review['rating']; ?>/5</td>
                                    <td><?php echo nl2br(htmlspecialchars($review['comment'])); ?></td>
                                    <td><?php echo date('d M Y', strtotime($review['created_at'])); ?></td>
                                    <td>
                                        <form method="POST" style="display:flex;gap:0.6rem;align-items:flex-start;">
                                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                            <textarea name="response" placeholder="Type your response..." rows="2" style="width:240px;"><?php echo htmlspecialchars($review['response'] ?? ''); ?></textarea>
                                            <button type="submit" name="respond_review" class="btn" style="padding:0.6rem 1.2rem;font-size:0.95rem;">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Offers & Promotions -->
            <div class="form-group">
                <h3>Offers & Promotions</h3>
                <form method="POST" style="margin-bottom:1.8rem;max-width:620px;">
                    <div style="display:grid;gap:1.2rem;">
                        <input type="text" name="title" placeholder="Offer Title" required>
                        <textarea name="description" placeholder="Description" rows="3"></textarea>
                        <input type="number" name="discount_percentage" placeholder="Discount %" min="0" max="100" step="0.01">
                        <input type="date" name="valid_until" required>
                        <button type="submit" name="create_offer" class="btn" style="width:auto;padding:0.8rem 2rem;">Create Offer</button>
                    </div>
                </form>

                <?php if (!empty($offers)): ?>
                    <div style="display:grid;gap:1.4rem;">
                        <?php foreach ($offers as $offer): ?>
                            <div style="border:1px solid #eee;padding:1.4rem;border-radius:12px;background:#fafafa;">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.8rem;">
                                    <div>
                                        <strong style="color:var(--primary);font-size:1.25rem;"><?php echo htmlspecialchars($offer['title']); ?></strong>
                                        <span style="margin-left:1.2rem;color:#555;font-weight:500;">(<?php echo $offer['discount_percentage']; ?>% off)</span>
                                    </div>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                        <button type="submit" name="delete_offer" class="btn" style="background:#dc3545;padding:0.6rem 1.2rem;" onclick="return confirm('Delete this offer?');">Delete</button>
                                    </form>
                                </div>
                                <p style="margin:0.8rem 0;color:#444;line-height:1.5;"><?php echo nl2br(htmlspecialchars($offer['description'])); ?></p>
                                <small style="color:#777;">Valid until: <?php echo date('d M Y', strtotime($offer['valid_until'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No offers created yet.</p>
                <?php endif; ?>
            </div>

            <!-- Trainers -->
            <div class="form-group">
                <h3>Trainers</h3>
                <form method="POST" style="margin-bottom:1.8rem;max-width:520px;">
                    <div style="display:grid;gap:1.2rem;">
                        <input type="text" name="name" placeholder="Trainer Name" required>
                        <input type="text" name="specialization" placeholder="Specialization">
                        <input type="text" name="contact" placeholder="Contact (phone/email)">
                        <button type="submit" name="add_trainer" class="btn" style="width:auto;padding:0.8rem 2rem;">Add Trainer</button>
                    </div>
                </form>

                <?php if (!empty($trainers)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Specialization</th>
                                <th>Contact</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($trainers as $trainer): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($trainer['name']); ?></td>
                                    <td><?php echo htmlspecialchars($trainer['specialization'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($trainer['contact'] ?? '—'); ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="trainer_id" value="<?php echo $trainer['id']; ?>">
                                            <button type="submit" name="delete_trainer" class="btn" style="background:#dc3545;padding:0.6rem 1.2rem;" onclick="return confirm('Delete this trainer?');">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No trainers added yet.</p>
                <?php endif; ?>
            </div>

        </section>
    </div>

    <!-- Pending Member Details Modal -->
    <div id="pendingMemberModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="pendingModalMemberName"></h4>
                <span class="close" onclick="closeModal('pendingMemberModal')">×</span>
            </div>
            <div id="pendingModalBody"></div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('content').classList.toggle('shifted');
        }

        function openPendingMemberModal(userId, memberName, gymId) {
            const modal = document.getElementById('pendingMemberModal');
            const modalHeader = document.getElementById('pendingModalMemberName');
            const modalBody = document.getElementById('pendingModalBody');

            modalHeader.innerText = memberName;
            modalBody.innerHTML = '<p>Loading member details...</p>';

            fetch('../fetch_member_details.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `user_id=${userId}&gym_id=${gymId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    modalBody.innerHTML = `<p style="color:#c62828;">${data.error}</p>`;
                } else {
                    let html = `
                        <img src="${data.profile_pic || '../uploads/placeholder.jpg'}" class="profile-pic" onerror="this.src='../uploads/placeholder.jpg'">
                        <p><strong>Email:</strong> ${data.email || '—'}</p>
                        <h5 style="margin:1.8rem 0 0.8rem;color:var(--primary);">Attendance History</h5>
                    `;

                    if (data.visits && data.visits.length > 0) {
                        html += '<table class="summary-table"><tr><th>Date</th></tr>';
                        data.visits.forEach(v => {
                            html += `<tr><td>${v.visit_date}</td></tr>`;
                        });
                        html += '</table>';
                    } else {
                        html += '<p>No attendance recorded yet.</p>';
                    }

                    html += '<h5 style="margin:1.8rem 0 0.8rem;color:var(--primary);">Sent Diet & Training Plans</h5>';

                    if (data.plans && data.plans.length > 0) {
                        html += '<table class="summary-table"><tr><th>Diet</th><th>Training</th><th>Sent</th></tr>';
                        data.plans.forEach(p => {
                            html += `<tr>
                                <td>${p.diet_plan.substring(0,45)}${p.diet_plan.length > 45 ? '...' : ''}</td>
                                <td>${p.training_split.substring(0,45)}${p.training_split.length > 45 ? '...' : ''}</td>
                                <td>${p.sent_at}</td>
                            </tr>`;
                        });
                        html += '</table>';
                    } else {
                        html += '<p>No plans sent yet.</p>';
                    }

                    modalBody.innerHTML = html;
                }
            })
            .catch(err => {
                modalBody.innerHTML = '<p style="color:#c62828;">Failed to load data. Please try again.</p>';
            });

            modal.style.display = 'block';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('pendingMemberModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>