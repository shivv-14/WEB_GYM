<?php
class NotificationController {
    private $notificationModel;

    public function __construct() {
        require_once '../models/Notification.php';
        $this->notificationModel = new Notification();
    }

    public function sendEmailNotification($userEmail, $subject, $message) {
        // Use PHPMailer or similar library to send email
        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.example.com'; // Set the SMTP server to send through
            $mail->SMTPAuth = true;
            $mail->Username = 'your_email@example.com'; // SMTP username
            $mail->Password = 'your_password'; // SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            //Recipients
            $mail->setFrom('from@example.com', 'Gym Website');
            $mail->addAddress($userEmail); // Add a recipient

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $message;

            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function notifyUserOfDietPlan($userId) {
        // Fetch user email from the database
        $userEmail = $this->notificationModel->getUserEmail($userId);
        $subject = 'Your Diet Plan';
        $message = 'Here is your personalized diet plan...'; // Customize the message

        return $this->sendEmailNotification($userEmail, $subject, $message);
    }

    public function notifyUserOfTrainingPlan($userId) {
        // Fetch user email from the database
        $userEmail = $this->notificationModel->getUserEmail($userId);
        $subject = 'Your Training Plan';
        $message = 'Here is your personalized training plan...'; // Customize the message

        return $this->sendEmailNotification($userEmail, $subject, $message);
    }
}
?>