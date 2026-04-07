<?php
class AuthController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function login($email, $password) {
        // Logic for user login
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            return true;
        }
        return false;
    }

    public function signup($name, $email, $password) {
        // Logic for user signup
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);
        return $stmt->execute();
    }

    public function sendResetLink($email) {
        // Check if user exists
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $token = bin2hex(random_bytes(50));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $stmt = $this->db->prepare("UPDATE users SET reset_token = :token, reset_token_expiry = :expiry WHERE email = :email");
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':expiry', $expiry);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            if ($this->sendResetEmail($email, $token)) {
                return true;
            } else {
                return 'Failed to send reset email.';
            }
        }
        return 'Email not found.';
    }

    public function resetPassword($token, $newPassword) {
        // Validate token and expiry
        $stmt = $this->db->prepare("SELECT * FROM users WHERE reset_token = :token");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            if (isset($user['reset_token_expiry']) && strtotime($user['reset_token_expiry']) > time()) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $this->db->prepare("UPDATE users SET password = :password, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = :token");
                $stmt->bindParam(':password', $hashedPassword);
                $stmt->bindParam(':token', $token);
                return $stmt->execute();
            } else {
                return 'Reset token expired.';
            }
        }
        return 'Invalid reset token.';
    }

    private function sendResetEmail($email, $token) {
        // Use PHPMailer to send the email
        require_once __DIR__ . '/../../PHPMailer/PHPMailer.php';
        require_once __DIR__ . '/../../PHPMailer/SMTP.php';
        require_once __DIR__ . '/../../PHPMailer/Exception.php';
        $mail = new \PHPMailer\PHPMailer\PHPMailer();
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com'; // Set your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'your@email.com'; // SMTP username
        $mail->Password = 'yourpassword'; // SMTP password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->setFrom('no-reply@yourdomain.com', 'WebGym');
        $mail->addAddress($email);
        $mail->Subject = 'Password Reset Request';
        $resetLink = "http://yourdomain.com/reset_password.php?token=" . $token;
        $mail->Body = "To reset your password, please click the link below:\n" . $resetLink;
        return $mail->send();
    }
}
?>