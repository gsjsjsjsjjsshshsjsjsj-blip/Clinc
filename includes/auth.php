<?php
/**
 * نظام المصادقة والتحقق
 * Authentication System
 */

require_once __DIR__ . '/../config/config.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * تسجيل الدخول
     * User login
     */
    public function login($email, $password) {
        try {
            // التحقق من البيانات
            $email = sanitize($email);
            
            if (!validateEmail($email)) {
                return ['success' => false, 'message' => 'البريد الإلكتروني غير صحيح'];
            }
            
            // التحقق من وجود المستخدم
            $stmt = $this->db->prepare("
                SELECT id, email, password, full_name, role, is_active, 
                       login_attempts, locked_until 
                FROM users 
                WHERE email = ? AND email_verified = 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $this->logActivity(null, 'login_failed', 'users', null, ['email' => $email]);
                return ['success' => false, 'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة'];
            }
            
            // التحقق من حالة القفل
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                $remaining = ceil((strtotime($user['locked_until']) - time()) / 60);
                return ['success' => false, 'message' => "الحساب مقفل. حاول مرة أخرى بعد $remaining دقيقة"];
            }
            
            // التحقق من حالة الحساب
            if (!$user['is_active']) {
                return ['success' => false, 'message' => 'الحساب غير مفعل. يرجى التواصل مع الإدارة'];
            }
            
            // التحقق من كلمة المرور
            if (!verifyPassword($password, $user['password'])) {
                $this->handleFailedLogin($user['id'], $user['login_attempts']);
                return ['success' => false, 'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة'];
            }
            
            // إعادة تعيين محاولات تسجيل الدخول
            $this->resetLoginAttempts($user['id']);
            
            // إنشاء الجلسة
            $this->createSession($user);
            
            // تسجيل النشاط
            $this->logActivity($user['id'], 'login_success', 'users', $user['id']);
            
            // تحديث وقت آخر تسجيل دخول
            $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            return [
                'success' => true, 
                'message' => 'تم تسجيل الدخول بنجاح',
                'role' => $user['role']
            ];
            
        } catch (Exception $e) {
            error_log("Login Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ في تسجيل الدخول'];
        }
    }
    
    /**
     * التسجيل كمريض
     * Patient registration
     */
    public function registerPatient($data) {
        try {
            // التحقق من البيانات
            $email = sanitize($data['email']);
            $password = $data['password'];
            $full_name = sanitize($data['full_name']);
            $phone = sanitize($data['phone']);
            
            // التحقق من صحة البيانات
            if (!validateEmail($email)) {
                return ['success' => false, 'message' => 'البريد الإلكتروني غير صحيح'];
            }
            
            if (!validatePhone($phone)) {
                return ['success' => false, 'message' => 'رقم الهاتف غير صحيح'];
            }
            
            if (strlen($password) < 8) {
                return ['success' => false, 'message' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل'];
            }
            
            // التحقق من وجود المستخدم
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'البريد الإلكتروني مسجل مسبقاً'];
            }
            
            // إنشاء رمز التحقق
            $verificationToken = generateToken();
            
            // بدء المعاملة
            $this->db->beginTransaction();
            
            // إدراج المستخدم
            $stmt = $this->db->prepare("
                INSERT INTO users (email, password, full_name, phone, role, verification_token)
                VALUES (?, ?, ?, ?, 'patient', ?)
            ");
            $hashedPassword = hashPassword($password);
            $stmt->execute([$email, $hashedPassword, $full_name, $phone, $verificationToken]);
            $userId = $this->db->lastInsertId();
            
            // إدراج بيانات المريض
            $stmt = $this->db->prepare("INSERT INTO patients (user_id) VALUES (?)");
            $stmt->execute([$userId]);
            
            // تسجيل النشاط
            $this->logActivity($userId, 'user_registered', 'users', $userId);
            
            // تأكيد المعاملة
            $this->db->commit();
            
            // إرسال بريد التحقق (إذا كان مفعلاً)
            if (ENABLE_EMAIL_NOTIFICATIONS) {
                $this->sendVerificationEmail($email, $full_name, $verificationToken);
            }
            
            return [
                'success' => true, 
                'message' => 'تم التسجيل بنجاح. يرجى التحقق من بريدك الإلكتروني',
                'user_id' => $userId
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Registration Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ في التسجيل'];
        }
    }
    
    /**
     * تسجيل الخروج
     * User logout
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'users', $_SESSION['user_id']);
        }
        
        session_unset();
        session_destroy();
        
        return ['success' => true, 'message' => 'تم تسجيل الخروج بنجاح'];
    }
    
    /**
     * معالجة فشل تسجيل الدخول
     * Handle failed login attempt
     */
    private function handleFailedLogin($userId, $currentAttempts) {
        $attempts = $currentAttempts + 1;
        $lockedUntil = null;
        
        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            // قفل الحساب لمدة 15 دقيقة
            $lockedUntil = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        }
        
        $stmt = $this->db->prepare("
            UPDATE users 
            SET login_attempts = ?, locked_until = ?
            WHERE id = ?
        ");
        $stmt->execute([$attempts, $lockedUntil, $userId]);
        
        $this->logActivity($userId, 'login_failed', 'users', $userId);
    }
    
    /**
     * إعادة تعيين محاولات تسجيل الدخول
     * Reset login attempts
     */
    private function resetLoginAttempts($userId) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET login_attempts = 0, locked_until = NULL 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
    }
    
    /**
     * إنشاء الجلسة
     * Create user session
     */
    private function createSession($user) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();
    }
    
    /**
     * تسجيل النشاط
     * Log activity
     */
    private function logActivity($userId, $action, $entityType, $entityId, $details = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_log (user_id, action, entity_type, entity_id, ip_address, user_agent, details)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $action,
                $entityType,
                $entityId,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $details ? json_encode($details) : null
            ]);
        } catch (Exception $e) {
            error_log("Activity Log Error: " . $e->getMessage());
        }
    }
    
    /**
     * إرسال بريد التحقق
     * Send verification email
     */
    private function sendVerificationEmail($email, $name, $token) {
        // يمكن تنفيذ إرسال البريد هنا
        // This is a placeholder for email sending functionality
        $verificationLink = SITE_URL . "/verify-email.php?token=" . $token;
        
        // في بيئة الإنتاج، استخدم مكتبة مثل PHPMailer
        // For production, use a library like PHPMailer
    }
    
    /**
     * التحقق من الجلسة
     * Check session validity
     */
    public function checkSession() {
        if (!isLoggedIn()) {
            return false;
        }
        
        // التحقق من انتهاء الجلسة
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_LIFETIME) {
            $this->logout();
            return false;
        }
        
        return true;
    }
}
?>
