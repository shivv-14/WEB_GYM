<?php
require_once 'db_connect.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Fetch memberships starting today
$today = date('Y-m-d');
$stmt_start = $conn->prepare("
    SELECT p.*, u.email as user_email, u.name as user_name, u2.email as owner_email, g.gym_name
    FROM payments p 
    JOIN users u ON p.user_id = u.id 
    JOIN gyms g ON p.gym_name = g.gym_name
    JOIN users u2 ON g.user_id = u2.id
    WHERE p.joining_date = ? AND p.payment_status = 'successful'
");
if ($stmt_start === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt_start->bind_param("s", $today);
$stmt_start->execute();
$starting_memberships = $stmt_start->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_start->close();

// Fetch memberships ending today
$stmt_end = $conn->prepare("
    SELECT p.*, u.email as user_email, u.name as user_name, u2.email as owner_email, g.gym_name
    FROM payments p 
    JOIN users u ON p.user_id = u.id 
    JOIN gyms g ON p.gym_name = g.gym_name
    JOIN users u2 ON g.user_id = u2.id
    WHERE p.renewal_date = ? AND p.payment_status = 'successful'
");
if ($stmt_end === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt_end->bind_param("s", $today);
$stmt_end->execute();
$ending_memberships = $stmt_end->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_end->close();

// Initialize PHPMailer
$mail = new PHPMailer(true);
try {
    // Send start date notifications
    foreach ($starting_memberships as $membership) {
        // Fetch SMTP credentials
        $smtp_username = 'your-smtp-email@gmail.com'; // Default SMTP email
        $smtp_password = 'your-smtp-app-password'; // Default SMTP App Password
        $stmt_smtp = $conn->prepare("SELECT smtp_username, smtp_password FROM smtp_credentials WHERE user_id = (SELECT user_id FROM gyms WHERE gym_name = ?)");
        if ($stmt_smtp) {
            $stmt_smtp->bind_param("s", $membership['gym_name']);
            $stmt_smtp->execute();
            $result = $stmt_smtp->get_result();
            if ($result->num_rows > 0) {
                $smtp = $result->fetch_assoc();
                $smtp_username = $smtp['smtp_username'];
                $smtp_password = $smtp['smtp_password'];
            }
            $stmt_smtp->close();
        }

        // SMTP settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom($membership['owner_email'], $membership['gym_name']);
        $mail->addAddress($membership['user_email'], $membership['user_name']);
        $mail->isHTML(true);
        $mail->Subject = 'Your Membership at ' . htmlspecialchars($membership['gym_name']) . ' Has Started!';
        $mail->Body = '
            <h1>Hey ' . htmlspecialchars($membership['user_name']) . ', Welcome to ' . htmlspecialchars($membership['gym_name']) . '!</h1>
            <p>Your membership has officially started today, ' . htmlspecialchars($membership['joining_date']) . '.</p>
            <p><strong>Membership Type:</strong> ' . htmlspecialchars($membership['membership_type']) . '</p>
            <p><strong>Renewal Date:</strong> ' . htmlspecialchars($membership['renewal_date']) . '</p>
            <p>We’re excited to have you on board! Get ready to achieve your fitness goals.</p>';

        $mail->send();
        $mail->clearAddresses();
    }

    // Send end date notifications
    foreach ($ending_memberships as $membership) {
        // Fetch SMTP credentials
        $smtp_username = 'your-smtp-email@gmail.com'; // Default SMTP email
        $smtp_password = 'your-smtp-app-password'; // Default SMTP App Password
        $stmt_smtp = $conn->prepare("SELECT smtp_username, smtp_password FROM smtp_credentials WHERE user_id = (SELECT user_id FROM gyms WHERE gym_name = ?)");
        if ($stmt_smtp) {
            $stmt_smtp->bind_param("s", $membership['gym_name']);
            $stmt_smtp->execute();
            $result = $stmt_smtp->get_result();
            if ($result->num_rows > 0) {
                $smtp = $result->fetch_assoc();
                $smtp_username = $smtp['smtp_username'];
                $smtp_password = $smtp['smtp_password'];
            }
            $stmt_smtp->close();
        }

        // SMTP settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom($membership['owner_email'], $membership['gym_name']);
        $mail->addAddress($membership['user_email'], $membership['user_name']);
        $mail->isHTML(true);
        $mail->Subject = 'Your Membership at ' . htmlspecialchars($membership['gym_name']) . ' Has Ended';
        $mail->Body = '
            <h1>Hey ' . htmlspecialchars($membership['user_name']) . ', Your Membership Has Ended</h1>
            <p>Your membership at ' . htmlspecialchars($membership['gym_name']) . ' ended on ' . htmlspecialchars($membership['renewal_date']) . '.</p>
            <p><strong>Membership Type:</strong> ' . htmlspecialchars($membership['membership_type']) . '</p>
            <p>Please renew your membership to continue enjoying our facilities. Visit our website to make a payment.</p>
            <p>Thank you for being a valued member!</p>';

        $mail->send();
        $mail->clearAddresses();
    }
} catch (Exception $e) {
    // Log the error
    error_log("Notification email failed: {$mail->ErrorInfo}");
}

echo "Notifications sent successfully.";
?>