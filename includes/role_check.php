<?php
/**
 * Role-Based Access Control
 * Functions for checking and enforcing user roles
 */

/**
 * Require admin role - redirect if not admin
 */
function require_admin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        // Not an admin, redirect to appropriate dashboard
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'victim') {
            header("Location: victim_dashboard.php");
        } else {
            header("Location: login.php");
        }
        exit;
    }
}

/**
 * Require victim role - redirect if not victim
 */
function require_victim() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'victim') {
        // Not a victim, redirect to appropriate dashboard
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: login.php");
        }
        exit;
    }
}

/**
 * Get victim ID for logged-in user
 */
function get_victim_id() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['victim_id']) ? $_SESSION['victim_id'] : null;
}

/**
 * Check if user is admin
 */
function is_admin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Check if user is victim
 */
function is_victim() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['role']) && $_SESSION['role'] === 'victim';
}

/**
 * Get user role
 */
function get_user_role() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}
?>
