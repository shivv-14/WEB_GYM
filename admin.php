<?php
ob_start();
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed. Please check db_connect.php configuration.");
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_gym'])) {
        $gym_name = trim($_POST['gym_name'] ?? '');
        $google_maps_link = trim($_POST['google_maps_link'] ?? '');
        $user_id = $_POST['user_id'] ?? '';
        $district = trim($_POST['district'] ?? '');
        $membership_1month = (float)($_POST['membership_1month'] ?? 0);
        $membership_2months = (float)($_POST['membership_2months'] ?? 0);
        $membership_6months = (float)($_POST['membership_6months'] ?? 0);
        $membership_1year = (float)($_POST['membership_1year'] ?? 0);

        if (empty($gym_name) || empty($google_maps_link) || empty($user_id) || empty($district)) {
            $error = "All fields are required.";
        } elseif (!filter_var($google_maps_link, FILTER_VALIDATE_URL)) {
            $error = "Invalid Google Maps URL.";
        } elseif ($membership_1month < 0 || $membership_2months < 0 || $membership_6months < 0 || $membership_1year < 0) {
            $error = "Membership prices cannot be negative.";
        } else {
            $stmt = $conn->prepare("INSERT INTO gyms (user_id, gym_name, district, google_maps_link, membership_1month, membership_2months, membership_6months, membership_1year) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt === false) {
                die("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("isssdddd", $user_id, $gym_name, $district, $google_maps_link, $membership_1month, $membership_2months, $membership_6months, $membership_1year);
            if ($stmt->execute()) {
                $success = "Gym added successfully.";
            } else {
                $error = "Error adding gym: " . $stmt->error;
            }
            $stmt->close();
        }
    } elseif (isset($_POST['delete_gym'])) {
        $gym_id = $_POST['gym_id'] ?? '';
        $stmt = $conn->prepare("DELETE FROM gyms WHERE id = ?");
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $gym_id);
        if ($stmt->execute()) {
            $success = "Gym deleted successfully.";
        } else {
            $error = "Error deleting gym: " . $stmt->error;
        }
        $stmt->close();
    }
}

$stmt = $conn->prepare("SELECT u.id, u.name, u.email, g.id AS gym_id, g.gym_name, g.district, g.google_maps_link, g.membership_1month, g.membership_2months, g.membership_6months, g.membership_1year 
                       FROM users u 
                       LEFT JOIN gyms g ON u.id = g.user_id 
                       WHERE u.role = 'gym_owner'");
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();
$gym_owners = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$districts = [
    'Adilabad', 'Bhadradri Kothagudem', 'Hyderabad', 'Jagtial', 'Jangaon',
    'Jayashankar Bhupalpally', 'Jogulamba Gadwal', 'Kamareddy', 'Karimnagar',
    'Khammam', 'Komaram Bheem', 'Mahabubabad', 'Mahabubnagar', 'Mancherial',
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
    <title>Gym Website - Admin Dashboard</title>
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
            display: none;
        }

        .navbar .logout-btn {
            padding: 0.5rem 1rem;
            background-color: #DC2626;
            color: #FFFFFF;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .navbar .logout-btn:hover {
            background-color: #B91C1C;
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
        .form-group:nth-child(4) { animation-delay: 1.1s; }
        .form-group:nth-child(5) { animation-delay: 1.3s; }

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
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn:hover {
            background: #3a0065;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(75, 0, 130, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .table-container {
            margin-top: 2rem;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        th, td {
            padding: 0.75rem;
            text-align: left;
        }

        th {
            background-color: var(--primary);
            color: #FFFFFF;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }

        td {
            border-bottom: 1px solid #E5E7EB;
        }

        .btn-danger {
            background-color: #DC2626;
            color: #FFFFFF;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            transition: background-color 0.3s;
        }

        .btn-danger:hover {
            background-color: #B91C1C;
        }

        .login-error-message {
            color: #d32f2f;
            text-align: center;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            font-weight: 500;
            display: <?php echo $error ? 'block' : 'none'; ?>;
        }

        .login-success-message {
            color: green;
            text-align: center;
            font-size: 0.875rem;
            margin-bottom: 1rem;
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

        @media (max-width: 768px) {
            .navbar .menu-toggle {
                display: block;
            }
            .sidebar {
                left: -250px;
            }
            .sidebar.active {
                left: 0;
            }
            .content.shifted {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="floating-logo">
        <img src="gymlogo.png" alt="Gym Logo">
    </div>

    <nav class="navbar">
        <button class="logout-btn" onclick="if(confirm('Are you sure you want to logout?')) window.location.href='logout.php';">Logout</button>
        <div class="logo">Gym Website</div>
        <div class="menu-toggle" onclick="toggleSidebar()">☰</div>
    </nav>

    <div class="sidebar" id="sidebar">
        <a href="#">Dashboard</a>
        <a href="#">Manage Gyms</a>
        <a href="#">Reports</a>
    </div>

    <div class="content" id="content">
        <section class="container">
            <div class="header">
                <h1>Admin Dashboard</h1>
            </div>
            
            <div class="login-error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>

            <div class="login-success-message">
                <?php echo htmlspecialchars($success); ?>
            </div>

            <div class="form-group">
                <h3 class="text-xl font-semibold mb-4">Add New Gym</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="user_id">Gym Owner</label>
                        <select name="user_id" id="user_id" required>
                            <option value="">Select Gym Owner</option>
                            <?php foreach ($gym_owners as $owner): ?>
                                <option value="<?php echo htmlspecialchars($owner['id']); ?>"><?php echo htmlspecialchars($owner['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="gym_name">Gym Name</label>
                        <input type="text" name="gym_name" id="gym_name" required>
                    </div>
                    <div class="form-group">
                        <label for="district">District</label>
                        <select name="district" id="district" required>
                            <option value="">Select District</option>
                            <?php foreach ($districts as $district): ?>
                                <option value="<?php echo htmlspecialchars($district); ?>"><?php echo htmlspecialchars($district); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="google_maps_link">Google Maps Link</label>
                        <input type="url" name="google_maps_link" id="google_maps_link" required>
                    </div>
                    <div class="form-group">
                        <label>Membership Prices</label>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label>1 Month</label>
                                <input type="number" name="membership_1month" required min="0" step="0.01">
                            </div>
                            <div>
                                <label>2 Months</label>
                                <input type="number" name="membership_2months" required min="0" step="0.01">
                            </div>
                            <div>
                                <label>6 Months</label>
                                <input type="number" name="membership_6months" required min="0" step="0.01">
                            </div>
                            <div>
                                <label>1 Year</label>
                                <input type="number" name="membership_1year" required min="0" step="0.01">
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="add_gym" class="btn">Add Gym Location</button>
                </form>
            </div>

            <div class="table-container">
                <h3 class="text-xl font-semibold mb-4">Gym Owners and Gyms</h3>
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th>Gym Owner</th>
                                <th>Email</th>
                                <th>Gym Name</th>
                                <th>District</th>
                                <th>Location</th>
                                <th>Membership Prices</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($gym_owners as $owner): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($owner['name']); ?></td>
                                    <td><?php echo htmlspecialchars($owner['email']); ?></td>
                                    <td><?php echo $owner['gym_name'] ? htmlspecialchars($owner['gym_name']) : 'No Gym'; ?></td>
                                    <td><?php echo $owner['district'] ? htmlspecialchars($owner['district']) : 'N/A'; ?></td>
                                    <td>
                                        <?php if ($owner['google_maps_link']): ?>
                                            <a href="<?php echo htmlspecialchars($owner['google_maps_link']); ?>" target="_blank" class="text-blue-600 hover:underline">View Location</a>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($owner['gym_name']): ?>
                                            <ul class="list-disc list-inside">
                                                <li>1 Month: ₹<?php echo htmlspecialchars($owner['membership_1month']); ?></li>
                                                <li>2 Months: ₹<?php echo htmlspecialchars($owner['membership_2months']); ?></li>
                                                <li>6 Months: ₹<?php echo htmlspecialchars($owner['membership_6months']); ?></li>
                                                <li>1 Year: ₹<?php echo htmlspecialchars($owner['membership_1year']); ?></li>
                                            </ul>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($owner['gym_id']): ?>
                                            <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this gym?');">
                                                <input type="hidden" name="gym_id" value="<?php echo htmlspecialchars($owner['gym_id']); ?>">
                                                <button type="submit" name="delete_gym" class="btn-danger">Delete</button>
                                            </form>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');
            sidebar.classList.toggle('active');
            content.classList.toggle('shifted');
        }
    </script>
</body>
</html>