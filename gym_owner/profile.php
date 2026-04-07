<?php
ob_start();
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gym_owner') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$user_email = $_SESSION['email'];

// Fetch owner details
$stmt = $conn->prepare("SELECT name, email, profile_pic FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch gym details
$stmt = $conn->prepare("SELECT * FROM gyms WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$gym = $stmt->get_result()->fetch_assoc();
$stmt->close();

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    
    if (isset($_FILES['owner_profile_pic']) && $_FILES['owner_profile_pic']['error'] === 0) {
        $upload_dir = '../uploads/';
        $file_ext = strtolower(pathinfo($_FILES['owner_profile_pic']['name'], PATHINFO_EXTENSION));
        if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $owner_pic_name = 'owner_profile_' . $user_id . '_' . time() . '.' . $file_ext;
            $owner_pic_path = $upload_dir . $owner_pic_name;
            move_uploaded_file($_FILES['owner_profile_pic']['tmp_name'], $owner_pic_path);
            $user['profile_pic'] = $owner_pic_path;
        }
    }
    
    if (isset($_FILES['gym_profile_pic']) && $_FILES['gym_profile_pic']['error'] === 0) {
        $file_ext = strtolower(pathinfo($_FILES['gym_profile_pic']['name'], PATHINFO_EXTENSION));
        if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $gym_pic_name = 'gym_profile_' . $gym['id'] . '_' . time() . '.' . $file_ext;
            $gym_pic_path = $upload_dir . $gym_pic_name;
            move_uploaded_file($_FILES['gym_profile_pic']['tmp_name'], $gym_pic_path);
            $gym['profile_pic'] = $gym_pic_path;
        }
    }
    
    // Update owner
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, profile_pic = ? WHERE id = ?");
    $_SESSION['profile_pic'] = $user['profile_pic'];
    $stmt->bind_param("sssi", $name, $email, $user['profile_pic'], $user_id);
    if ($stmt->execute()) {
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;
        $success = 'Profile updated!';
    } else {
        $error = 'Update failed.';
    }
    $stmt->close();
    
    // Update gym
    $stmt = $conn->prepare("UPDATE gyms SET gym_name = ?, profile_pic = ? WHERE user_id = ?");
    $stmt->bind_param("ssi", $_POST['gym_name'], $gym['profile_pic'], $user_id);
    $stmt->execute();
    $stmt->close();
}

$districts = ['Hyderabad', 'Mahabubnagar']; // Simplified
?>
<!DOCTYPE html>
<html>
<head>
    <title>Owner Profile</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        :root { --primary: #4B0082; --secondary: #FFC107; --dark: #2C2A29; --light: #f8f9fa; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--light); min-height: 100vh; padding: 6rem 2rem 2rem; }
        .navbar { background-color: var(--primary); display: flex; justify-content: space-between; align-items: center; padding: 1rem 2rem; color: white; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .sidebar { width: 250px; background-color: var(--primary); color: #FFFFFF; position: fixed; top: 60px; bottom: 0; left: -250px; transition: left 0.3s ease; padding: 1rem; z-index: 1000; }
        .sidebar.active { left: 0; }
        .sidebar .profile-circle { background-color: var(--secondary); color: var(--primary); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 2rem; margin: 0 auto 1rem; }
        .sidebar a { display: block; padding: 0.75rem 1rem; color: #FFFFFF; text-decoration: none; font-weight: 500; transition: background-color 0.3s; }
        .sidebar a:hover { background-color: rgba(255, 255, 255, 0.1); }
        .content { margin-left: 0; transition: margin-left 0.3s ease; }
        .content.shifted { margin-left: 250px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 2rem; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .section { margin-bottom: 2rem; }
        .section h2 { color: var(--primary); border-bottom: 2px solid var(--secondary); padding-bottom: 0.5rem; }
        input, textarea, select { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 10px; margin-bottom: 1rem; box-sizing: border-box; }
        .profile-pic { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem; }
        .btn { background: var(--primary); color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 10px; cursor: pointer; margin-top: 1rem; text-decoration: none; display: inline-block; }
        .btn:hover { background: #3a0065; }
        .back { background: #6c757d; }
        .success { color: green; background: #d4edda; padding: 1rem; border-radius: 10px; margin-bottom: 1rem; }
        .error { color: red; background: #f8d7da; padding: 1rem; border-radius: 10px; margin-bottom: 1rem; }
        .menu-toggle { font-size: 1.5rem; cursor: pointer; }
        @media (max-width: 768px) { .content.shifted { margin-left: 0; } }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 2rem; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .section { margin-bottom: 2rem; }
        .section h2 { color: #4B0082; border-bottom: 2px solid #FFC107; padding-bottom: 0.5rem; }
        input, textarea, select { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 10px; margin-bottom: 1rem; }
        .profile-pic { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem; }
        .btn { background: #4B0082; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 10px; cursor: pointer; width: 100%; margin-top: 1rem; }
        .btn:hover { background: #3a0065; }
        .back { background: #6c757d; }
        .success { color: green; background: #d4edda; padding: 1rem; border-radius: 10px; margin-bottom: 1rem; }
        .error { color: red; background: #f8d7da; padding: 1rem; border-radius: 10px; margin-bottom: 1rem; }
        .sidebar-active { left: 0 !important; }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($success): ?><div class="success"><?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="section">
                <h2>Owner Profile</h2>
                <?php if ($user['profile_pic']): ?>
                    <img src="../<?php echo htmlspecialchars($user['profile_pic']); ?>" class="profile-pic" alt="Owner Pic">
                <?php endif; ?>
                <input type="file" name="owner_profile_pic" accept="image/*">
                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" placeholder="Name" required>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" placeholder="Email" required>
            </div>
            
            <div class="section">
                <h2>Gym Profile</h2>
                <?php if ($gym['profile_pic']): ?>
                    <img src="../<?php echo htmlspecialchars($gym['profile_pic']); ?>" class="profile-pic" alt="Gym Pic">
                <?php endif; ?>
                <input type="file" name="gym_profile_pic" accept="image/*">
                <input type="text" name="gym_name" value="<?php echo htmlspecialchars($gym['gym_name']); ?>" placeholder="Gym Name" required>
                <select name="district">
                    <option>Select District</option>
                    <?php foreach ($districts as $d): ?>
                        <option <?php echo $gym['district'] == $d ? 'selected' : ''; ?>><?php echo $d; ?></option>
                    <?php endforeach; ?>
                </select>
                <!-- Add other gym fields here -->
            </div>
            
            <button type="submit" name="update_profile" class="btn">Update Profile</button>
<nav class="navbar" style="position: static; margin-bottom:1rem;">
        <div class="menu-toggle" onclick="toggleSidebar()" style="cursor:pointer;">☰</div>
        <div class="logo">Gym Website</div>
    </nav>
    <div class="sidebar" id="sidebar" style="top:60px;">
        <div class="profile-circle"><?php echo htmlspecialchars(strtoupper(substr($user_name, 0, 1))); ?></div>
        <a href="profile.php">Profile</a>
        <a href="gym_owner_dashboard.php">Dashboard</a>
        <a href="../notifications.php">Notifications</a>
        <a href="../logout.php">Logout</a>
    </div>
            <a href="gym_owner_dashboard.php" class="btn" style="width:auto; margin-left:1rem;">Dashboard</a>

        </form>
    </div>
</body>
</html>
