<?php
/**
 * إدارة الإشعارات
 * Notifications API
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';

header('Content-Type: application/json');

// التحقق من تسجيل الدخول
$auth = new Auth();
if (!$auth->checkSession()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول']);
    exit;
}

try {
    $notifications = new Notifications();
    $action = $_GET['action'] ?? 'get';
    
    switch ($action) {
        case 'get':
            // الحصول على الإشعارات
            $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] == '1';
            $limit = $_GET['limit'] ?? 20;
            $result = $notifications->getUserNotifications($_SESSION['user_id'], $unreadOnly, $limit);
            break;
            
        case 'count':
            // عدد الإشعارات غير المقروءة
            $result = $notifications->getUnreadCount($_SESSION['user_id']);
            break;
            
        case 'mark_read':
            // تعليم كمقروء
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير مسموحة']);
                exit;
            }
            $notificationId = $_POST['notification_id'] ?? '';
            if (empty($notificationId)) {
                echo json_encode(['success' => false, 'message' => 'معرف الإشعار مطلوب']);
                exit;
            }
            $result = $notifications->markAsRead($notificationId, $_SESSION['user_id']);
            break;
            
        case 'mark_all_read':
            // تعليم الكل كمقروء
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير مسموحة']);
                exit;
            }
            $result = $notifications->markAllAsRead($_SESSION['user_id']);
            break;
            
        case 'delete':
            // حذف إشعار
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير مسموحة']);
                exit;
            }
            $notificationId = $_POST['notification_id'] ?? $_GET['notification_id'] ?? '';
            if (empty($notificationId)) {
                echo json_encode(['success' => false, 'message' => 'معرف الإشعار مطلوب']);
                exit;
            }
            $result = $notifications->deleteNotification($notificationId, $_SESSION['user_id']);
            break;
            
        default:
            $result = ['success' => false, 'message' => 'إجراء غير صحيح'];
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Notifications API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
}
?>
