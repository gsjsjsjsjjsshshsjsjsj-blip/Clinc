<?php
/**
 * إعدادات قاعدة البيانات
 * Database Configuration File
 */

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'medical_appointments');
define('DB_CHARSET', 'utf8mb4');

/**
 * إنشاء اتصال آمن بقاعدة البيانات
 * Create secure database connection with error handling
 */
class Database {
    private $connection;
    private static $instance = null;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // عدم عرض تفاصيل الخطأ للمستخدم
            // Don't expose error details to users
            error_log("Database Connection Error: " . $e->getMessage());
            die("حدث خطأ في الاتصال بقاعدة البيانات. يرجى المحاولة لاحقاً.");
        }
    }
    
    /**
     * نمط Singleton لضمان اتصال واحد فقط
     * Singleton pattern for single connection
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * الحصول على الاتصال
     * Get database connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * منع الاستنساخ
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * منع إلغاء التسلسل
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>
