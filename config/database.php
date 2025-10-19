<?php
/**
 * Database Configuration
 * إعدادات قاعدة البيانات
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'medical_appointments';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    public $conn;

    /**
     * Get database connection
     * الحصول على اتصال قاعدة البيانات
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            throw new Exception("Database connection failed");
        }
        
        return $this->conn;
    }
}
?>