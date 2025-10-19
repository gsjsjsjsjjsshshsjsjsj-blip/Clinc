<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';
require_once '../config/config.php';
require_once '../classes/User.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch($method) {
        case 'POST':
            switch($action) {
                case 'register':
                    handleRegister($user);
                    break;
                case 'login':
                    handleLogin($user);
                    break;
                case 'logout':
                    handleLogout($user);
                    break;
                default:
                    send_json_response(['error' => 'إجراء غير صحيح'], 400);
            }
            break;
        case 'GET':
            switch($action) {
                case 'profile':
                    handleGetProfile($user);
                    break;
                default:
                    send_json_response(['error' => 'إجراء غير صحيح'], 400);
            }
            break;
        default:
            send_json_response(['error' => 'طريقة طلب غير مدعومة'], 405);
    }
} catch(Exception $e) {
    error_log("Auth API Error: " . $e->getMessage());
    send_json_response(['error' => 'حدث خطأ في الخادم'], 500);
}

/**
 * Handle user registration
 * معالجة تسجيل المستخدم
 */
function handleRegister($user) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    // التحقق من البيانات المطلوبة
    if(empty($data['full_name']) || empty($data['email']) || empty($data['password'])) {
        send_json_response(['error' => 'جميع الحقول مطلوبة'], 400);
    }

    // التحقق من صحة البريد الإلكتروني
    if(!validate_email($data['email'])) {
        send_json_response(['error' => 'البريد الإلكتروني غير صحيح'], 400);
    }

    // التحقق من قوة كلمة المرور
    if(!validate_password($data['password'])) {
        send_json_response(['error' => 'كلمة المرور يجب أن تحتوي على 8 أحرف على الأقل وتشمل حرف كبير وصغير ورقم'], 400);
    }

    // التحقق من وجود البريد الإلكتروني
    if($user->emailExists($data['email'])) {
        send_json_response(['error' => 'البريد الإلكتروني مستخدم بالفعل'], 409);
    }

    // إعداد بيانات المستخدم
    $user->full_name = $data['full_name'];
    $user->email = $data['email'];
    $user->password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
    $user->phone = $data['phone'] ?? null;
    $user->date_of_birth = $data['date_of_birth'] ?? null;
    $user->gender = $data['gender'] ?? 'male';
    $user->role = $data['role'] ?? 'patient';

    // إنشاء المستخدم
    if($user->create()) {
        send_json_response([
            'success' => true,
            'message' => 'تم إنشاء الحساب بنجاح',
            'user_id' => $user->id
        ], 201);
    } else {
        send_json_response(['error' => 'فشل في إنشاء الحساب'], 500);
    }
}

/**
 * Handle user login
 * معالجة تسجيل الدخول
 */
function handleLogin($user) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(empty($data['email']) || empty($data['password'])) {
        send_json_response(['error' => 'البريد الإلكتروني وكلمة المرور مطلوبان'], 400);
    }

    if($user->login($data['email'], $data['password'])) {
        send_json_response([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'role' => $user->role
            ]
        ]);
    } else {
        send_json_response(['error' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة'], 401);
    }
}

/**
 * Handle user logout
 * معالجة تسجيل الخروج
 */
function handleLogout($user) {
    if($user->logout()) {
        send_json_response([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح'
        ]);
    } else {
        send_json_response(['error' => 'فشل في تسجيل الخروج'], 500);
    }
}

/**
 * Handle get user profile
 * معالجة الحصول على ملف المستخدم
 */
function handleGetProfile($user) {
    check_auth();
    
    if($user->getById($_SESSION['user_id'])) {
        send_json_response([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'date_of_birth' => $user->date_of_birth,
                'gender' => $user->gender,
                'role' => $user->role,
                'is_active' => $user->is_active
            ]
        ]);
    } else {
        send_json_response(['error' => 'المستخدم غير موجود'], 404);
    }
}
?>