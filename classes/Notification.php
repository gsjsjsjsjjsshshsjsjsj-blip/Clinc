<?php
/**
 * Notification Class - فئة الإشعارات
 * Handles system notifications and alerts
 */

require_once '../config/database.php';

class Notification {
    private $conn;
    private $table_name = "notifications";

    public $id;
    public $user_id;
    public $title;
    public $message;
    public $type;
    public $related_id;
    public $is_read;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Send notification - إرسال إشعار
     */
    public function sendNotification($user_id, $title, $message, $type = 'system', $related_id = null) {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET user_id=:user_id, title=:title, message=:message, type=:type, related_id=:related_id";

        $stmt = $this->conn->prepare($query);

        // Sanitize data
        $title = htmlspecialchars(strip_tags($title));
        $message = htmlspecialchars(strip_tags($message));

        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":message", $message);
        $stmt->bindParam(":type", $type);
        $stmt->bindParam(":related_id", $related_id);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            
            // Send real-time notification if possible (WebSocket, Server-Sent Events, etc.)
            $this->sendRealTimeNotification($user_id, $title, $message, $type);
            
            return true;
        }

        return false;
    }

    /**
     * Get user notifications - الحصول على إشعارات المستخدم
     */
    public function getUserNotifications($user_id, $unread_only = false, $limit = 20, $offset = 0) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id";
        
        if ($unread_only) {
            $query .= " AND is_read = 0";
        }
        
        $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mark notification as read - تمييز الإشعار كمقروء
     */
    public function markAsRead($notification_id, $user_id) {
        $query = "UPDATE " . $this->table_name . " SET is_read = 1 
                  WHERE id = :notification_id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":notification_id", $notification_id);
        $stmt->bindParam(":user_id", $user_id);

        return $stmt->execute();
    }

    /**
     * Mark all notifications as read - تمييز جميع الإشعارات كمقروءة
     */
    public function markAllAsRead($user_id) {
        $query = "UPDATE " . $this->table_name . " SET is_read = 1 WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);

        return $stmt->execute();
    }

    /**
     * Get unread notification count - الحصول على عدد الإشعارات غير المقروءة
     */
    public function getUnreadCount($user_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND is_read = 0";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    /**
     * Delete notification - حذف الإشعار
     */
    public function deleteNotification($notification_id, $user_id) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE id = :notification_id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":notification_id", $notification_id);
        $stmt->bindParam(":user_id", $user_id);

        return $stmt->execute();
    }

    /**
     * Send appointment reminders - إرسال تذكيرات المواعيد
     */
    public function sendAppointmentReminders() {
        // Get appointments for tomorrow that haven't been reminded
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        $query = "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) as patient_name,
                         CONCAT(d_user.first_name, ' ', d_user.last_name) as doctor_name,
                         d.clinic_name
                  FROM appointments a
                  INNER JOIN users u ON a.patient_id = u.id
                  INNER JOIN doctors d ON a.doctor_id = d.id
                  INNER JOIN users d_user ON d.user_id = d_user.id
                  WHERE a.appointment_date = :tomorrow 
                  AND a.status IN ('pending', 'confirmed')
                  AND a.reminder_sent = 0";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":tomorrow", $tomorrow);
        $stmt->execute();

        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $sent_count = 0;

        foreach ($appointments as $appointment) {
            $message = "تذكير: لديك موعد غداً مع د. {$appointment['doctor_name']} في {$appointment['clinic_name']} الساعة " . 
                      date('g:i A', strtotime($appointment['appointment_time'])) . 
                      ". رمز التأكيد: {$appointment['confirmation_code']}";

            if ($this->sendNotification($appointment['patient_id'], 'تذكير بالموعد', $message, 'reminder', $appointment['id'])) {
                // Mark reminder as sent
                $update_query = "UPDATE appointments SET reminder_sent = 1 WHERE id = :appointment_id";
                $update_stmt = $this->conn->prepare($update_query);
                $update_stmt->bindParam(":appointment_id", $appointment['id']);
                $update_stmt->execute();
                
                $sent_count++;
            }
        }

        return $sent_count;
    }

    /**
     * Send real-time notification - إرسال إشعار فوري
     */
    private function sendRealTimeNotification($user_id, $title, $message, $type) {
        // This would integrate with WebSocket, Server-Sent Events, or push notifications
        // For now, we'll store it for AJAX polling
        
        $notification_data = [
            'user_id' => $user_id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'timestamp' => time()
        ];

        // Store in session or cache for real-time retrieval
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['realtime_notifications'])) {
            $_SESSION['realtime_notifications'] = [];
        }
        
        $_SESSION['realtime_notifications'][] = $notification_data;
    }

    /**
     * Get real-time notifications - الحصول على الإشعارات الفورية
     */
    public static function getRealTimeNotifications($user_id) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $notifications = [];
        
        if (isset($_SESSION['realtime_notifications'])) {
            foreach ($_SESSION['realtime_notifications'] as $key => $notification) {
                if ($notification['user_id'] == $user_id) {
                    $notifications[] = $notification;
                    unset($_SESSION['realtime_notifications'][$key]);
                }
            }
            
            // Reindex array
            $_SESSION['realtime_notifications'] = array_values($_SESSION['realtime_notifications']);
        }
        
        return $notifications;
    }

    /**
     * Send email notification - إرسال إشعار بالبريد الإلكتروني
     */
    public function sendEmailNotification($user_id, $subject, $message, $template = 'default') {
        // Get user email
        $query = "SELECT email, first_name, last_name FROM users WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            return false;
        }
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Email configuration (you would configure this based on your email service)
        $to = $user['email'];
        $from = 'noreply@shifa-medical.com';
        $headers = [
            'From' => $from,
            'Reply-To' => $from,
            'Content-Type' => 'text/html; charset=UTF-8'
        ];
        
        // Load email template
        $email_body = $this->loadEmailTemplate($template, [
            'user_name' => $user['first_name'] . ' ' . $user['last_name'],
            'subject' => $subject,
            'message' => $message
        ]);
        
        // Send email (you would use a proper email service like PHPMailer, SendGrid, etc.)
        return mail($to, $subject, $email_body, implode("\r\n", array_map(
            function($key, $value) { return "$key: $value"; },
            array_keys($headers),
            $headers
        )));
    }

    /**
     * Load email template - تحميل قالب البريد الإلكتروني
     */
    private function loadEmailTemplate($template, $variables) {
        $template_path = "../templates/email/{$template}.html";
        
        if (!file_exists($template_path)) {
            // Default template
            return "
            <html dir='rtl'>
            <body style='font-family: Arial, sans-serif; direction: rtl; text-align: right;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color: #10B981;'>مرحباً {$variables['user_name']}</h2>
                    <p>{$variables['message']}</p>
                    <hr>
                    <p style='color: #666; font-size: 12px;'>
                        هذا إشعار تلقائي من نظام شفاء للمواعيد الطبية<br>
                        لا تقم بالرد على هذا البريد الإلكتروني
                    </p>
                </div>
            </body>
            </html>";
        }
        
        $template_content = file_get_contents($template_path);
        
        // Replace variables
        foreach ($variables as $key => $value) {
            $template_content = str_replace("{{$key}}", $value, $template_content);
        }
        
        return $template_content;
    }
}
?>