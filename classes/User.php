<?php
/**
 * User Class - فئة المستخدم
 * Handles user authentication and management
 */

require_once '../config/database.php';

class User {
    private $conn;
    private $table_name = "users";

    // User properties
    public $id;
    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $password_hash;
    public $date_of_birth;
    public $gender;
    public $national_id;
    public $address;
    public $city;
    public $role;
    public $profile_image;
    public $is_active;
    public $email_verified;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Register new user - تسجيل مستخدم جديد
     */
    public function register() {
        // Validate input data
        if (!$this->validateRegistrationData()) {
            return false;
        }

        // Check if email already exists
        if ($this->emailExists()) {
            return false;
        }

        $query = "INSERT INTO " . $this->table_name . " 
                  SET first_name=:first_name, last_name=:last_name, email=:email, 
                      phone=:phone, password_hash=:password_hash, date_of_birth=:date_of_birth,
                      gender=:gender, national_id=:national_id, address=:address, city=:city";

        $stmt = $this->conn->prepare($query);

        // Sanitize data
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->password_hash = password_hash($this->password_hash, PASSWORD_DEFAULT);

        // Bind values
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":password_hash", $this->password_hash);
        $stmt->bindParam(":date_of_birth", $this->date_of_birth);
        $stmt->bindParam(":gender", $this->gender);
        $stmt->bindParam(":national_id", $this->national_id);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":city", $this->city);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    /**
     * Login user - تسجيل دخول المستخدم
     */
    public function login($email, $password) {
        $query = "SELECT id, first_name, last_name, email, phone, password_hash, role, is_active, email_verified 
                  FROM " . $this->table_name . " 
                  WHERE email = :email AND is_active = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $row['password_hash'])) {
                $this->id = $row['id'];
                $this->first_name = $row['first_name'];
                $this->last_name = $row['last_name'];
                $this->email = $row['email'];
                $this->phone = $row['phone'];
                $this->role = $row['role'];
                $this->is_active = $row['is_active'];
                $this->email_verified = $row['email_verified'];
                
                // Create session
                $this->createSession();
                return true;
            }
        }

        return false;
    }

    /**
     * Create user session - إنشاء جلسة المستخدم
     */
    private function createSession() {
        session_start();
        $_SESSION['user_id'] = $this->id;
        $_SESSION['user_name'] = $this->first_name . ' ' . $this->last_name;
        $_SESSION['user_email'] = $this->email;
        $_SESSION['user_role'] = $this->role;
        $_SESSION['logged_in'] = true;

        // Store session in database
        $session_token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $query = "INSERT INTO user_sessions SET user_id=:user_id, session_token=:session_token, 
                  ip_address=:ip_address, user_agent=:user_agent, expires_at=:expires_at";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->id);
        $stmt->bindParam(":session_token", $session_token);
        $stmt->bindParam(":ip_address", $_SERVER['REMOTE_ADDR']);
        $stmt->bindParam(":user_agent", $_SERVER['HTTP_USER_AGENT']);
        $stmt->bindParam(":expires_at", $expires_at);
        $stmt->execute();

        $_SESSION['session_token'] = $session_token;
    }

    /**
     * Check if email exists - التحقق من وجود البريد الإلكتروني
     */
    public function emailExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Validate registration data - التحقق من صحة بيانات التسجيل
     */
    private function validateRegistrationData() {
        // Check required fields
        if (empty($this->first_name) || empty($this->last_name) || 
            empty($this->email) || empty($this->phone) || empty($this->password_hash)) {
            return false;
        }

        // Validate email format
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Validate phone number (Saudi format)
        if (!preg_match('/^(\+966|0)?[5][0-9]{8}$/', $this->phone)) {
            return false;
        }

        // Validate password strength
        if (strlen($this->password_hash) < 8) {
            return false;
        }

        return true;
    }

    /**
     * Get user by ID - الحصول على المستخدم بالمعرف
     */
    public function getUserById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->email = $row['email'];
            $this->phone = $row['phone'];
            $this->role = $row['role'];
            return true;
        }

        return false;
    }

    /**
     * Update user profile - تحديث ملف المستخدم
     */
    public function updateProfile() {
        $query = "UPDATE " . $this->table_name . " 
                  SET first_name=:first_name, last_name=:last_name, phone=:phone,
                      date_of_birth=:date_of_birth, address=:address, city=:city
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Sanitize data
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->phone = htmlspecialchars(strip_tags($this->phone));

        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":date_of_birth", $this->date_of_birth);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":city", $this->city);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    /**
     * Logout user - تسجيل خروج المستخدم
     */
    public function logout() {
        session_start();
        
        // Remove session from database
        if (isset($_SESSION['session_token'])) {
            $query = "DELETE FROM user_sessions WHERE session_token = :session_token";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":session_token", $_SESSION['session_token']);
            $stmt->execute();
        }

        // Destroy session
        session_destroy();
        return true;
    }

    /**
     * Check if user is logged in - التحقق من تسجيل دخول المستخدم
     */
    public static function isLoggedIn() {
        session_start();
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Get current user role - الحصول على دور المستخدم الحالي
     */
    public static function getCurrentUserRole() {
        session_start();
        return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
    }
}
?>