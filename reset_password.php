```php
<?php
ob_start();
session_start();
require_once 'db_connect.php';

define('BASE_PATH', '/gymwebsite/');

$error = '';
$success = '';

$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';

if (empty($email) || empty($token)) {
    $error = "Invalid reset link.";
} else {
    $stmt = $conn->prepare("SELECT created_at FROM password_resets WHERE email = ? AND token = ?");
    $stmt->bind_param("ss", $email, $token);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$result) {
        $error = "Invalid or expired reset link.";
    } elseif (strtotime($result['created_at']) < strtotime('-1 hour')) {
        $error = "Reset link has expired.";
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    if (empty($password) || empty($confirm_password)) {
        $error = "Both password fields required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        if ($stmt->execute()) {
            $stmt->close();
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->close();
            $success = "Password reset successfully. <a href='" . BASE_PATH . "login.php'>Login</a>";
        } else {
            $error = "Error resetting password.";
            $stmt->close();
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
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Roboto', sans-serif; background: #F5F5F5; }
        .navbar { background: #4B0082; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .navbar a { color: #FFFFFF; }
        .navbar a:hover { color: #FFC107; }
        .card { background: #FFFFFF; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); padding: 1.5rem; }
        .btn-primary { background: #FFC107; color: #4B0082; padding: 0.5rem 1rem; border-radius: 9999px; font-weight: bold; }
        .btn-primary:hover { background: #FFD700; }
    </style>
</head>
<body>
    <nav class="navbar flex justify-between items-center px-6 py-4">
        <div class="text-2xl font-bold text-white">Gym Website</div>
        <div><a href="<?php echo BASE_PATH; ?>index.php" class="text-white hover:text-yellow-400">Home</a></div>
    </nav>

    <section class="flex items-center justify-center min-h-screen px-4">
        <div class="card w-full max-w-sm">
            <h2 class="text-2xl font-bold mb-4 text-center text-red-600">Reset Password</h2>
            <?php if ($success): ?>
                <p class="text-green-500 text-center mb-4"><?php echo $success; ?></p>
            <?php elseif ($error): ?>
                <p class="text-red-500 text-center mb-4"><?php echo htmlspecialchars($error); ?></p>
            <?php else: ?>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-gray-700">New Password</label>
                        <input type="password" name="password" class="w-full p-2 border rounded" required minlength="8">
                    </div>
                    <div>
                        <label class="block text-gray-700">Confirm Password</label>
                        <input type="password" name="confirm_password" class="w-full p-2 border rounded" required minlength="8">
                    </div>
                    <button type="submit" name="reset_password" class="btn-primary w-full">Reset Password</button>
                </form>
            <?php endif; ?>
        </div>
    </section>
</body>
</html>
