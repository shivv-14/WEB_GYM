<?php
ob_start();
session_start();
require_once 'db_connect.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gym_member') {
    header("Location: userlogin.php");
    exit();
}

if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed. Please check db_connect.php configuration.");
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$gym_id = $_GET['gym_id'] ?? 0;

$gym = null;
$owner_email = '';
$stmt = $conn->prepare("SELECT g.*, u.email as owner_email FROM gyms g JOIN users u ON g.user_id = u.id WHERE g.id = ?");
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $gym_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $gym = $result->fetch_assoc();
    $owner_email = $gym['owner_email'];
}
$stmt->close();

if (!$gym) {
    die("Invalid gym selected.");
}

$user_email = '';
$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $user_email = $user['email'];
}
$stmt->close();

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $membership_type = $_POST['membership_type'] ?? '';
    $payment_mode = $_POST['payment_mode'] ?? '';
    
    $amount_paid = 0;
    $renewal_date = '';
    $joining_date = date('Y-m-d');
    switch ($membership_type) {
        case '1month':
            $amount_paid = $gym['membership_1month'];
            $renewal_date = date('Y-m-d', strtotime('+1 month'));
            break;
        case '2months':
            $amount_paid = $gym['membership_2months'];
            $renewal_date = date('Y-m-d', strtotime('+2 months'));
            break;
        case '6months':
            $amount_paid = $gym['membership_6months'];
            $renewal_date = date('Y-m-d', strtotime('+6 months'));
            break;
        case '1year':
            $amount_paid = $gym['membership_1year'];
            $renewal_date = date('Y-m-d', strtotime('+1 year'));
            break;
        default:
            $error = "Invalid membership type.";
    }

    if (empty($name) || empty($phone) || empty($payment_mode)) {
        $error = "All fields are required.";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = "Phone number must be 10 digits.";
    } elseif (!in_array($payment_mode, ['cash', 'qr'])) {
        $error = "Invalid payment mode.";
    } else {
        $stmt_phone = $conn->prepare("SELECT id FROM payments WHERE phone = ?");
        if ($stmt_phone === false) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt_phone->bind_param("s", $phone);
        $stmt_phone->execute();
        $result = $stmt_phone->get_result();
        if ($result->num_rows > 0) {
            $error = "Phone number already used.";
            $stmt_phone->close();
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO payments (user_id, gym_name, name, phone, membership_type, amount_paid, payment_mode, payment_status, joining_date, renewal_date) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)");
            if ($stmt_insert === false) {
                $error = "Error preparing payment insertion: " . $conn->error;
                $stmt_phone->close();
            } else {
                $stmt_insert->bind_param("isssssdss", $user_id, $gym['gym_name'], $name, $phone, $membership_type, $amount_paid, $payment_mode, $joining_date, $renewal_date);
                if ($stmt_insert->execute()) {
                    $success = "Payment submitted successfully. Awaiting confirmation.";

                    $smtp_username = 'mywork1430@gmail.com';
                    $smtp_password = 'xzsr dwfl lyrm fnwd';

                    // Email to gym owner
                    $mail_owner = new PHPMailer(true);
                    try {
                        $mail_owner->isSMTP();
                        $mail_owner->Host = 'smtp.gmail.com';
                        $mail_owner->SMTPAuth = true;
                        $mail_owner->Username = $smtp_username;
                        $mail_owner->Password = $smtp_password;
                        $mail_owner->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                        $mail_owner->Port = 465;

                        $mail_owner->setFrom($smtp_username, 'Gym Website');
                        $mail_owner->addAddress($owner_email);
                        $mail_owner->isHTML(true);
                        $mail_owner->Subject = 'New Membership Payment Received';
                        $mail_owner->Body = '
                            <h1>New Membership Payment</h1>
                            <p>A new payment has been submitted for your gym: ' . htmlspecialchars($gym['gym_name']) . '</p>
                            <p><strong>Name:</strong> ' . htmlspecialchars($name) . '</p>
                            <p><strong>Phone:</strong> ' . htmlspecialchars($phone) . '</p>
                            <p><strong>Membership Type:</strong> ' . htmlspecialchars($membership_type) . '</p>
                            <p><strong>Amount Paid:</strong> ₹' . htmlspecialchars($amount_paid) . '</p>
                            <p><strong>Payment Mode:</strong> ' . htmlspecialchars($payment_mode) . '</p>
                            <p><strong>Joining Date:</strong> ' . htmlspecialchars($joining_date) . '</p>
                            <p><strong>Renewal Date:</strong> ' . htmlspecialchars($renewal_date) . '</p>
                            <p>Please verify the payment in your dashboard.</p>';

                        $mail_owner->send();
                    } catch (Exception $e) {
                        $error .= " Failed to send notification email to gym owner. Error: {$mail_owner->ErrorInfo}";
                        error_log("PHPMailer Owner Error: {$mail_owner->ErrorInfo}");
                    }

                    // Email to user
                    $mail_joiner = new PHPMailer(true);
                    try {
                        $mail_joiner->isSMTP();
                        $mail_joiner->Host = 'smtp.gmail.com';
                        $mail_joiner->SMTPAuth = true;
                        $mail_joiner->Username = $smtp_username;
                        $mail_joiner->Password = $smtp_password;
                        $mail_joiner->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                        $mail_joiner->Port = 465;

                        $mail_joiner->setFrom($smtp_username, 'Gym Website');
                        $mail_joiner->addAddress($user_email, $name);
                        $mail_joiner->isHTML(true);
                        $mail_joiner->Subject = 'Your Membership Payment Confirmation';
                        $mail_joiner->Body = '
                            <h1>Thank You for Joining ' . htmlspecialchars($gym['gym_name']) . '!</h1>
                            <p>Your payment has been submitted successfully and is awaiting confirmation.</p>
                            <p><strong>Membership Type:</strong> ' . htmlspecialchars($membership_type) . '</p>
                            <p><strong>Amount Paid:</strong> ₹' . htmlspecialchars($amount_paid) . '</p>
                            <p><strong>Payment Mode:</strong> ' . htmlspecialchars($payment_mode) . '</p>
                            <p><strong>Joining Date:</strong> ' . htmlspecialchars($joining_date) . '</p>
                            <p><strong>Renewal Date:</strong> ' . htmlspecialchars($renewal_date) . '</p>
                            <p>We’ll notify you once your payment is confirmed.</p>';

                        $mail_joiner->send();
                    } catch (Exception $e) {
                        $error .= " Failed to send welcome email to joiner. Error: {$mail_joiner->ErrorInfo}";
                        error_log("PHPMailer Joiner Error: {$mail_joiner->ErrorInfo}");
                    }

                    $stmt_insert->close();
                    header("Location: user_dashboard.php?success=" . urlencode($success));
                    exit();
                } else {
                    $error = "Error submitting payment: " . $stmt_insert->error;
                    $stmt_insert->close();
                }
            }
            $stmt_phone->close();
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
    <title>Gym Website - Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #F5F5F5;
        }
        .navbar {
            background-color: #4B0082;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .navbar a {
            color: #FFFFFF;
            transition: color 0.3s;
        }
        .navbar a:hover {
            color: #FFC107;
        }
        .section {
            padding: 4rem 2rem;
        }
        .form-container {
            max-width: 28rem;
            margin: 0 auto;
            background-color: #FFFFFF;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .btn-primary {
            background-color: #FFC107;
            color: #4B0082;
            padding: 0.75rem 1.5rem;
            border-radius: 9999px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .btn-primary:hover {
            background-color: #FFD700;
        }
    </style>
    <script>
        function updatePaymentDetails() {
            const membershipType = document.querySelector('[name="membership_type"]').value;
            const amountField = document.querySelector('[name="amount_paid"]');
            const renewalField = document.querySelector('[name="renewal_date"]');
            const prices = {
                '1month': <?php echo json_encode($gym['membership_1month'] ?? 0); ?>,
                '2months': <?php echo json_encode($gym['membership_2months'] ?? 0); ?>,
                '6months': <?php echo json_encode($gym['membership_6months'] ?? 0); ?>,
                '1year': <?php echo json_encode($gym['membership_1year'] ?? 0); ?>
            };
            const renewalDates = {
                '1month': '<?php echo date('Y-m-d', strtotime('+1 month')); ?>',
                '2months': '<?php echo date('Y-m-d', strtotime('+2 months')); ?>',
                '6months': '<?php echo date('Y-m-d', strtotime('+6 months')); ?>',
                '1year': '<?php echo date('Y-m-d', strtotime('+1 year')); ?>'
            };
            amountField.value = prices[membershipType] || 0;
            renewalField.value = renewalDates[membershipType] || '';
        }
    </script>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar flex justify-between items-center px-6 py-4">
        <div class="text-2xl font-bold text-white">Gym Website</div>
        <div class="space-x-4">
            <a href="user_dashboard.php" class="hover:text-yellow-400">Dashboard</a>
            <a href="logout.php" class="hover:text-yellow-400">Logout</a>
        </div>
    </nav>

    <!-- Payment Section -->
    <section class="section">
        <div class="form-container">
            <h2 class="text-2xl font-bold mb-6 text-red-600">Join <?php echo htmlspecialchars($gym['gym_name']); ?></h2>
            <?php if ($error): ?>
                <p class="text-red-500 mb-4"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <?php if ($success): ?>
                <p class="text-green-500 mb-4"><?php echo htmlspecialchars($success); ?></p>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700">Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($user_name); ?>" class="w-full p-2 border rounded" readonly>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700">Contact Number</label>
                    <input type="text" name="phone" class="w-full p-2 border rounded" required pattern="[0-9]{10}" placeholder="Enter 10-digit phone number">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700">Membership Type</label>
                    <select name="membership_type" class="w-full p-2 border rounded" required onchange="updatePaymentDetails()">
                        <option value="">Select Membership</option>
                        <option value="1month">1 Month (₹<?php echo $gym['membership_1month'] ?? 0; ?>)</option>
                        <option value="2months">2 Months (₹<?php echo $gym['membership_2months'] ?? 0; ?>)</option>
                        <option value="6months">6 Months (₹<?php echo $gym['membership_6months'] ?? 0; ?>)</option>
                        <option value="1year">1 Year (₹<?php echo $gym['membership_1year'] ?? 0; ?>)</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700">Amount (₹)</label>
                    <input type="number" name="amount_paid" class="w-full p-2 border rounded" readonly>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700">Renewal Date</label>
                    <input type="date" name="renewal_date" class="w-full p-2 border rounded" readonly>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700">Payment Mode</label>
                    <select name="payment_mode" class="w-full p-2 border rounded" required>
                        <option value="">Select Payment Mode</option>
                        <option value="cash">Cash</option>
                        <option value="qr">QR Code</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary w-full">Submit Payment</button>
            </form>
            <a href="user_dashboard.php" class="mt-4 inline-block text-red-600 hover:underline">Back to Dashboard</a>
        </div>
    </section>

    <!-- Footer Section -->
    <section class="section bg-white text-center">
        <p class="text-gray-600">&copy; 2025 Gym Website. All rights reserved.</p>
    </section>
</body>
</html>