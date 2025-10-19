<?php
require_once 'config/database.php';
require_once 'config/config.php';

/**
 * User Class
 * فئة المستخدم
 */
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $full_name;
    public $email;
    public $password_hash;
    public $phone;
    public $date_of_birth;
    public $gender;
    public $role;
    public $is_active;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create new user
     * إنشاء مستخدم جديد
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (full_name, email, password_hash, phone, date_of_birth, gender, role) 
                  VALUES (:full_name, :email, :password_hash, :phone, :date_of_birth, :gender, :role)";

        $stmt = $this->conn->prepare($query);

        // تنظيف البيانات
        $this->full_name = sanitize_input($this->full_name);
        $this->email = sanitize_input($this->email);
        $this->phone = sanitize_input($this->phone);

        // ربط المعاملات
        $stmt->bindParam(':full_name', $this->full_name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password_hash', $this->password_hash);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':date_of_birth', $this->date_of_birth);
        $stmt->bindParam(':gender', $this->gender);
        $stmt->bindParam(':role', $this->role);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    /**
     * Login user
     * تسجيل دخول المستخدم
     */
    public function login($email, $password) {
        $query = "SELECT id, full_name, email, password_hash, role, is_active 
                  FROM " . $this->table_name . " 
                  WHERE email = :email AND is_active = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(password_verify($password, $row['password_hash'])) {
                $this->id = $row['id'];
                $this->full_name = $row['full_name'];
                $this->email = $row['email'];
                $this->role = $row['role'];
                $this->is_active = $row['is_active'];
                
                // إنشاء جلسة
                $_SESSION['user_id'] = $this->id;
                $_SESSION['user_name'] = $this->full_name;
                $_SESSION['user_email'] = $this->email;
                $_SESSION['user_role'] = $this->role;
                
                return true;
            }
        }
        return false;
    }

    /**
     * Get user by ID
     * الحصول على المستخدم بالمعرف
     */
    public function getById($id) {
        $query = "SELECT id, full_name, email, phone, date_of_birth, gender, role, is_active, created_at 
                  FROM " . $this->table_name . " 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->full_name = $row['full_name'];
            $this->email = $row['email'];
            $this->phone = $row['phone'];
            $this->date_of_birth = $row['date_of_birth'];
            $this->gender = $row['gender'];
            $this->role = $row['role'];
            $this->is_active = $row['is_active'];
            return true;
        }
        return false;
    }

    /**
     * Update user
     * تحديث المستخدم
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET full_name = :full_name, phone = :phone, date_of_birth = :date_of_birth, 
                      gender = :gender, updated_at = CURRENT_TIMESTAMP 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $this->full_name = sanitize_input($this->full_name);
        $this->phone = sanitize_input($this->phone);

        $stmt->bindParam(':full_name', $this->full_name);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':date_of_birth', $this->date_of_birth);
        $stmt->bindParam(':gender', $this->gender);
        $stmt->bindParam(':id', $this->id);

        return $stmt->execute();
    }

    /**
     * Change password
     * تغيير كلمة المرور
     */
    public function changePassword($old_password, $new_password) {
        // التحقق من كلمة المرور القديمة
        $query = "SELECT password_hash FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($old_password, $row['password_hash'])) {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                $update_query = "UPDATE " . $this->table_name . " 
                                SET password_hash = :password_hash, updated_at = CURRENT_TIMESTAMP 
                                WHERE id = :id";
                $update_stmt = $this->conn->prepare($update_query);
                $update_stmt->bindParam(':password_hash', $new_hash);
                $update_stmt->bindParam(':id', $this->id);
                
                return $update_stmt->execute();
            }
        }
        return false;
    }

    /**
     * Check if email exists
     * التحقق من وجود البريد الإلكتروني
     */
    public function emailExists($email) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Get all users with pagination
     * الحصول على جميع المستخدمين مع التصفح
     */
    public function getAll($page = 1, $limit = 10, $role = null) {
        $offset = ($page - 1) * $limit;
        
        $where_clause = "";
        if($role) {
            $where_clause = "WHERE role = :role";
        }
        
        $query = "SELECT id, full_name, email, phone, gender, role, is_active, created_at 
                  FROM " . $this->table_name . " 
                  " . $where_clause . " 
                  ORDER BY created_at DESC 
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        
        if($role) {
            $stmt->bindParam(':role', $role);
        }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Logout user
     * تسجيل خروج المستخدم
     */
    public function logout() {
        session_destroy();
        return true;
    }
}
?>