<?php
ob_start();
session_start();
require_once 'db_connect.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/admin.php");
        exit();
    } elseif ($_SESSION['role'] === 'gym_owner') {
                    header("Location: gym_owner/gym_owner_dashboard.php");
                    exit();
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
    if ($user['role'] === 'admin' || $user['role'] === 'gym_owner') {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email']; // <-- ADD THIS LINE
        if ($user['role'] === 'admin') {
                    header("Location: admin/admin.php");
                    exit();
                } elseif ($user['role'] === 'gym_owner') {
header("Location: gym_owner/gym_owner_dashboard.php");
                    exit();
                }
            } else {
                $error = "This is the Gym Owner login page. If you are a gym member, please go to the gym joiner login.";
            }
        } else {
            $error = "Invalid email or password.";
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
    <title>Gym Website - Login</title>
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
            margin-bottom: 20px; /* Adjusted margin */
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

        .form-group input {
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

        .form-group input:focus {
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
        .form-group input:not(:placeholder-shown) + label {
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
        
        /* IMPROVED ERROR MESSAGE STYLE */
        .login-error-message {
            color: #d32f2f;
            text-align: center;
            font-size: 14px;
            margin-bottom: 15px;
            font-weight: 500;
            display: <?php echo $error ? 'block' : 'none'; ?>; /* Controlled by PHP */
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
            <h1>Welcome Back</h1>
            <p>Login to access your gym dashboard</p>
        </div>
        
        <div class="login-error-message">
            <?php echo htmlspecialchars($error); ?>
        </div>

        <form id="loginForm" method="POST">
            <div class="form-group">
                <input type="email" id="email" name="email" placeholder=" " required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                <label for="email">Email</label>
            </div>

            <div class="form-group">
                <input type="password" id="password" name="password" placeholder=" " required>
                <label for="password">Password</label>
            </div>

            <button type="submit" class="btn">Sign In</button>

            <div class="signup-link">
                <p>Don't have an account?</p>
                <a href="signup.php">Sign Up</a>
            </div>
        </form>
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