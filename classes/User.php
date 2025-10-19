<?php
/**
 * User Class
 * Handles user authentication and management
 */

require_once __DIR__ . '/../config/config.php';

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
    public $profile_image;
    public $is_active;
    public $email_verified;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Register new user
    public function register() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (full_name, email, password_hash, phone, date_of_birth, gender, role) 
                  VALUES (:full_name, :email, :password_hash, :phone, :date_of_birth, :gender, :role)";

        $stmt = $this->conn->prepare($query);

        // Sanitize and bind values
        $this->full_name = htmlspecialchars(strip_tags($this->full_name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->gender = htmlspecialchars(strip_tags($this->gender));
        $this->role = htmlspecialchars(strip_tags($this->role));

        $stmt->bindParam(":full_name", $this->full_name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password_hash", $this->password_hash);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":date_of_birth", $this->date_of_birth);
        $stmt->bindParam(":gender", $this->gender);
        $stmt->bindParam(":role", $this->role);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Login user
    public function login($email, $password) {
        $query = "SELECT id, full_name, email, password_hash, role, is_active, email_verified 
                  FROM " . $this->table_name . " 
                  WHERE email = :email AND is_active = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(password_verify($password, $row['password_hash'])) {
                $this->id = $row['id'];
                $this->full_name = $row['full_name'];
                $this->email = $row['email'];
                $this->role = $row['role'];
                $this->is_active = $row['is_active'];
                $this->email_verified = $row['email_verified'];
                
                // Set session variables
                $_SESSION['user_id'] = $this->id;
                $_SESSION['user_name'] = $this->full_name;
                $_SESSION['user_email'] = $this->email;
                $_SESSION['user_role'] = $this->role;
                $_SESSION['login_time'] = time();
                
                return true;
            }
        }
        return false;
    }

    // Check if email exists
    public function emailExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Get user by ID
    public function getUserById($id) {
        $query = "SELECT id, full_name, email, phone, date_of_birth, gender, role, profile_image, 
                         is_active, email_verified, created_at 
                  FROM " . $this->table_name . " 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
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
            $this->profile_image = $row['profile_image'];
            $this->is_active = $row['is_active'];
            $this->email_verified = $row['email_verified'];
            return true;
        }
        return false;
    }

    // Update user profile
    public function updateProfile() {
        $query = "UPDATE " . $this->table_name . " 
                  SET full_name = :full_name, phone = :phone, date_of_birth = :date_of_birth, 
                      gender = :gender, profile_image = :profile_image, updated_at = CURRENT_TIMESTAMP 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $this->full_name = htmlspecialchars(strip_tags($this->full_name));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->gender = htmlspecialchars(strip_tags($this->gender));

        $stmt->bindParam(":full_name", $this->full_name);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":date_of_birth", $this->date_of_birth);
        $stmt->bindParam(":gender", $this->gender);
        $stmt->bindParam(":profile_image", $this->profile_image);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Change password
    public function changePassword($new_password) {
        $query = "UPDATE " . $this->table_name . " 
                  SET password_hash = :password_hash, updated_at = CURRENT_TIMESTAMP 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt->bindParam(":password_hash", $new_password_hash);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Logout user
    public static function logout() {
        session_destroy();
        return true;
    }

    // Check if user is logged in
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['login_time']) && 
               (time() - $_SESSION['login_time']) < SESSION_TIMEOUT;
    }

    // Check user role
    public static function hasRole($role) {
        return self::isLoggedIn() && $_SESSION['user_role'] === $role;
    }

    // Get current user data
    public static function getCurrentUser() {
        if(self::isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'email' => $_SESSION['user_email'],
                'role' => $_SESSION['user_role']
            ];
        }
        return null;
    }
}
?>