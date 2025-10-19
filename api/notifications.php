<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';
require_once '../config/config.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch($method) {
        case 'GET':
            switch($action) {
                case 'my-notifications':
                    handleGetMyNotifications($db);
                    break;
                case 'unread-count':
                    handleGetUnreadCount($db);
                    break;
                default:
                    send_json_response(['error' => 'إجراء غير صحيح'], 400);
            }
            break;
        case 'PUT':
            switch($action) {
                case 'mark-read':
                    handleMarkAsRead($db);
                    break;
                case 'mark-all-read':
                    handleMarkAllAsRead($db);
                    break;
                default:
                    send_json_response(['error' => 'إجراء غير صحيح'], 400);
            }
            break;
        default:
            send_json_response(['error' => 'طريقة طلب غير مدعومة'], 405);
    }
} catch(Exception $e) {
    error_log("Notifications API Error: " . $e->getMessage());
    send_json_response(['error' => 'حدث خطأ في الخادم'], 500);
}

/**
 * Handle get my notifications
 * معالجة الحصول على إشعاراتي
 */
function handleGetMyNotifications($db) {
    check_auth();
    
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 20;
    $offset = ($page - 1) * $limit;
    
    $query = "SELECT id, title, message, type, is_read, created_at 
              FROM notifications 
              WHERE user_id = :user_id 
              ORDER BY created_at DESC 
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // تنسيق التواريخ
    foreach($notifications as &$notification) {
        $notification['created_at_formatted'] = format_date($notification['created_at'], 'Y-m-d H:i');
        $notification['time_ago'] = getTimeAgo($notification['created_at']);
    }
    
    send_json_response([
        'success' => true,
        'notifications' => $notifications,
        'pagination' => [
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total' => count($notifications)
        ]
    ]);
}

/**
 * Handle get unread count
 * معالجة الحصول على عدد الإشعارات غير المقروءة
 */
function handleGetUnreadCount($db) {
    check_auth();
    
    $query = "SELECT COUNT(*) as unread_count 
              FROM notifications 
              WHERE user_id = :user_id AND is_read = 0";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    send_json_response([
        'success' => true,
        'unread_count' => (int)$result['unread_count']
    ]);
}

/**
 * Handle mark notification as read
 * معالجة وضع علامة مقروء على الإشعار
 */
function handleMarkAsRead($db) {
    check_auth();
    
    $notification_id = $_GET['id'] ?? null;
    
    if(!$notification_id) {
        send_json_response(['error' => 'معرف الإشعار مطلوب'], 400);
    }
    
    $query = "UPDATE notifications 
              SET is_read = 1 
              WHERE id = :id AND user_id = :user_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $notification_id);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    
    if($stmt->execute() && $stmt->rowCount() > 0) {
        send_json_response([
            'success' => true,
            'message' => 'تم وضع علامة مقروء على الإشعار'
        ]);
    } else {
        send_json_response(['error' => 'الإشعار غير موجود أو لا يمكن تحديثه'], 404);
    }
}

/**
 * Handle mark all notifications as read
 * معالجة وضع علامة مقروء على جميع الإشعارات
 */
function handleMarkAllAsRead($db) {
    check_auth();
    
    $query = "UPDATE notifications 
              SET is_read = 1 
              WHERE user_id = :user_id AND is_read = 0";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    
    if($stmt->execute()) {
        send_json_response([
            'success' => true,
            'message' => 'تم وضع علامة مقروء على جميع الإشعارات'
        ]);
    } else {
        send_json_response(['error' => 'فشل في تحديث الإشعارات'], 500);
    }
}

/**
 * Get time ago string
 * الحصول على نص "منذ وقت"
 */
function getTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if($time < 60) {
        return 'الآن';
    } elseif($time < 3600) {
        $minutes = floor($time / 60);
        return "منذ $minutes دقيقة";
    } elseif($time < 86400) {
        $hours = floor($time / 3600);
        return "منذ $hours ساعة";
    } elseif($time < 2592000) {
        $days = floor($time / 86400);
        return "منذ $days يوم";
    } else {
        return format_date($datetime, 'Y-m-d');
    }
}
?>