<?php
ob_start();
session_start();
require_once '../db_connect.php';
require_once '../ReviewController.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gym_owner') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$user_email = $_SESSION['email'];

$stmt = $conn->prepare("SELECT * FROM gyms WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$gym = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total_members = 0;
$average_rating = $gym ? ReviewController::getAverageRating($gym['id']) : 0;
if ($gym) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM payments WHERE gym_name = ? AND payment_status = 'approved'");
    $stmt->bind_param("s", $gym['gym_name']);
    $stmt->execute();
    $total_members = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
}

$members = [];
if ($gym) {
    $stmt = $conn->prepare("SELECT DISTINCT u.id, u.name, u.email, u.profile_pic, p.membership_type, p.joining_date, p.renewal_date 
                            FROM users u 
                            JOIN payments p ON u.id = p.user_id 
                            WHERE p.gym_name = ? AND p.payment_status = 'approved'");
    $stmt->bind_param("s", $gym['gym_name']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }
    $stmt->close();
}

$notifications = [];
if ($gym) {
    $stmt = $conn->prepare("SELECT r.id, r.comment, r.created_at, u.name 
                            FROM reviews r 
                            JOIN users u ON r.user_id = u.id 
                            WHERE r.gym_id = ? AND r.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->bind_param("i", $gym['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notifications[] = "New review by {$row['name']} on {$row['created_at']}: {$row['comment']}";
    }
    $stmt->close();
}

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
    <title>Gym Owner Overview</title>
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
            margin-bottom: 1.5rem;
        }

        .header h1 {
            color: var(--primary);
            font-size: 2rem;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group h3 {
            color: var(--primary);
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .form-group label {
            display: block;
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            box-sizing: border-box;
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(75, 0, 130, 0.1);
        }

        .btn {
            width: 100%;
            padding: 0.75rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            background: var(--primary);
            color: #FFFFFF;
            margin-top: 1rem;
        }

        .btn:hover {
            background: #3a0065;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 0.75rem;
            text-align: left;
        }

        th {
            background-color: var(--primary);
            color: #FFFFFF;
            font-weight: 600;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .stats-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .notification-item {
            background: #f0f8ff;
            padding: 1rem;
            border-left: 4px solid var(--primary);
            margin-bottom: 1rem;
            border-radius: 5px;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @media (max-width: 768px) {
            .content.shifted {
                margin-left: 0;
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
        <div class="logo">Gym Website - Overview</div>
    </nav>

    <div class="sidebar" id="sidebar">
        <div class="profile-circle" onclick="window.location.href='profile.php'" style="cursor:pointer;">
            <?php 
            $user_pic = $_SESSION['profile_pic'] ?? '';
            if ($user_pic):
            ?>
            <img src="../<?php echo htmlspecialchars($user_pic); ?>" alt="Profile" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
            <?php else: 
            ?>
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
                <h1>Owner Overview</h1>
            </div>

            <!-- Overview Stats -->
            <div class="form-group">
                <h3>Overview</h3>
                <div class="grid">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $total_members; ?></div>
                        <div>Total Members</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-number"><?php echo number_format($average_rating, 1); ?>/5</div>
                        <div>Average Rating</div>
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <div class="form-group">
                <h3>Notifications</h3>
                <?php if (empty($notifications)): ?>
                    <p>No new notifications.</p>
                <?php else: ?>
                    <div class="notification-list">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item">
                                <?php echo htmlspecialchars($notification); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Gym Profile -->
            <div class="form-group">
                <h3>Gym Profile</h3>
                <?php if ($gym): ?>
                    <form method="POST" action="">
                        <div>
                            <label>Gym Name</label>
                            <input type="text" name="gym_name" value="<?php echo htmlspecialchars($gym['gym_name']); ?>" required>
                        </div>
                        <div>
                            <label>District</label>
                            <select name="district" required>
                                <option>Select District</option>
                                <?php foreach ($districts as $district): ?>
                                    <option value="<?php echo htmlspecialchars($district); ?>" <?php echo $gym['district'] === $district ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($district); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Google Maps Link</label>
                            <input type="url" name="google_maps_link" value="<?php echo htmlspecialchars($gym['google_maps_link']); ?>" required>
                        </div>
                        <div>
                            <label>About the Gym</label>
                            <textarea name="description" rows="4"><?php echo htmlspecialchars($gym['description'] ?? ''); ?></textarea>
                        </div>
                        <div>
                            <label>Facilities</label>
                            <textarea name="facilities" rows="3"><?php echo htmlspecialchars($gym['facilities'] ?? ''); ?></textarea>
                        </div>
                        <div>
                            <label>Operating Hours</label>
                            <input type="text" name="operating_hours" value="<?php echo htmlspecialchars($gym['operating_hours'] ?? ''); ?>" required>
                        </div>
                        <div>
                            <label>Membership Prices</label>
                            <div class="grid">
                                <div>
                                    <label>1 Month</label>
                                    <input type="number" name="membership_1month" value="<?php echo htmlspecialchars($gym['membership_1month']); ?>" step="0.01" required>
                                </div>
                                <div>
                                    <label>2 Months</label>
                                    <input type="number" name="membership_2months" value="<?php echo htmlspecialchars($gym['membership_2months']); ?>" step="0.01" required>
                                </div>
                                <div>
                                    <label>6 Months</label>
                                    <input type="number" name="membership_6months" value="<?php echo htmlspecialchars($gym['membership_6months']); ?>" step="0.01" required>
                                </div>
                                <div>
                                    <label>1 Year</label>
                                    <input type="number" name="membership_1year" value="<?php echo htmlspecialchars($gym['membership_1year']); ?>" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="update_gym" class="btn">Update Gym Details</button>
                    </form>
                <?php else: ?>
                    <p>No gym assigned. Contact admin.</p>
                <?php endif; ?>
            </div>

            <!-- Members -->
            <div class="form-group">
                <h3>Members</h3>
                <form method="POST" style="margin-bottom: 1rem;">
                    <button type="submit" name="export_members" class="btn">Export Members to CSV</button>
                </form>
                <?php if (empty($members)): ?>
                    <p>No members found.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Membership</th>
                                <th>Join Date</th>
                                <th>Renewal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($member['name']); ?></td>
                                    <td><?php echo htmlspecialchars($member['email']); ?></td>
                                    <td><?php echo htmlspecialchars($member['membership_type']); ?></td>
                                    <td><?php echo htmlspecialchars($member['joining_date']); ?></td>
                                    <td><?php echo htmlspecialchars($member['renewal_date']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Send Diet Plan -->
            <div class="form-group">
                <h3>Send Diet & Training Plan</h3>
                <?php if (!empty($members)): ?>
                    <form method="POST">
                        <div>
                            <label>Select Member</label>
                            <select name="member_id" required>
                                <option value="">Select Member</option>
                                <?php foreach ($members as $member): ?>
                                    <option value="<?php echo $member['id']; ?>">
                                        <?php echo htmlspecialchars($member['name']); ?> (<?php echo htmlspecialchars($member['email']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Diet Plan</label>
                            <textarea name="diet_plan" rows="4" required placeholder="Diet plan details..."></textarea>
                        </div>
                        <div>
                            <label>Training Split</label>
                            <textarea name="training_split" rows="4" required placeholder="Training split..."></textarea>
                        </div>
                        <button type="submit" name="send_plan" class="btn">Send Plan</button>
                    </form>
                <?php else: ?>
                    <p>No members available.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('content').classList.toggle('shifted');
        }
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>

