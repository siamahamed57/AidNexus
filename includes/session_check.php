<?php
/**
 * Session Check - Ensures user is authenticated
 * Include this file at the top of protected pages
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // User is not logged in, redirect to login page
    header("Location: login.php");
    exit;
}

// Set user variables for easy access
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$user_email = $_SESSION['email'];
$user_role = $_SESSION['role'] ?? 'victim';
$user_victim_id = $_SESSION['victim_id'] ?? null;

// Optional: Check session timeout (30 minutes of inactivity)
$timeout_duration = 1800; // 30 minutes in seconds

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    // Session has expired
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit;
}

// Update last activity timestamp
$_SESSION['last_activity'] = time();
?>
