<?php
session_start();

// Remove all session data
$_SESSION = array();

// Destroy the admin session
session_destroy();

// Redirect to the main Gas Hub homepage
header("Location: ../index.php");
exit();
?>