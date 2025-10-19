<?php
/**
 * معالج التسجيل
 * Registration Handler
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير مسموحة']);
    exit;
}

try {
    $data = [
        'email' => $_POST['email'] ?? '',
        'password' => $_POST['password'] ?? '',
        'full_name' => $_POST['full_name'] ?? '',
        'phone' => $_POST['phone'] ?? ''
    ];
    
    // التحقق من الحقول المطلوبة
    if (empty($data['email']) || empty($data['password']) || 
        empty($data['full_name']) || empty($data['phone'])) {
        echo json_encode(['success' => false, 'message' => 'يرجى إدخال جميع الحقول المطلوبة']);
        exit;
    }
    
    // التحقق من تطابق كلمة المرور
    if (isset($_POST['confirm_password']) && $data['password'] !== $_POST['confirm_password']) {
        echo json_encode(['success' => false, 'message' => 'كلمتا المرور غير متطابقتين']);
        exit;
    }
    
    $auth = new Auth();
    $result = $auth->registerPatient($data);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Register API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
}
?>
