<?php
/**
 * Helper Functions for AidNexus
 * Common utility functions used across the application
 */

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Format date for display
 */
function format_date($date) {
    if (empty($date)) return 'N/A';
    $timestamp = strtotime($date);
    return date('M d, Y', $timestamp);
}

/**
 * Format datetime for display
 */
function format_datetime($datetime) {
    if (empty($datetime)) return 'N/A';
    $timestamp = strtotime($datetime);
    return date('M d, Y g:i A', $timestamp);
}

/**
 * Get status badge HTML
 */
function get_status_badge($status) {
    $status = strtolower($status);
    $badges = [
        'open' => '<span class="case-status-tag status-open">Open</span>',
        'in-process' => '<span class="case-status-tag status-inprogress">In-Process</span>',
        'closed' => '<span class="case-status-tag status-closed">Closed</span>',
        'pending' => '<span class="case-status-tag status-pending">Pending</span>',
        'confirmed' => '<span class="appointment-tag tag-confirmed">Confirmed</span>',
    ];
    
    return isset($badges[$status]) ? $badges[$status] : '<span class="case-status-tag">' . ucfirst($status) . '</span>';
}

/**
 * Get user initials for avatar
 */
function get_user_initials($name) {
    $words = explode(' ', $name);
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Redirect with message
 */
function redirect_with_message($url, $message, $type = 'success') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $url");
    exit;
}

/**
 * Get and clear flash message
 */
function get_flash_message() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Log error to file
 */
function log_error($message, $context = []) {
    $log_file = __DIR__ . '/../logs/error.log';
    $log_dir = dirname($log_file);
    
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $context_str = !empty($context) ? json_encode($context) : '';
    $log_message = "[$timestamp] $message $context_str" . PHP_EOL;
    
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

/**
 * Send JSON response
 */
function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Validate email format
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Get relative time (e.g., "2 hours ago")
 */
function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    
    return format_date($datetime);
}
?>
