<?php
/**
 * Authentication API Endpoints
 * نقاط نهاية واجهة برمجة التطبيقات للمصادقة
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../classes/User.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$action = $_GET['action'] ?? '';
$response = ['success' => false, 'message' => '', 'data' => null];

try {
    switch ($action) {
        case 'register':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            $required_fields = ['first_name', 'last_name', 'email', 'phone', 'password', 'gender'];
            foreach ($required_fields as $field) {
                if (empty($input[$field])) {
                    throw new Exception("الحقل {$field} مطلوب");
                }
            }
            
            // Set user properties
            $user->first_name = $input['first_name'];
            $user->last_name = $input['last_name'];
            $user->email = $input['email'];
            $user->phone = $input['phone'];
            $user->password_hash = $input['password'];
            $user->date_of_birth = $input['date_of_birth'] ?? null;
            $user->gender = $input['gender'];
            $user->national_id = $input['national_id'] ?? null;
            $user->address = $input['address'] ?? null;
            $user->city = $input['city'] ?? null;
            
            if ($user->register()) {
                $response['success'] = true;
                $response['message'] = 'تم إنشاء الحساب بنجاح';
                $response['data'] = [
                    'user_id' => $user->id,
                    'email' => $user->email
                ];
            } else {
                throw new Exception('فشل في إنشاء الحساب. قد يكون البريد الإلكتروني مستخدماً مسبقاً');
            }
            break;

        case 'login':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['email']) || empty($input['password'])) {
                throw new Exception('البريد الإلكتروني وكلمة المرور مطلوبان');
            }
            
            if ($user->login($input['email'], $input['password'])) {
                $response['success'] = true;
                $response['message'] = 'تم تسجيل الدخول بنجاح';
                $response['data'] = [
                    'user_id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'session_token' => $_SESSION['session_token'] ?? null
                ];
            } else {
                throw new Exception('البريد الإلكتروني أو كلمة المرور غير صحيحة');
            }
            break;

        case 'logout':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            if ($user->logout()) {
                $response['success'] = true;
                $response['message'] = 'تم تسجيل الخروج بنجاح';
            } else {
                throw new Exception('حدث خطأ أثناء تسجيل الخروج');
            }
            break;

        case 'check_session':
            if (User::isLoggedIn()) {
                session_start();
                $response['success'] = true;
                $response['message'] = 'المستخدم مسجل الدخول';
                $response['data'] = [
                    'user_id' => $_SESSION['user_id'],
                    'name' => $_SESSION['user_name'],
                    'email' => $_SESSION['user_email'],
                    'role' => $_SESSION['user_role']
                ];
            } else {
                throw new Exception('المستخدم غير مسجل الدخول');
            }
            break;

        case 'update_profile':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            if (!User::isLoggedIn()) {
                throw new Exception('يجب تسجيل الدخول أولاً');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            session_start();
            
            $user->id = $_SESSION['user_id'];
            $user->first_name = $input['first_name'];
            $user->last_name = $input['last_name'];
            $user->phone = $input['phone'];
            $user->date_of_birth = $input['date_of_birth'] ?? null;
            $user->address = $input['address'] ?? null;
            $user->city = $input['city'] ?? null;
            
            if ($user->updateProfile()) {
                $response['success'] = true;
                $response['message'] = 'تم تحديث الملف الشخصي بنجاح';
            } else {
                throw new Exception('فشل في تحديث الملف الشخصي');
            }
            break;

        case 'change_password':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            if (!User::isLoggedIn()) {
                throw new Exception('يجب تسجيل الدخول أولاً');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['current_password']) || empty($input['new_password'])) {
                throw new Exception('كلمة المرور الحالية والجديدة مطلوبتان');
            }
            
            session_start();
            
            // Verify current password
            $query = "SELECT password_hash FROM users WHERE id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":user_id", $_SESSION['user_id']);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                throw new Exception('المستخدم غير موجود');
            }
            
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!password_verify($input['current_password'], $user_data['password_hash'])) {
                throw new Exception('كلمة المرور الحالية غير صحيحة');
            }
            
            // Update password
            $new_password_hash = password_hash($input['new_password'], PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password_hash = :password_hash WHERE id = :user_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(":password_hash", $new_password_hash);
            $update_stmt->bindParam(":user_id", $_SESSION['user_id']);
            
            if ($update_stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'تم تغيير كلمة المرور بنجاح';
            } else {
                throw new Exception('فشل في تغيير كلمة المرور');
            }
            break;

        default:
            throw new Exception('عملية غير مدعومة');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>