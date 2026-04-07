<?php
ob_start();
session_start();
require_once 'db_connect.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/admin.php");
    } elseif ($_SESSION['role'] === 'gym_member') {
        header("Location: user_dashboard.php");
    } elseif ($_SESSION['role'] === 'gym_owner') {
        header("Location: gym_owner/gym_owner_dashboard.php");
    }
    exit();
}
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Website - Home</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Arvo:wght@700&display=swap');

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
            overflow-x: hidden;
            background-image:
                radial-gradient(circle at 10% 20%, rgba(75, 0, 130, 0.05) 0%, rgba(75, 0, 130, 0.05) 90%),
                radial-gradient(circle at 90% 80%, rgba(255, 193, 7, 0.05) 0%, rgba(255, 193, 7, 0.05) 90%);
            position: relative;
        }

        .floating-logo {
            position: absolute;
            top: 30px;
            animation: float 6s ease-in-out infinite;
            filter: drop-shadow(0 10px 5px rgba(0,0,0,0.1));
            left: 50%;
            transform: translateX(-50%);
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
            width: 100%;
        }

        .navbar .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #FFFFFF;
        }

        .navbar .nav-links {
            display: flex;
            gap: 1rem;
        }

        .navbar a {
            color: #FFFFFF;
            text-decoration: none;
            transition: color 0.3s;
        }

        .navbar a:hover {
            color: var(--secondary);
        }

        .background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background-image: url('background.png');
            background-size: cover;
            background-position: center;
            z-index: -1;
            opacity: 0;
            animation: slideFromRight 1.5s ease-out forwards;
        }

        @keyframes slideFromRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .hero-section {
            background: linear-gradient(135deg, #4B0082 0%, #6A5ACD 100%);
            color: #FFFFFF;
            padding: 4rem 2rem;
            text-align: center;
            position: relative;
            z-index: 1;
            min-height: 60vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .hero-section h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            font-family: 'Arvo', serif;
        }

        .hero-section p {
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .btn-primary {
            background-color: var(--secondary);
            color: var(--primary);
            padding: 0.75rem 1.5rem;
            border-radius: 9999px;
            font-weight: bold;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary:hover {
            background-color: #FFD700;
        }

        .footer {
            background-color: white;
            text-align: center;
            padding: 1rem;
            color: var(--dark);
            width: 100%;
            position: relative;
            z-index: 1;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }

        @media (max-width: 768px) {
            .hero-section h2 {
                font-size: 2rem;
            }

            .hero-section p {
                font-size: 1rem;
            }

            .floating-logo img {
                height: 100px;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="logo">Gym Website</div>
        <div class="nav-links">
            <a href="question.php">Get Started</a>
            <a href="login.php">Login</a>
            <a href="signup.php">Sign Up</a>
        </div>
    </div>

    <div class="background"></div>

    <section class="hero-section">
        <h2>Welcome to Our Gym</h2>
        <p>Start your fitness journey or manage your gym with ease.</p>
        <a href="question.php" class="btn-primary">Get Started</a>
    </section>

    <div class="footer">
        <p>&copy; 2025 Gym Website. All rights reserved.</p>
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