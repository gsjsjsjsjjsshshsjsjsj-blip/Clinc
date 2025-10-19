<?php
/**
 * نظام الإشعارات
 * Notifications System
 */

require_once __DIR__ . '/../config/config.php';

class Notifications {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * الحصول على إشعارات المستخدم
     * Get user notifications
     */
    public function getUserNotifications($userId, $unreadOnly = false, $limit = 20) {
        try {
            $sql = "
                SELECT * FROM notifications
                WHERE user_id = ?
            ";
            
            $params = [$userId];
            
            if ($unreadOnly) {
                $sql .= " AND is_read = 0";
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return ['success' => true, 'notifications' => $stmt->fetchAll()];
            
        } catch (Exception $e) {
            error_log("Get Notifications Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ في جلب الإشعارات'];
        }
    }
    
    /**
     * الحصول على عدد الإشعارات غير المقروءة
     * Get unread notifications count
     */
    public function getUnreadCount($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM notifications 
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return ['success' => true, 'count' => $result['count']];
            
        } catch (Exception $e) {
            error_log("Get Unread Count Error: " . $e->getMessage());
            return ['success' => false, 'count' => 0];
        }
    }
    
    /**
     * تعليم إشعار كمقروء
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$notificationId, $userId]);
            
            return ['success' => true, 'message' => 'تم تحديث الإشعار'];
            
        } catch (Exception $e) {
            error_log("Mark Read Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ في تحديث الإشعار'];
        }
    }
    
    /**
     * تعليم جميع الإشعارات كمقروءة
     * Mark all notifications as read
     */
    public function markAllAsRead($userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = NOW()
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->execute([$userId]);
            
            return ['success' => true, 'message' => 'تم تحديث جميع الإشعارات'];
            
        } catch (Exception $e) {
            error_log("Mark All Read Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ في تحديث الإشعارات'];
        }
    }
    
    /**
     * حذف إشعار
     * Delete notification
     */
    public function deleteNotification($notificationId, $userId) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM notifications 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$notificationId, $userId]);
            
            return ['success' => true, 'message' => 'تم حذف الإشعار'];
            
        } catch (Exception $e) {
            error_log("Delete Notification Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ في حذف الإشعار'];
        }
    }
    
    /**
     * إرسال إشعار تذكير بالموعد
     * Send appointment reminder
     */
    public function sendAppointmentReminders() {
        try {
            // الحصول على المواعيد القادمة في الـ 24 ساعة القادمة
            $stmt = $this->db->query("
                SELECT a.id, a.appointment_date, a.appointment_time,
                       p.user_id as patient_user_id, 
                       d.user_id as doctor_user_id,
                       pu.full_name as patient_name,
                       du.full_name as doctor_name
                FROM appointments a
                JOIN patients p ON a.patient_id = p.id
                JOIN doctors d ON a.doctor_id = d.id
                JOIN users pu ON p.user_id = pu.id
                JOIN users du ON d.user_id = du.id
                WHERE a.status = 'confirmed'
                AND CONCAT(a.appointment_date, ' ', a.appointment_time) 
                    BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)
                AND NOT EXISTS (
                    SELECT 1 FROM notifications 
                    WHERE related_id = a.id 
                    AND type = 'reminder'
                    AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                )
            ");
            
            $appointments = $stmt->fetchAll();
            $count = 0;
            
            foreach ($appointments as $appointment) {
                $dateTime = date('Y-m-d h:i A', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']));
                
                // إشعار للمريض
                $this->sendNotification(
                    $appointment['patient_user_id'],
                    'تذكير بموعدك',
                    "لديك موعد مع د. {$appointment['doctor_name']} في $dateTime",
                    'reminder',
                    $appointment['id']
                );
                
                // إشعار للطبيب
                $this->sendNotification(
                    $appointment['doctor_user_id'],
                    'تذكير بموعد',
                    "لديك موعد مع {$appointment['patient_name']} في $dateTime",
                    'reminder',
                    $appointment['id']
                );
                
                $count++;
            }
            
            return ['success' => true, 'sent' => $count];
            
        } catch (Exception $e) {
            error_log("Send Reminders Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ في إرسال التذكيرات'];
        }
    }
    
    /**
     * إرسال إشعار
     * Send notification
     */
    private function sendNotification($userId, $title, $message, $type, $relatedId = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, title, message, type, related_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $title, $message, $type, $relatedId]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Send Notification Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * إرسال بريد إلكتروني
     * Send email notification
     */
    public function sendEmailNotification($to, $subject, $body) {
        if (!ENABLE_EMAIL_NOTIFICATIONS) {
            return false;
        }
        
        // يمكن تنفيذ إرسال البريد الإلكتروني هنا باستخدام PHPMailer
        // This is a placeholder for email sending functionality
        
        try {
            // مثال بسيط باستخدام mail() - في الإنتاج استخدم PHPMailer
            $headers = "From: " . SITE_NAME . " <noreply@shifa.sa>\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
            return mail($to, $subject, $body, $headers);
            
        } catch (Exception $e) {
            error_log("Send Email Error: " . $e->getMessage());
            return false;
        }
    }
}
?>
