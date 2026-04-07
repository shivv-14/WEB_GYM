<?php
ob_start();
session_start();
require_once 'db_connect.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin.php");
    } elseif ($_SESSION['role'] === 'gym_member') {
        header("Location: user_dashboard.php");
    } elseif ($_SESSION['role'] === 'gym_owner') {
        header("Location: gym_owner_dashboard.php");
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
    <title>Gym Website - Choose Role</title>
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

        .container {
            position: relative;
            width: 100%;
            max-width: 450px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px;
            margin: 150px auto 40px;
            opacity: 0;
            transform: translateX(-100%);
            animation: slideFromLeft 1.5s ease-out forwards;
        }

        @keyframes slideFromLeft {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
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
            font-weight: 700;
            margin-bottom: 10px;
            font-family: 'Arvo', serif;
        }

        .header p {
            color: var(--secondary);
            font-size: 14px;
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
            position: relative;
            overflow: hidden;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-family: 'Arvo', serif;
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

        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
        }

        .particle {
            position: absolute;
            background: var(--primary);
            border-radius: 50%;
            opacity: 0;
        }

        .footer {
            background-color: white;
            text-align: center;
            padding: 1rem;
            color: var(--dark);
            width: 100%;
            position: absolute;
            bottom: 0;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
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
    <div class="navbar">
        <div class="logo">Gym Website</div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="login.php">Login</a>
            <a href="signup.php">Sign Up</a>
        </div>
    </div>

    <div class="background"></div>

    <div class="container">
        <div class="header">
            <h1>What would you like to do?</h1>
        </div>
        <a href="login.php" class="btn">I Own a Gym</a>
        <a href="userlogin.php" class="btn">I Want to Join a Gym</a>
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