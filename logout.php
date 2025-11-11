<?php
require_once '../includes/paths.php';

// Initialize session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Destroy all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: ' . BASE_URL . 'user/login.php');
exit;
?>