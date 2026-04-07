<?php
ob_start();
session_start();

// Destroy the session
session_unset();
session_destroy();

// Redirect to question.php
header("Location: ../question.php");
exit();

ob_end_flush();
?>

