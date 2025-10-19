<?php
/**
 * ملف الإعدادات العامة للنظام
 * General System Configuration
 */

// بدء الجلسة
session_start();

// إعدادات النظام
define('SITE_NAME', 'شفاء - نظام المواعيد الطبية');
define('SITE_URL', 'http://localhost/medical-appointments');
define('TIMEZONE', 'Asia/Riyadh');

// تعيين المنطقة الزمنية
date_default_timezone_set(TIMEZONE);

// إعدادات الأمان
define('HASH_ALGORITHM', 'sha256');
define('SESSION_LIFETIME', 3600); // ساعة واحدة
define('MAX_LOGIN_ATTEMPTS', 5);

// إعدادات الإشعارات
define('ENABLE_EMAIL_NOTIFICATIONS', true);
define('ENABLE_SMS_NOTIFICATIONS', false);

// إعدادات رفع الملفات
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf']);

// أدوار المستخدمين
define('ROLE_ADMIN', 'admin');
define('ROLE_DOCTOR', 'doctor');
define('ROLE_PATIENT', 'patient');

// حالات المواعيد
define('APPOINTMENT_PENDING', 'pending');
define('APPOINTMENT_CONFIRMED', 'confirmed');
define('APPOINTMENT_CANCELLED', 'cancelled');
define('APPOINTMENT_COMPLETED', 'completed');

// تضمين ملف قاعدة البيانات
require_once __DIR__ . '/database.php';

/**
 * معالجة الأخطاء المخصصة
 * Custom Error Handler
 */
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno]: $errstr in $errfile on line $errline");
    
    // عدم عرض تفاصيل الخطأ في بيئة الإنتاج
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
        return true;
    }
    return false;
}

set_error_handler("customErrorHandler");

/**
 * تنظيف البيانات المدخلة
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * التحقق من صحة البريد الإلكتروني
 * Validate email address
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * التحقق من صحة رقم الهاتف السعودي
 * Validate Saudi phone number
 */
function validatePhone($phone) {
    // نمط الأرقام السعودية: 05xxxxxxxx أو 9665xxxxxxxx
    $pattern = '/^(05|9665)\d{8}$/';
    return preg_match($pattern, $phone);
}

/**
 * إنشاء رمز عشوائي آمن
 * Generate secure random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * تشفير كلمة المرور
 * Hash password securely
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * التحقق من كلمة المرور
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * التحقق من تسجيل الدخول
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * التحقق من صلاحيات المستخدم
 * Check user role
 */
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * إعادة التوجيه
 * Redirect to URL
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * عرض رسالة نجاح
 * Set success message
 */
function setSuccessMessage($message) {
    $_SESSION['success_message'] = $message;
}

/**
 * عرض رسالة خطأ
 * Set error message
 */
function setErrorMessage($message) {
    $_SESSION['error_message'] = $message;
}

/**
 * الحصول على رسالة وحذفها
 * Get and clear message
 */
function getMessage($type) {
    $key = $type . '_message';
    if (isset($_SESSION[$key])) {
        $message = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $message;
    }
    return null;
}
?>
