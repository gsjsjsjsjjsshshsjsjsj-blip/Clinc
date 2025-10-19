<?php
/**
 * Notifications API Endpoints
 * نقاط نهاية واجهة برمجة التطبيقات للإشعارات
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Notification.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$database = new Database();
$db = $database->getConnection();
$notification = new Notification($db);

$action = $_GET['action'] ?? '';
$response = ['success' => false, 'message' => '', 'data' => null];

try {
    // Check if user is logged in
    if (!User::isLoggedIn()) {
        throw new Exception('يجب تسجيل الدخول أولاً');
    }

    session_start();
    $user_id = $_SESSION['user_id'];

    switch ($action) {
        case 'get_notifications':
            $unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
            $limit = intval($_GET['limit'] ?? 20);
            $offset = intval($_GET['offset'] ?? 0);
            
            $notifications = $notification->getUserNotifications($user_id, $unread_only, $limit, $offset);
            
            $response['success'] = true;
            $response['data'] = $notifications;
            break;

        case 'get_unread_count':
            $count = $notification->getUnreadCount($user_id);
            
            $response['success'] = true;
            $response['data'] = ['count' => $count];
            break;

        case 'mark_as_read':
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                throw new Exception('Method not allowed');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $notification_id = $input['notification_id'] ?? '';
            
            if (empty($notification_id)) {
                throw new Exception('معرف الإشعار مطلوب');
            }
            
            if ($notification->markAsRead($notification_id, $user_id)) {
                $response['success'] = true;
                $response['message'] = 'تم تمييز الإشعار كمقروء';
            } else {
                throw new Exception('فشل في تمييز الإشعار كمقروء');
            }
            break;

        case 'mark_all_as_read':
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                throw new Exception('Method not allowed');
            }
            
            if ($notification->markAllAsRead($user_id)) {
                $response['success'] = true;
                $response['message'] = 'تم تمييز جميع الإشعارات كمقروءة';
            } else {
                throw new Exception('فشل في تمييز الإشعارات كمقروءة');
            }
            break;

        case 'delete_notification':
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                throw new Exception('Method not allowed');
            }
            
            $notification_id = $_GET['id'] ?? '';
            
            if (empty($notification_id)) {
                throw new Exception('معرف الإشعار مطلوب');
            }
            
            if ($notification->deleteNotification($notification_id, $user_id)) {
                $response['success'] = true;
                $response['message'] = 'تم حذف الإشعار';
            } else {
                throw new Exception('فشل في حذف الإشعار');
            }
            break;

        case 'send_notification':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            // Only admins can send notifications
            if ($_SESSION['user_role'] !== 'admin') {
                throw new Exception('غير مسموح لك بإرسال الإشعارات');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            $required_fields = ['user_id', 'title', 'message'];
            foreach ($required_fields as $field) {
                if (empty($input[$field])) {
                    throw new Exception("الحقل {$field} مطلوب");
                }
            }
            
            $type = $input['type'] ?? 'system';
            $related_id = $input['related_id'] ?? null;
            
            if ($notification->sendNotification($input['user_id'], $input['title'], $input['message'], $type, $related_id)) {
                $response['success'] = true;
                $response['message'] = 'تم إرسال الإشعار بنجاح';
            } else {
                throw new Exception('فشل في إرسال الإشعار');
            }
            break;

        case 'get_realtime_notifications':
            $realtime_notifications = Notification::getRealTimeNotifications($user_id);
            
            $response['success'] = true;
            $response['data'] = $realtime_notifications;
            break;

        case 'send_appointment_reminders':
            // Only admins can trigger reminders
            if ($_SESSION['user_role'] !== 'admin') {
                throw new Exception('غير مسموح لك بإرسال التذكيرات');
            }
            
            $sent_count = $notification->sendAppointmentReminders();
            
            $response['success'] = true;
            $response['message'] = "تم إرسال {$sent_count} تذكير";
            $response['data'] = ['sent_count' => $sent_count];
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