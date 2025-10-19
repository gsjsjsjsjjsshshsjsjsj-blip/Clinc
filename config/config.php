<?php
/**
 * Application Configuration
 * Medical Appointment System
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Application settings
define('APP_NAME', 'شفاء - نظام المواعيد الطبية');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost');

// Security settings
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_TIMEOUT', 3600); // 1 hour

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Email settings (configure with your SMTP settings)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', 'noreply@shifa.com');
define('FROM_NAME', 'نظام شفاء');

// Timezone
date_default_timezone_set('Asia/Riyadh');

// Include database configuration
require_once __DIR__ . '/database.php';
?>