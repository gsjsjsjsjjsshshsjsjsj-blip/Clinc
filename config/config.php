<?php
/**
 * Application Configuration
 * إعدادات التطبيق
 */

// إعدادات التطبيق
define('APP_NAME', 'شفاء - نظام المواعيد الطبية');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost');

// إعدادات الأمان
define('JWT_SECRET', 'your-secret-key-here-change-in-production');
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_TIMEOUT', 3600); // ساعة واحدة

// إعدادات الملفات
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

// إعدادات الإشعارات
define('NOTIFICATION_EMAIL', 'noreply@shifa.com');
define('SMS_API_KEY', 'your-sms-api-key');
define('PUSH_NOTIFICATION_KEY', 'your-push-notification-key');

// إعدادات الوقت
date_default_timezone_set('Asia/Riyadh');

// بدء الجلسة
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// إعدادات معالجة الأخطاء
error_reporting(E_ALL);
ini_set('display_errors', 0); // إخفاء الأخطاء في الإنتاج
ini_set('log_errors', 1);
ini_set('error_log', 'logs/error.log');

// إنشاء مجلد السجلات إذا لم يكن موجوداً
if (!file_exists('logs')) {
    mkdir('logs', 0755, true);
}

// دالة لتنظيف البيانات
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// دالة للتحقق من صحة البريد الإلكتروني
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// دالة للتحقق من قوة كلمة المرور
function validate_password($password) {
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        return false;
    }
    // يجب أن تحتوي على حرف كبير وصغير ورقم
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $password);
}

// دالة لتوليد رمز عشوائي
function generate_random_code($length = 6) {
    return strtoupper(substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length));
}

// دالة لتنسيق التاريخ
function format_date($date, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($date));
}

// دالة للتحقق من صحة التاريخ
function validate_date($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// دالة لإرسال استجابة JSON
function send_json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// دالة للتحقق من تسجيل الدخول
function check_auth() {
    if (!isset($_SESSION['user_id'])) {
        send_json_response(['error' => 'غير مصرح لك بالوصول'], 401);
    }
}

// دالة للتحقق من دور المستخدم
function check_role($allowed_roles) {
    check_auth();
    if (!in_array($_SESSION['user_role'], $allowed_roles)) {
        send_json_response(['error' => 'ليس لديك صلاحية للوصول'], 403);
    }
}
?>