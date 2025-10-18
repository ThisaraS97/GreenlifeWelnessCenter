<?php
session_start();

// Clear all therapist session data
unset($_SESSION['therapist_id']);
unset($_SESSION['therapist_name']);
unset($_SESSION['therapist_email']);

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: therapist-login.php");
exit();
?>