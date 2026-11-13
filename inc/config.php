<?php
/**
 * Configuration file for I Mobile.lk
 * Contains all global settings and constants
 */

// ===== APPLICATION SETTINGS =====
define('SITE_NAME', 'I Mobile.lk');
define('SITE_URL', 'http://localhost/imobile/');
define('ADMIN_URL', 'http://localhost/imobile/admin/');

// ===== DATABASE SETTINGS =====
define('DB_HOST', 'localhost');
define('DB_NAME', 'imobile_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ===== SECURITY SETTINGS =====
define('PASSWORD_MIN_LENGTH', 6);
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_TIMEOUT', 900); // 15 minutes in seconds

// ===== FILE UPLOAD SETTINGS =====
define('UPLOAD_DIR', 'assets/images/');
define('UPLOAD_MAX_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// ===== CURRENCY SETTINGS =====
define('CURRENCY', 'LKR');
define('CURRENCY_SYMBOL', 'LKR ');
define('CURRENCY_DECIMALS', 2);

// ===== PAGINATION SETTINGS =====
define('ITEMS_PER_PAGE', 20);
define('PRODUCTS_PER_PAGE', 12);
define('ORDERS_PER_PAGE', 20);

// ===== EMAIL SETTINGS (Future use) =====
define('MAIL_FROM', 'noreply@imobile.lk');
define('MAIL_FROM_NAME', 'I Mobile.lk');
// Admin email address for order notifications
define('ADMIN_EMAIL', 'pasindurandeepa3@gmail.com');
// SMTP settings (leave SMTP_HOST empty to use PHP mail())
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'pasindurandeepa3@gmail.com');
define('SMTP_PASS', 'wpoa sric fcrb jkkk');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // 'tls' or 'ssl' or empty

// ===== ERROR REPORTING =====
define('SHOW_ERRORS', true); // Set to false in production
if (SHOW_ERRORS) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
}

// ===== TIME ZONE =====
date_default_timezone_set('Asia/Colombo');

// ===== HELPER FUNCTIONS =====

/**
 * Redirect to a URL
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
}

/**
 * Get current user name
 */
function getCurrentUserName() {
    return isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Guest';
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return CURRENCY_SYMBOL . number_format($amount, CURRENCY_DECIMALS);
}

/**
 * Sanitize input
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (basic)
 */
function isValidPhone($phone) {
    return preg_match('/^[\d\s\-\+\(\)]{10,}$/', $phone);
}

/**
 * Generate random string
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Log error message
 */
function logError($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";
    error_log($logMessage, 3, 'logs/error.log');
}

?>
