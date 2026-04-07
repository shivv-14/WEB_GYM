<?php
ob_start();
session_start();
require_once 'db_connect.php';

// Redirect if not logged in or not a gym member
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'gym_member') {
    header("Location: userlogin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? '';
$user_email = $_SESSION['email'] ?? '';
$error = '';
$success = isset($_GET['success']) ? $_GET['success'] : '';

// Fetch user details
$stmt_user = $conn->prepare("SELECT name, email, profile_pic FROM users WHERE id = ?");
if (!$stmt_user) {
    die("Prepare failed: " . $conn->error);
}
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

if ($user) {
    $user_name = $user['name'];
    $user_email = $user['email'];
    $profile_pic = $user['profile_pic'] ?? 'Uploads/default_profile.jpg';
    $_SESSION['name'] = $user_name;
    $_SESSION['email'] = $user_email;
} else {
    $error = "User not found.";
    session_destroy();
    header("Location: userlogin.php");
    exit();
}

// Fetch all gyms with main image
$district_filter = $_GET['district'] ?? '';
$gyms = [];
$query = "SELECT g.* FROM gyms g";
if ($district_filter) {
    $query .= " WHERE g.district = ?";
}
$stmt_gyms = $conn->prepare($query);
if (!$stmt_gyms) {
    die("Prepare failed: " . $conn->error);
}
if ($district_filter) {
    $stmt_gyms->bind_param("s", $district_filter);
}
$stmt_gyms->execute();
$result = $stmt_gyms->get_result();
while ($row = $result->fetch_assoc()) {
    $stmt_img = $conn->prepare("SELECT image_path FROM gym_images WHERE gym_id = ? ORDER BY uploaded_at DESC LIMIT 1");
    if ($stmt_img) {
        $stmt_img->bind_param("i", $row['id']);
        $stmt_img->execute();
        $img_result = $stmt_img->get_result();
        $img_row = $img_result->fetch_assoc();
        $row['main_image'] = $img_row ? $img_row['image_path'] : $row['main_image_path'];
        $stmt_img->close();
    } else {
        $row['main_image'] = $row['main_image_path'];
    }
    $gyms[] = $row;
}
$stmt_gyms->close();

$districts = [
    'Adilabad', 'Bhadradri Kothagudem', 'Hyderabad', 'Jagtial', 'Jangaon',
    'Jayashankar Bhupalpally', 'Jogulamba Gadwal', 'Kamareddy', 'Karimnagar',
    'Khammam', 'Komaram Bheem', 'Mahabubnagar', 'Mancherial',
    'Medak', 'Medchal Malkajgiri', 'Mulugu', 'Nagarkurnool', 'Nalgonda',
    'Narayanpet', 'Nirmal', 'Nizamabad', 'Peddapalli', 'Rajanna Sircilla',
    'Rangareddy', 'Sangareddy', 'Siddipet', 'Suryapet', 'Vikarabad',
    'Wanaparthy', 'Warangal Rural', 'Warangal Urban', 'Yadadri Bhuvanagiri'
];

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Website - Gym Joiner Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4B0082; /* Violet */
            --secondary: #FFC107; /* Yellow */
            --dark: #2C2A29;
            --light: #f8f9fa;
            --gradient-top-left: rgba(75, 0, 130, 1);
            --gradient-top-right: rgba(255, 193, 7, 1);
            --gradient-bottom-left: rgba(0, 128, 0, 1);
            --gradient-bottom-right: rgba(255, 99, 71, 1);
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
            left: 30px;
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
            display: block;
        }

        .sidebar {
            width: 250px;
            background-color: var(--primary);
            color: #FFFFFF;
            position: fixed;
            top: 0;
            bottom: 0;
            left: -250px;
            transition: left 0.3s ease;
            padding: 2rem 1rem;
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
        }

        .sidebar .profile-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
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

        .content.active {
            margin-left: 250px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            animation: fadeInUp 0.8s 0.4s forwards;
        }

        .header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .header h1 {
            color: var(--primary);
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            opacity: 0;
            animation: fadeIn 0.8s 0.6s forwards;
        }

        .form-group {
            margin-bottom: 1.5rem;
            opacity: 0;
            transform: translateX(-20px);
            animation: slideIn 0.5s forwards;
        }

        .form-group:nth-child(1) { animation-delay: 0.5s; }
        .form-group:nth-child(2) { animation-delay: 0.7s; }
        .form-group:nth-child(3) { animation-delay: 0.9s; }

        .form-group label {
            display: block;
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            color: var(--dark);
            transition: all 0.3s;
        }

        .form-group input:focus, .form-group select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(75, 0, 130, 0.2);
            outline: none;
        }

        .btn {
            width: 100%;
            padding: 0.75rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            background: var(--primary);
            color: #FFFFFF;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(75, 0, 130, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .gym-card {
            display: flex;
            align-items: center;
            border-radius: 10px;
            overflow: hidden;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
            background: linear-gradient(
                135deg,
                var(--gradient-top-left) 0%,
                var(--gradient-top-right) 25%,
                var(--gradient-bottom-left) 75%,
                var(--gradient-bottom-right) 100%
            );
        }

        .gym-card.dark {
            background: var(--gradient-top-left);
            color: #ffffff;
        }

        .gym-card.dark .btn {
            background: #ffffff;
            color: var(--gradient-top-left);
        }

        .gym-card.light {
            background: #ffffff;
            color: var(--dark);
        }

        .gym-card.light .btn {
            background: var(--gradient-top-left);
            color: #ffffff;
        }

        .gym-card img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 10px;
            margin-left: 1rem;
        }

        .gym-card .info {
            flex-grow: 1;
            overflow: hidden;
        }

        .gym-card .info h4 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .gym-card .info p {
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .gym-card .info a {
            color: var(--secondary);
            text-decoration: none;
            margin-right: 1rem;
            font-size: 0.9rem;
        }

        .gym-card .info a:hover {
            text-decoration: underline;
        }

        .error-message {
            color: #d32f2f;
            text-align: center;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            font-weight: 500;
            display: <?php echo $error ? 'block' : 'none'; ?>;
        }

        .success-message {
            color: green;
            text-align: center;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            font-weight: 500;
            display: <?php echo $success ? 'block' : 'none'; ?>;
        }

        .footer {
            text-align: center;
            padding: 1rem;
            background-color: var(--primary);
            color: #FFFFFF;
            position: fixed;
            bottom: 0;
            width: 100%;
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

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }

        @media (max-width: 767px) {
            .navbar .menu-toggle { display: block; }
            .sidebar { left: -250px; }
            .sidebar.active { left: 0; }
            .content.active { margin-left: 0; }
            .gym-card {
                flex-direction: column;
                text-align: center;
                width: 100%;
                height: auto;
            }
            .gym-card img {
                margin-left: 0;
                margin-bottom: 1rem;
            }
        }

        @media (min-width: 768px) {
            .sidebar { left: -250px; }
            .sidebar.active { left: 0; }
            .content.active { margin-left: 250px; }
        }
    </style>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');
            sidebar.classList.toggle('active');
            content.classList.toggle('active');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const particlesContainer = document.createElement('div');
            particlesContainer.classList.add('particles');
            particlesContainer.id = 'particles';
            document.body.appendChild(particlesContainer);

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

        const style = document.createElement('style');
        style.textContent = `
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
            @keyframes particle-float {
                0% { transform: translateY(0) rotate(0deg); opacity: 0; }
                10% { opacity: 1; }
                100% { transform: translateY(-1000px) rotate(720deg); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>
</head>
<body>
    <div class="floating-logo">
        <img src="gymlogo.png" alt="Gym Logo" onerror="this.src='Uploads/default_profile.jpg'">
    </div>

    <nav class="navbar">
        <div class="menu-toggle" onclick="toggleSidebar()">☰</div>
        <div class="logo">Gym Website</div>
    </nav>

    <div class="sidebar" id="sidebar">
        <div class="profile-circle">
            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Pic" onerror="this.src='Uploads/default_profile.jpg'">
        </div>
        <a href="user_profile.php">Profile</a>
        <a href="user_dashboard.php">Dashboard</a>
        <a href="notifications.php">Notifications</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="content" id="content">
        <section class="container">
            <div class="header">
                <h1>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h1>
            </div>

            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>

            <!-- Gym Search and Filter -->
            <div class="form-group">
                <h3 class="text-xl font-semibold mb-4">Find a Gym</h3>
                <form method="GET" action="" class="mb-4">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <label for="district" class="block text-gray-700">Filter by District</label>
                            <select name="district" id="district" class="w-full p-2 border rounded focus:ring-2 focus:ring-yellow-500">
                                <option value="">All Districts</option>
                                <?php foreach ($districts as $district): ?>
                                    <option value="<?php echo htmlspecialchars($district); ?>" <?php echo $district_filter === $district ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($district); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn mt-6 md:mt-0">Filter</button>
                    </div>
                </form>
            </div>

            <!-- Available Gyms -->
            <div class="form-group">
                <h3 class="text-xl font-semibold mb-4">Available Gyms</h3>
                <?php if (empty($gyms)): ?>
                    <p class="text-gray-600">No gyms found in the selected district.</p>
                <?php else: ?>
                    <?php
                    $isDark = true;
                    foreach ($gyms as $gym): ?>
                        <div class="gym-card <?php echo $isDark ? 'dark' : 'light'; ?>">
                            <div class="info">
                                <h4><?php echo htmlspecialchars($gym['gym_name']); ?></h4>
                                <p><strong>District:</strong> <?php echo htmlspecialchars($gym['district']); ?></p>
                                <p><a href="<?php echo htmlspecialchars($gym['google_maps_link']); ?>" target="_blank">View Location</a></p>
                                <p><a href="gym_profile.php?gym_id=<?php echo htmlspecialchars($gym['id']); ?>">View Gym Profile</a></p>
                                <a href="payment.php?gym_id=<?php echo htmlspecialchars($gym['id']); ?>" class="btn mt-2">Join This Gym</a>
                            </div>
                            <img src="<?php echo htmlspecialchars($gym['main_image']); ?>" alt="Gym Image" onerror="this.src='Uploads/vv.png'">
                        </div>
                        <?php $isDark = !$isDark; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="footer">
        <p>&copy; 2025 Gym Website. All rights reserved.</p>
    </div>
</body>
</html>