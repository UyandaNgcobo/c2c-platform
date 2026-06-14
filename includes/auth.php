<?php
// Only start the session if one isn't already running
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function requireAdmin() {
    // We call requireLogin first just to be safe
    requireLogin(); 
    
    if ($_SESSION['role'] !== 'admin') {
        header("Location: dashboard.php"); // Or unauthorized.php
        exit();
    }
}
?>