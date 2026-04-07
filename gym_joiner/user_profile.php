<?php
ob_start();
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'gym_member') {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';
$error = '';
$success = '';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? '';
$user_email = $_SESSION['email'] ?? '';

// Fetch user details
$stmt_user = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
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
    $_SESSION['name'] = $user_name;
    $_SESSION['email'] = $user_email;
} else {
    $error = "User not found.";
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_name = trim($_POST['name'] ?? '');
    $new_email = trim($_POST['email'] ?? '');

    if (empty($new_name) || empty($new_email)) {
        $error = "Name and email are required.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $stmt_update = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        if ($stmt_update) {
            $stmt_update->bind_param("ssi", $new_name, $new_email, $user_id);
            if ($stmt_update->execute()) {
                $_SESSION['name'] = $new_name;
                $_SESSION['email'] = $new_email;
                $success = "Profile updated successfully.";
            } else {
                $error = "Error updating profile: " . $stmt_update->error;
            }
            $stmt_update->close();
        }

        // Handle profile picture upload
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['profile_pic']['tmp_name'];
            $file_name = basename($_FILES['profile_pic']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png'];
            $upload_dir = 'uploads/';
            $new_file_name = $user_id . '_' . time() . '.' . $file_ext;

            if (in_array($file_ext, $allowed_ext)) {
                $destination = $upload_dir . $new_file_name;
                if (move_uploaded_file($file_tmp, $destination)) {
                    // Optionally update profile_pic in database if added as a column
                    // For now, we'll assume it's managed separately or not stored in DB
                    $success .= " Profile picture uploaded successfully.";
                } else {
                    $error .= " Failed to upload profile picture.";
                }
            } else {
                $error .= " Invalid file format. Only JPG, JPEG, and PNG are allowed.";
            }
        }
    }
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Website - User Profile</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        :root { --primary: #4B0082; --secondary: #FFC107; --dark: #2C2A29; --light: #f8f9fa; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--light); min-height: 100vh; padding: 2rem; }
        .container { background: white; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); padding: 2rem; max-width: 600px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 1.5rem; }
        .header h1 { color: var(--primary); font-size: 2rem; font-weight: 600; }
        .profile-pic { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; margin: 0 auto 1rem; display: block; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; color: var(--dark); margin-bottom: 0.5rem; font-weight: 500; }
        .form-group input, .form-group textarea { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 10px; font-size: 1rem; color: var(--dark); }
        .form-group input:focus, .form-group textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(75,0,130,0.2); outline: none; }
        .btn { width: 100%; padding: 0.75rem; background: var(--primary); color: white; border: none; border-radius: 10px; font-size: 1rem; font-weight: 500; cursor: pointer; transition: all 0.3s; }
        .btn:hover { background: #3a0065; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(75,0,130,0.3); }
        .btn:active { transform: translateY(0); }
        .login-error-message { color: #d32f2f; text-align: center; margin-bottom: 1rem; }
        .login-success-message { color: green; text-align: center; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>My Profile</h1>
        </div>
        <div class="login-error-message"><?php echo htmlspecialchars($error); ?></div>
        <div class="login-success-message"><?php echo htmlspecialchars($success); ?></div>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <img src="uploads/default_profile.jpg" alt="Profile Picture" class="profile-pic">
                <label for="profile_pic">Upload Profile Picture</label>
                <input type="file" name="profile_pic" id="profile_pic" accept="image/jpeg,image/png,image/jpg">
            </div>
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($user_name); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user_email); ?>" required>
            </div>
            <button type="submit" name="update_profile" class="btn">Update Profile</button>
        </form>
    </div>
</body>
</html>