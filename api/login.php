<?php
/**
 * معالج تسجيل الدخول
 * Login Handler
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
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'يرجى إدخال جميع الحقول المطلوبة']);
        exit;
    }
    
    $auth = new Auth();
    $result = $auth->login($email, $password);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Login API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
}
?>
