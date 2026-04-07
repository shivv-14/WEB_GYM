<?php
ob_start();
session_start();
require_once 'db_connect.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin.php");
    } elseif ($_SESSION['role'] === 'gym_member') {
        header("Location: gym_joiner/user_dashboard.php");
    } elseif ($_SESSION['role'] === 'gym_owner') {
        header("Location: gym_owner/gym_owner_dashboard.php");
    }
    exit();
}

$error = '';
$success = '';
$show_otp_form = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['otp'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    $db_role = '';
    if ($role === 'Admin') {
        $db_role = 'admin';
    } elseif ($role === 'Gym Owner') {
        $db_role = 'gym_owner';
    } elseif ($role === 'Gym Joiner') {
        $db_role = 'gym_member';
    }

    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $error = "Email already registered.";
        } else {
            $otp = sprintf("%04d", rand(0, 9999));
            $_SESSION['signup_data'] = [
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $db_role,
                'otp' => $otp,
                'otp_expiry' => time() + 60
            ];

            $mail = new PHPMailer(true);
            try {
$mail->SMTPDebug = 2; // Enable debug to see auth error
$mail->isSMTP();
$mail->Host       = 'smtp.gmail.com';           // SMTP server
$mail->SMTPAuth   = true;                       // Enable SMTP authentication
$mail->Username   = 'mywork1430@gmail.com';     // Gmail address
$mail->Password   = 'mezicnnhmdwmswpp';      // New Gmail app password
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Change to STARTTLS
$mail->Port       = 587;                        // Port 587 for STARTTLS

                $mail->setFrom('mywork1430@gmail.com', 'Gym Website');
                $mail->addAddress($email, $name);
                $mail->isHTML(true);
                $mail->Subject = 'Your OTP for Gym Website Signup';
                $mail->Body = '
                    <h1>Welcome to Gym Website!</h1>
                    <p>Your OTP for email verification is: <strong>' . htmlspecialchars($otp) . '</strong></p>
                    <p>Please enter this OTP on the verification page. It is valid for 1 minute.</p>
                    <p>If you did not request this, please ignore this email.</p>';

                $mail->send();
                $success = "Please verify your email. OTP has been sent successfully to your email.";
                $show_otp_form = true;
            } catch (Exception $e) {
                $error = "Failed to send OTP email. Error: {$mail->ErrorInfo}";
                error_log("PHPMailer Signup Error: {$mail->ErrorInfo}");
                unset($_SESSION['signup_data']);
            }
        }
        $stmt->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['otp'])) {
    $entered_otp = trim($_POST['otp'] ?? '');
    if (!isset($_SESSION['signup_data']) || time() > $_SESSION['signup_data']['otp_expiry']) {
        $error = "OTP has expired. Please resend OTP.";
        unset($_SESSION['signup_data']);
        $show_otp_form = true;
    } elseif ($entered_otp === $_SESSION['signup_data']['otp']) {
        $signup_data = $_SESSION['signup_data'];
        error_log("Inserting user: " . print_r($signup_data, true));
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, otp, is_verified) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt === false) {
            $error = "Prepare failed: " . $conn->error;
            error_log("Prepare failed: " . $conn->error);
        } else {
            $is_verified = 1;
            $stmt->bind_param("sssssi", $signup_data['name'], $signup_data['email'], $signup_data['password'], $signup_data['role'], $signup_data['otp'], $is_verified);
            if ($stmt->execute()) {
                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['role'] = $signup_data['role'];
                $_SESSION['name'] = $signup_data['name'];
                $success = "Your email is verified, you are not a robot or fake confirmed.";
                unset($_SESSION['signup_data']);
                if ($signup_data['role'] === 'admin') {
                    header("Location: admin.php");
                } elseif ($signup_data['role'] === 'gym_owner') {
                    header("Location: gym_owner_dashboard.php");
                } elseif ($signup_data['role'] === 'gym_member') {
                    header("Location: user_dashboard.php");
                }
                exit();
            } else {
                $error = "Error inserting user: " . $stmt->error;
                error_log("Insert error: " . $stmt->error);
            }
            $stmt->close();
        }
    } else {
        $error = "Invalid OTP. Please try again.";
        $show_otp_form = true;
    }
}

if (isset($_POST['resend_otp']) && isset($_SESSION['signup_data'])) {
    $otp = sprintf("%04d", rand(0, 9999));
    $_SESSION['signup_data']['otp'] = $otp;
    $_SESSION['signup_data']['otp_expiry'] = time() + 60;

    $mail = new PHPMailer(true);
    try {
$mail->SMTPDebug = 0; // Production mode
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'mywork1430@gmail.com';
$mail->Password = 'mezicnnhmdwmswpp';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
$mail->Port = 465;

        $mail->setFrom('mywork1430@gmail.com', 'Gym Website');
        $mail->addAddress($_SESSION['signup_data']['email'], $_SESSION['signup_data']['name']);
        $mail->isHTML(true);
        $mail->Subject = 'Your New OTP for Gym Website Signup';
        $mail->Body = '
            <h1>Welcome to Gym Website!</h1>
            <p>Your new OTP for email verification is: <strong>' . htmlspecialchars($otp) . '</strong></p>
            <p>Please enter this OTP on the verification page. It is valid for 1 minute.</p>
            <p>If you did not request this, please ignore this email.</p>';

        $mail->send();
        $success = "A new OTP has been sent to your email.";
        $show_otp_form = true;
    } catch (Exception $e) {
        $error = "Failed to resend OTP email. Error: {$mail->ErrorInfo}";
        error_log("PHPMailer Signup Error: {$mail->ErrorInfo}");
        unset($_SESSION['signup_data']);
    }
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Website - Signup</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
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

        .container {
            position: relative;
            width: 100%;
            max-width: 450px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px;
            margin-top: 150px;
            opacity: 0;
            transform: translateY(50px);
            animation: fadeInUp 0.8s 0.4s forwards;
            overflow: hidden;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--primary);
            animation: expandLine 1.2s 0.8s forwards;
            transform-origin: left;
            transform: scaleX(0);
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            color: var(--primary);
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
            opacity: 0;
            animation: fadeIn 0.8s 0.6s forwards;
        }

        .header p {
            color: var(--secondary);
            font-size: 14px;
            opacity: 0;
            animation: fadeIn 0.8s 0.8s forwards;
        }

        .form-group {
            position: relative;
            margin-bottom: 25px;
            opacity: 0;
            transform: translateX(-20px);
            animation: slideIn 0.5s forwards;
        }

        .form-group:nth-child(1) { animation-delay: 1.0s; }
        .form-group:nth-child(2) { animation-delay: 1.2s; }
        .form-group:nth-child(3) { animation-delay: 1.4s; }
        .form-group:nth-child(4) { animation-delay: 1.6s; }

        .form-group input, .form-group select {
            width: 100%;
            padding: 15px 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
            color: var(--dark);
            transition: all 0.3s;
            background-color: rgba(255, 255, 255, 0.9);
            appearance: none;
            -webkit-appearance: none;
        }

        .form-group select {
            cursor: pointer;
        }

        .form-group input:focus, .form-group select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(75, 0, 130, 0.2);
            outline: none;
        }

        .form-group label {
            position: absolute;
            top: 15px;
            left: 20px;
            color: var(--secondary);
            font-size: 16px;
            font-weight: 300;
            transition: all 0.3s;
            pointer-events: none;
            background: white;
            padding: 0 5px;
        }

        .form-group input:focus + label,
        .form-group input:not(:placeholder-shown) + label,
        .form-group select:focus + label,
        .form-group select:not([value=""]) + label {
            top: -10px;
            left: 15px;
            font-size: 12px;
            color: var(--primary);
            background: white;
        }

        .btn {
            width: 100%;
            padding: 15px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.8s 1.6s forwards;
            position: relative;
            overflow: hidden;
        }

        .btn:hover {
            background: #3a0065;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(75, 0, 130, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }

        .btn:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }

        .signup-link {
            text-align: center;
            margin-top: 20px;
            opacity: 0;
            animation: fadeIn 0.8s 1.8s forwards;
        }

        .signup-link p {
            color: var(--secondary);
            font-size: 14px;
            display: inline-block;
            margin-right: 5px;
        }

        .signup-link a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            position: relative;
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .signup-link a:hover {
            background-color: rgba(75, 0, 130, 0.1);
            transform: translateY(-2px);
        }

        .signup-link a:active {
            transform: translateY(0);
        }

        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .particle {
            position: absolute;
            background: var(--primary);
            border-radius: 50%;
            opacity: 0;
        }
        
        .login-error-message {
            color: #d32f2f;
            text-align: center;
            font-size: 14px;
            margin-bottom: 15px;
            font-weight: 500;
            display: <?php echo $error ? 'block' : 'none'; ?>;
        }

        .login-success-message {
            color: green;
            text-align: center;
            font-size: 14px;
            margin-bottom: 15px;
            font-weight: 500;
            display: <?php echo $success ? 'block' : 'none'; ?>;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            to { opacity: 1; }
        }

        @keyframes slideIn {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes expandLine {
            to { transform: scaleX(1); }
        }

        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 1;
            }
            100% {
                transform: scale(40, 40);
                opacity: 0;
            }
        }

        @keyframes particle-float {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            100% {
                transform: translateY(-1000px) rotate(720deg);
                opacity: 0;
            }
        }

        @media (max-width: 768px) {
            .container {
                margin: 150px 20px 40px;
                padding: 30px;
            }

            .floating-logo img {
                height: 100px;
            }
        }
    </style>
</head>
<body>
    <div class="floating-logo">
        <img src="gymlogo.png" alt="Gym Logo">
    </div>

    <div class="container">
        <div class="header">
            <h1>Welcome to Gym Website</h1>
            <p>Sign up to join our community</p>
        </div>
        
        <div class="login-error-message">
            <?php echo htmlspecialchars($error); ?>
        </div>

        <div class="login-success-message">
            <?php echo htmlspecialchars($success); ?>
        </div>

        <?php if ($show_otp_form): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <input type="text" name="otp" id="otp" placeholder=" " required pattern="[0-9]{4}">
                    <label for="otp">Enter OTP</label>
                </div>
                <div class="form-group">
                    <p id="timer" class="timer green">Time remaining: 60 seconds</p>
                </div>
                <button type="submit" class="btn">Verify OTP</button>
                <button type="submit" name="resend_otp" class="btn mt-4">Resend OTP</button>
            </form>
            <script>
                let timeLeft = <?php echo isset($_SESSION['signup_data']) ? $_SESSION['signup_data']['otp_expiry'] - time() : 60; ?>;
                const timerElement = document.getElementById('timer');
                const interval = setInterval(() => {
                    timeLeft--;
                    timerElement.textContent = `Time remaining: ${timeLeft} seconds`;
                    if (timeLeft <= 30 && timeLeft > 10) {
                        timerElement.className = 'timer yellow';
                    } else if (timeLeft <= 10) {
                        timerElement.className = 'timer red';
                    }
                    if (timeLeft <= 0) {
                        clearInterval(interval);
                        timerElement.textContent = 'OTP expired. Please resend OTP.';
                        timerElement.className = 'timer red';
                    }
                }, 1000);
            </script>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <input type="text" name="name" id="name" placeholder=" " required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    <label for="name">Full Name</label>
                </div>
                <div class="form-group">
                    <input type="email" name="email" id="email" placeholder=" " required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    <label for="email">Email</label>
                </div>
                <div class="form-group">
                    <input type="password" name="password" id="password" placeholder=" " required>
                    <label for="password">Password</label>
                </div>
                <div class="form-group">
                    <select name="role" id="role" required>
                        <option value="" selected disabled></option>
                        <option value="Admin" <?php echo isset($_POST['role']) && $_POST['role'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="Gym Owner" <?php echo isset($_POST['role']) && $_POST['role'] === 'Gym Owner' ? 'selected' : ''; ?>>Gym Owner</option>
                        <option value="Gym Joiner" <?php echo isset($_POST['role']) && $_POST['role'] === 'Gym Joiner' ? 'selected' : ''; ?>>Gym Joiner</option>
                    </select>
                    <label for="role">Role</label>
                </div>
                <button type="submit" class="btn">Sign Up</button>
            </form>
            <div class="signup-link">
                <p>Already have an account?</p>
                <a href="login.php">Log in</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="particles" id="particles"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 15;
            for (let i = 0; i < particleCount; i++) { createParticle(); }
            function createParticle() {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                const size = Math.random() * 8 + 2;
                const posX = Math.random() * window.innerWidth;
                const delay = Math.random() * 5;
                const duration = Math.random() * 15 + 10;
                const opacity = Math.random() * 0.4 + 0.1;
                const color = `rgba(${Math.random() > 0.5 ? '75, 0, 130' : '255, 193, 7'}, ${opacity})`;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.left = `${posX}px`;
                particle.style.bottom = '-10px';
                particle.style.background = color;
                particle.style.animation = `particle-float ${duration}s linear ${delay}s infinite`;
                particlesContainer.appendChild(particle);
                setTimeout(() => { particle.remove(); createParticle(); }, duration * 1000);
            }
        });
    </script>
</body>
</html>