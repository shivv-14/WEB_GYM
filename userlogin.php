<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = 'gym_member'");
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            header("Location: gym_joiner/user_dashboard.php");
            exit();
        } else {
            $error = "Invalid email or password.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Website - Gym Joiner Login</title>
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

        .login-error-message {
            color: #d32f2f;
            text-align: center;
            font-size: 14px;
            margin-bottom: 15px;
            font-weight: 500;
            padding: 10px;
            background: rgba(211, 47, 47, 0.1);
            border-radius: 8px;
            border-left: 4px solid #d32f2f;
            <?php echo $error ? '' : 'display: none;'; ?>
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
        }

        .btn:hover {
            background: #3a0065;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(75, 0, 130, 0.3);
        }

        .links {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            opacity: 0;
            animation: fadeIn 0.8s 1.8s forwards;
        }

        .links a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            font-size: 14px;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .links a:hover {
            background-color: rgba(75, 0, 130, 0.1);
            transform: translateY(-1px);
        }

        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }

        @keyframes fadeInUp {
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeIn {
            to { opacity: 1; }
        }

        @keyframes slideIn {
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes expandLine {
            to { transform: scaleX(1); }
        }

        @media (max-width: 768px) {
            .container {
                margin: 150px 20px 40px;
                padding: 30px;
            }
            .floating-logo img { height: 100px; }
        }
    </style>
</head>
<body>
    <div class="floating-logo">
        <img src="gymlogo.png" alt="Gym Logo">
    </div>

    <div class="container">
        <div class="header">
            <h1>Gym Member Login</h1>
            <p>Access your gym membership dashboard</p>
        </div>
        
        <?php if ($error): ?>
        <div class="login-error-message">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

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
        </form>

        <div class="links">
            <a href="signup.php">Create Account</a>
            <a href="gym_owner/profile.php">Gym Owner Login</a>
        </div>
    </div>

    <div class="particles" id="particles"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const particlesContainer = document.getElementById('particles');
            function createParticle() {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                const size = Math.random() * 8 + 2;
                const posX = Math.random() * window.innerWidth;
                const delay = Math.random() * 5;
                const duration = Math.random() * 15 + 10;
                particle.style.cssText = `
                    width: ${size}px; height: ${size}px;
                    left: ${posX}px; bottom: -10px;
                    background: rgba(${Math.random() > 0.5 ? '75,0,130' : '255,193,7'}, ${Math.random()*0.4+0.1});
                    animation: particle-float ${duration}s linear ${delay}s infinite;
                `;
                particlesContainer.appendChild(particle);
                setTimeout(() => { particle.remove(); createParticle(); }, duration * 1000);
            }
            for (let i = 0; i < 15; i++) createParticle();
            
            // Add particle-float animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes particle-float {
                    0% { transform: translateY(0) rotate(0deg); opacity: 0; }
                    10% { opacity: 1; }
                    100% { transform: translateY(-1000px) rotate(720deg); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>
