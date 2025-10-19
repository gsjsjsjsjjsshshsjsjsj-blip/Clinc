<?php
/**
 * Notifications API Endpoints
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Notification.php';

$database = new Database();
$db = $database->getConnection();
$notification = new Notification($db);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Check authentication
if(!User::isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'يجب تسجيل الدخول أولاً'
    ]);
    exit();
}

try {
    switch($method) {
        case 'GET':
            switch($action) {
                case 'list':
                    getNotifications($notification);
                    break;
                case 'unread_count':
                    getUnreadCount($notification);
                    break;
                default:
                    throw new Exception('Invalid action');
            }
            break;
        case 'PUT':
            switch($action) {
                case 'mark_read':
                    markAsRead($notification);
                    break;
                case 'mark_all_read':
                    markAllAsRead($notification);
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

function getNotifications($notification) {
    $limit = $_GET['limit'] ?? 20;
    $unread_only = $_GET['unread_only'] ?? false;
    
    $notifications = $notification->getUserNotifications($_SESSION['user_id'], $limit, $unread_only);
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications
    ]);
}

function getUnreadCount($notification) {
    $count = $notification->getUnreadCount($_SESSION['user_id']);
    
    echo json_encode([
        'success' => true,
        'unread_count' => $count
    ]);
}

function markAsRead($notification) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(empty($data['notification_id'])) {
        throw new Exception('معرف الإشعار مطلوب');
    }

    if($notification->markAsRead($data['notification_id'], $_SESSION['user_id'])) {
        echo json_encode([
            'success' => true,
            'message' => 'تم تمييز الإشعار كمقروء'
        ]);
    } else {
        throw new Exception('فشل في تحديث الإشعار');
    }
}

function markAllAsRead($notification) {
    if($notification->markAllAsRead($_SESSION['user_id'])) {
        echo json_encode([
            'success' => true,
            'message' => 'تم تمييز جميع الإشعارات كمقروءة'
        ]);
    } else {
        throw new Exception('فشل في تحديث الإشعارات');
    }
}
?>