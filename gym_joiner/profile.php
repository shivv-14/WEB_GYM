<?php
ob_start();
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'gym_member') {
    header("Location: userlogin.php");
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
$error = '';
$success = '';

// Fetch current user details
$stmt_user = $conn->prepare("SELECT name, email, profile_pic FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

$user_name = $user['name'] ?? '';
$user_email = $user['email'] ?? '';
$profile_pic = $user['profile_pic'] ?? 'uploads/default_profile.jpg';

// Handle profile update (Name, Email, Profile Pic)
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
                $user_name = $new_name;
                $user_email = $new_email;
                $success = "Profile updated successfully.";
            } else {
                $error = "Error updating profile details.";
            }
            $stmt_update->close();
        }

        // Handle profile picture upload
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $file_ext = strtolower(pathinfo(basename($_FILES['profile_pic']['name']), PATHINFO_EXTENSION));
            $new_file_name = "user_{$user_id}_" . time() . ".{$file_ext}";
            $destination = $upload_dir . $new_file_name;

            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $destination)) {
                $stmt_pic = $conn->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
                if ($stmt_pic) {
                    $stmt_pic->bind_param("si", $destination, $user_id);
                    if ($stmt_pic->execute()) {
                        $profile_pic = $destination;
                        $success .= " Profile picture updated.";
                    } else {
                        $error .= " Failed to update profile picture in database.";
                    }
                    $stmt_pic->close();
                }
            } else {
                $error .= " Failed to move uploaded file.";
            }
        }
    }
}


// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $token = bin2hex(random_bytes(32));
    
    // Use an absolute URL for the reset link
    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?email=" . urlencode($user_email) . "&token=" . urlencode($token);

    $stmt_token = $conn->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?) ON DUPLICATE KEY UPDATE token = VALUES(token), created_at = NOW()");
    if (!$stmt_token) {
        $error = "Prepare failed: " . $conn->error;
    } else {
        $stmt_token->bind_param("ss", $user_email, $token);
        if ($stmt_token->execute()) {
            $mail = new PHPMailer(true);
            try {
                //Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'mywork1430@gmail.com'; // Your Gmail address
                $mail->Password   = 'xzsr dwfl lyrm fnwd'; // Your Gmail App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;

                //Recipients
                $mail->setFrom('mywork1430@gmail.com', 'Gym Website Admin');
                $mail->addAddress($user_email, $user_name);

                //Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body    = "<h1>Password Reset Request</h1>
                                  <p>You requested a password reset. Click the link below to set a new password:</p>
                                  <p><a href='$reset_link'>Reset Password</a></p>
                                  <p>This link will expire in 1 hour.</p>
                                  <p>If you did not request this, please ignore this email.</p>";
                $mail->send();
                $success = 'Password reset link has been sent to your email.';
            } catch (Exception $e) {
                $error = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            $error = "Error saving reset token: " . $stmt_token->error;
        }
        $stmt_token->close();
    }
}


ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Website - My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #F5F5F5; }
        .navbar { background-color: #4B0082; } .navbar a { color: #FFFFFF; } .navbar a:hover { color: #FFC107; }
        .card { background-color: #FFFFFF; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); padding: 2rem; margin-bottom: 2rem; }
        .btn-primary { background-color: #FFC107; color: #4B0082; padding: 0.75rem 1.5rem; border-radius: 9999px; font-weight: bold; transition: background-color 0.3s; }
        .btn-primary:hover { background-color: #FFD700; }
        .profile-pic-preview { width: 128px; height: 128px; border-radius: 50%; object-fit: cover; margin: 0 auto 1rem; display: block; border: 3px solid #FFC107; }
    </style>
</head>
<body>
    <nav class="navbar flex justify-between items-center px-6 py-4 mb-8">
        <div class="text-2xl font-bold text-white">Gym Website</div>
        <div class="flex items-center space-x-4">
            <a href="user_dashboard.php" class="hover:text-yellow-300">Dashboard</a>
            <a href="logout.php" class="hover:text-yellow-300">Logout</a>
        </div>
    </nav>

    <div class="container mx-auto max-w-2xl">
        <h2 class="text-3xl font-bold mb-6 text-center text-indigo-800">My Profile</h2>
        
        <?php if ($success): ?><p class="bg-green-100 text-green-700 p-3 rounded mb-4 text-center"><?php echo htmlspecialchars($success); ?></p><?php endif; ?>
        <?php if ($error): ?><p class="bg-red-100 text-red-700 p-3 rounded mb-4 text-center"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>

        <div class="card">
            <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
                <div>
                    <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture" class="profile-pic-preview" onerror="this.src='uploads/default_profile.jpg'">
                    <label for="profile_pic" class="block text-sm font-medium text-gray-700">Change Profile Picture</label>
                    <input type="file" name="profile_pic" id="profile_pic" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                </div>
                
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                    <input type="text" name="name" id="name" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm" value="<?php echo htmlspecialchars($user_name); ?>" required>
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                    <input type="email" name="email" id="email" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm" value="<?php echo htmlspecialchars($user_email); ?>" required>
                </div>

                <button type="submit" name="update_profile" class="w-full btn-primary">Update Profile</button>
            </form>
        </div>

        <div class="card">
            <form method="POST" action="">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-2">Reset Password</h3>
                <p class="text-sm text-gray-600 mb-4">Click the button below to send a password reset link to your email address.</p>
                <button type="submit" name="reset_password" class="w-full btn-primary bg-indigo-600 text-white hover:bg-indigo-700">Send Password Reset Link</button>
            </form>
        </div>
    </div>

</body>
</html>