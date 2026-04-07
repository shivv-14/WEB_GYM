<?php
ob_start();
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gym_owner') {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? '';
$user_email = $_SESSION['email'] ?? '';
$error = '';
$success = '';

// Check if gym already exists
$stmt = $conn->prepare("SELECT id FROM gyms WHERE user_id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    header("Location: gym_owner_dashboard.php");
    exit();
}
$stmt->close();

// Handle gym profile setup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_gym'])) {
    $gym_name = trim($_POST['gym_name'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $google_maps_link = trim($_POST['google_maps_link'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $facilities = trim($_POST['facilities'] ?? '');
    $operating_hours = trim($_POST['operating_hours'] ?? '');
    $membership_1month = (float)($_POST['membership_1month'] ?? 0);
    $membership_2months = (float)($_POST['membership_2months'] ?? 0);
    $membership_6months = (float)($_POST['membership_6months'] ?? 0);
    $membership_1year = (float)($_POST['membership_1year'] ?? 0);
    $gender = $_POST['gender'] ?? 'both';

    // Validate inputs
    if (empty($gym_name) || empty($district) || empty($google_maps_link) || empty($operating_hours)) {
        $error = "Required fields are missing.";
    } elseif (!filter_var($google_maps_link, FILTER_VALIDATE_URL)) {
        $error = "Invalid Google Maps URL.";
    } elseif ($membership_1month < 0 || $membership_2months < 0 || $membership_6months < 0 || $membership_1year < 0) {
        $error = "Membership prices cannot be negative.";
    } elseif (!in_array($gender, ['male', 'female', 'both'])) {
        $error = "Invalid gender selection.";
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $error = "A valid gym image is required.";
    } else {
        // Insert gym details
        $stmt = $conn->prepare("INSERT INTO gyms (user_id, gym_name, gender, district, google_maps_link, description, facilities, operating_hours, membership_1month, membership_2months, membership_6months, membership_1year) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            $error = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param("issssssddddd", $user_id, $gym_name, $gender, $district, $google_maps_link, $description, $facilities, $operating_hours, $membership_1month, $membership_2months, $membership_6months, $membership_1year);
            if ($stmt->execute()) {
                $gym_id = $conn->insert_id;

                // Handle image upload
                $upload_dir = 'uploads/';
                $image_name = time() . '_' . basename($_FILES['image']['name']);
                $image_path = $upload_dir . $image_name;

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                    $stmt_img = $conn->prepare("INSERT INTO gym_images (gym_id, image_path) VALUES (?, ?)");
                    if ($stmt_img) {
                        $stmt_img->bind_param("is", $gym_id, $image_path);
                        if ($stmt_img->execute()) {
                            $success = "Gym profile set up successfully. Redirecting to dashboard...";
                            header("Refresh: 3; URL=gym_owner_dashboard.php");
                        } else {
                            $error = "Error saving image: " . $stmt_img->error;
                        }
                        $stmt_img->close();
                    } else {
                        $error = "Prepare failed: " . $conn->error;
                    }
                } else {
                    $error = "Failed to upload image.";
                }
            } else {
                $error = "Error creating gym: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

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
    <title>Gym Website - Set Up Gym Profile</title>
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
        .card {
            background-color: #FFFFFF;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
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
        .sidebar {
            position: fixed;
            top: 0;
            right: -300px;
            width: 300px;
            height: 100%;
            background-color: #FFFFFF;
            box-shadow: -2px 0 4px rgba(0, 0, 0, 0.1);
            transition: right 0.3s ease-in-out;
            z-index: 999;
            padding: 1rem;
        }
        .sidebar.open {
            right: 0;
        }
        .hamburger {
            cursor: pointer;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .hamburger div {
            width: 25px;
            height: 3px;
            background-color: #FFFFFF;
        }
        .profile-circle {
            background-color: #FFC107;
            color: #4B0082;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
        }
        .input-field {
            transition: border-color 0.3s ease;
        }
        .input-field:focus {
            border-color: #FFC107;
            outline: none;
            ring: 2px;
        }
    </style>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }
        window.onclick = function(event) {
            if (!event.target.closest('.hamburger') && !event.target.closest('.sidebar')) {
                document.getElementById('sidebar').classList.remove('open');
            }
        }
    </script>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar flex justify-between items-center px-6 py-4">
        <div class="text-2xl font-bold text-white">Gym Website</div>
        <div class="flex items-center space-x-4">
            <div class="profile-circle"><?php echo htmlspecialchars(strtoupper(substr($user_name, 0, 1))); ?></div>
            <div class="hamburger" onclick="toggleSidebar()">
                <div></div>
                <div></div>
                <div></div>
            </div>
        </div>
    </nav>

    <!-- Sidebar Menu -->
    <div id="sidebar" class="sidebar">
        <h3 class="text-xl font-semibold mb-4 text-gray-800">Menu</h3>
        <ul class="space-y-2">
            <li><a href="setup_profile_gym_owner.php" class="text-gray-600 hover:text-yellow-500">Setup Profile</a></li>
            <li><a href="logout.php" class="text-red-600 hover:text-red-800">Logout</a></li>
        </ul>
    </div>

    <!-- Setup Gym Profile Section -->
    <section class="section">
        <h2 class="text-3xl font-bold mb-6 text-center text-red-600">Set Up Your Gym Profile</h2>
        <p class="text-center text-gray-600 mb-6">Please provide the required details to set up your gym.</p>
        <?php if (!empty($success)): ?>
            <p class="text-green-500 text-center mb-4"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <p class="text-red-500 text-center mb-4"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <div class="card max-w-2xl mx-auto">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="mb-4">
                    <label for="gym_name" class="block text-gray-700 font-medium">Gym Name</label>
                    <input type="text" name="gym_name" id="gym_name" class="w-full p-2 border rounded input-field" required
                           value="<?php echo htmlspecialchars($_POST['gym_name'] ?? ''); ?>">
                </div>
                <div class="mb-4">
                    <label for="gender" class="block text-gray-700 font-medium">Gender</label>
                    <select name="gender" id="gender" class="w-full p-2 border rounded input-field" required>
                        <option value="both" <?php echo ($_POST['gender'] ?? 'both') === 'both' ? 'selected' : ''; ?>>Both</option>
                        <option value="male" <?php echo ($_POST['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo ($_POST['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="district" class="block text-gray-700 font-medium">District</label>
                    <select name="district" id="district" class="w-full p-2 border rounded input-field" required>
                        <option value="">Select District</option>
                        <?php foreach ($districts as $district): ?>
                            <option value="<?php echo htmlspecialchars($district); ?>" <?php echo ($_POST['district'] ?? '') === $district ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($district); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="google_maps_link" class="block text-gray-700 font-medium">Google Maps Link</label>
                    <input type="url" name="google_maps_link" id="google_maps_link" class="w-full p-2 border rounded input-field" required
                           value="<?php echo htmlspecialchars($_POST['google_maps_link'] ?? ''); ?>">
                </div>
                <div class="mb-4">
                    <label for="description" class="block text-gray-700 font-medium">About the Gym</label>
                    <textarea name="description" id="description" class="w-full p-2 border rounded input-field" rows="5"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>
                <div class="mb-4">
                    <label for="facilities" class="block text-gray-700 font-medium">Facilities</label>
                    <textarea name="facilities" id="facilities" class="w-full p-2 border rounded input-field" rows="3"><?php echo htmlspecialchars($_POST['facilities'] ?? ''); ?></textarea>
                </div>
                <div class="mb-4">
                    <label for="operating_hours" class="block text-gray-700 font-medium">Operating Hours</label>
                    <input type="text" name="operating_hours" id="operating_hours" class="w-full p-2 border rounded input-field" required
                           value="<?php echo htmlspecialchars($_POST['operating_hours'] ?? ''); ?>">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium">Membership Prices</label>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-600">1 Month</label>
                            <input type="number" name="membership_1month" class="w-full p-2 border rounded input-field" required min="0" step="0.01"
                                   value="<?php echo htmlspecialchars($_POST['membership_1month'] ?? '0'); ?>">
                        </div>
                        <div>
                            <label class="block text-gray-600">2 Months</label>
                            <input type="number" name="membership_2months" class="w-full p-2 border rounded input-field" required min="0" step="0.01"
                                   value="<?php echo htmlspecialchars($_POST['membership_2months'] ?? '0'); ?>">
                        </div>
                        <div>
                            <label class="block text-gray-600">6 Months</label>
                            <input type="number" name="membership_6months" class="w-full p-2 border rounded input-field" required min="0" step="0.01"
                                   value="<?php echo htmlspecialchars($_POST['membership_6months'] ?? '0'); ?>">
                        </div>
                        <div>
                            <label class="block text-gray-600">1 Year</label>
                            <input type="number" name="membership_1year" class="w-full p-2 border rounded input-field" required min="0" step="0.01"
                                   value="<?php echo htmlspecialchars($_POST['membership_1year'] ?? '0'); ?>">
                        </div>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="image" class="block text-gray-700 font-medium">Upload Gym Image</label>
                    <input type="file" name="image" id="image" class="w-full p-2 border rounded input-field" accept="image/*" required>
                </div>
                <button type="submit" name="setup_gym" class="btn-primary w-full">Set Up Gym</button>
            </form>
        </div>
    </section>

    <!-- Footer Section -->
    <section class="section bg-white text-center">
        <p class="text-gray-600">&copy; 2025 Gym Website. All rights reserved.</p>
    </section>
</body>
</html>