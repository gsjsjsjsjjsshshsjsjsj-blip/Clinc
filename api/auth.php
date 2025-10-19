<?php
/**
 * Authentication API Endpoints
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/User.php';

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
                    registerUser($user);
                    break;
                case 'login':
                    loginUser($user);
                    break;
                case 'logout':
                    logoutUser();
                    break;
                default:
                    throw new Exception('Invalid action');
            }
            break;
        case 'GET':
            switch($action) {
                case 'profile':
                    getProfile($user);
                    break;
                case 'check_auth':
                    checkAuth();
                    break;
                default:
                    throw new Exception('Invalid action');
            }
            break;
        default:
            throw new Exception('Method not allowed');
    }
} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function registerUser($user) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Validate required fields
    $required_fields = ['full_name', 'email', 'password', 'gender'];
    foreach($required_fields as $field) {
        if(empty($data[$field])) {
            throw new Exception("حقل {$field} مطلوب");
        }
    }

    // Validate email format
    if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("البريد الإلكتروني غير صحيح");
    }

    // Validate password strength
    if(strlen($data['password']) < PASSWORD_MIN_LENGTH) {
        throw new Exception("كلمة المرور يجب أن تكون على الأقل " . PASSWORD_MIN_LENGTH . " أحرف");
    }

    // Check if email already exists
    $user->email = $data['email'];
    if($user->emailExists()) {
        throw new Exception("البريد الإلكتروني مستخدم بالفعل");
    }

    // Set user data
    $user->full_name = $data['full_name'];
    $user->email = $data['email'];
    $user->password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
    $user->phone = $data['phone'] ?? null;
    $user->date_of_birth = $data['date_of_birth'] ?? null;
    $user->gender = $data['gender'];
    $user->role = $data['role'] ?? 'patient';

    if($user->register()) {
        echo json_encode([
            'success' => true,
            'message' => 'تم إنشاء الحساب بنجاح',
            'user_id' => $user->id
        ]);
    } else {
        throw new Exception('فشل في إنشاء الحساب');
    }
}

function loginUser($user) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(empty($data['email']) || empty($data['password'])) {
        throw new Exception("البريد الإلكتروني وكلمة المرور مطلوبان");
    }

    if($user->login($data['email'], $data['password'])) {
        echo json_encode([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'user' => [
                'id' => $user->id,
                'name' => $user->full_name,
                'email' => $user->email,
                'role' => $user->role
            ]
        ]);
    } else {
        throw new Exception('البريد الإلكتروني أو كلمة المرور غير صحيحة');
    }
}

function logoutUser() {
    User::logout();
    echo json_encode([
        'success' => true,
        'message' => 'تم تسجيل الخروج بنجاح'
    ]);
}

function getProfile($user) {
    if(!User::isLoggedIn()) {
        throw new Exception('يجب تسجيل الدخول أولاً');
    }

    $user_id = $_SESSION['user_id'];
    if($user->getUserById($user_id)) {
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'date_of_birth' => $user->date_of_birth,
                'gender' => $user->gender,
                'role' => $user->role,
                'profile_image' => $user->profile_image,
                'email_verified' => $user->email_verified
            ]
        ]);
    } else {
        throw new Exception('المستخدم غير موجود');
    }
}

function checkAuth() {
    if(User::isLoggedIn()) {
        echo json_encode([
            'success' => true,
            'authenticated' => true,
            'user' => User::getCurrentUser()
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'authenticated' => false
        ]);
    }
}
?>