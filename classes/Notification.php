<?php
/**
 * Notification Class
 * Handles notification management
 */

require_once __DIR__ . '/../config/config.php';

class Notification {
    private $conn;
    private $table_name = "notifications";

    public $id;
    public $user_id;
    public $title;
    public $message;
    public $type;
    public $is_read;
    public $action_url;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new notification
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, title, message, type, action_url) 
                  VALUES (:user_id, :title, :message, :type, :action_url)";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->message = htmlspecialchars(strip_tags($this->message));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->action_url = htmlspecialchars(strip_tags($this->action_url));

        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":message", $this->message);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":action_url", $this->action_url);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Get user notifications
    public function getUserNotifications($user_id, $limit = 20, $unread_only = false) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = :user_id";

        if($unread_only) {
            $query .= " AND is_read = 0";
        }

        $query .= " ORDER BY created_at DESC LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Mark notification as read
    public function markAsRead($notification_id, $user_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_read = 1 
                  WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $notification_id);
        $stmt->bindParam(":user_id", $user_id);

        return $stmt->execute();
    }

    // Mark all notifications as read
    public function markAllAsRead($user_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_read = 1 
                  WHERE user_id = :user_id AND is_read = 0";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);

        return $stmt->execute();
    }

    // Get unread count
    public function getUnreadCount($user_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND is_read = 0";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    // Delete old notifications (older than 30 days)
    public function deleteOldNotifications($days = 30) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":days", $days, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // Send appointment reminder
    public function sendAppointmentReminder($appointment_id) {
        $query = "SELECT a.*, u.full_name as patient_name, u.email as patient_email,
                         d.consultation_fee, doc_user.full_name as doctor_name
                  FROM appointments a
                  LEFT JOIN users u ON a.patient_id = u.id
                  LEFT JOIN doctors d ON a.doctor_id = d.id
                  LEFT JOIN users doc_user ON d.user_id = doc_user.id
                  WHERE a.id = :appointment_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":appointment_id", $appointment_id);
        $stmt->execute();

        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

        if($appointment) {
            $title = "تذكير بالموعد";
            $message = "موعدك مع د. " . $appointment['doctor_name'] . " غداً في " . 
                      $appointment['appointment_time'] . " - " . $appointment['appointment_date'];

            $this->user_id = $appointment['patient_id'];
            $this->title = $title;
            $this->message = $message;
            $this->type = 'info';
            $this->action_url = '/appointments/' . $appointment_id;

            return $this->create();
        }

        return false;
    }

    // Send appointment confirmation
    public function sendAppointmentConfirmation($appointment_id) {
        $query = "SELECT a.*, u.full_name as patient_name, u.email as patient_email,
                         d.consultation_fee, doc_user.full_name as doctor_name
                  FROM appointments a
                  LEFT JOIN users u ON a.patient_id = u.id
                  LEFT JOIN doctors d ON a.doctor_id = d.id
                  LEFT JOIN users doc_user ON d.user_id = doc_user.id
                  WHERE a.id = :appointment_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":appointment_id", $appointment_id);
        $stmt->execute();

        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

        if($appointment) {
            $title = "تم تأكيد موعدك";
            $message = "تم تأكيد موعدك مع د. " . $appointment['doctor_name'] . " في " . 
                      $appointment['appointment_date'] . " في " . $appointment['appointment_time'];

            $this->user_id = $appointment['patient_id'];
            $this->title = $title;
            $this->message = $message;
            $this->type = 'success';
            $this->action_url = '/appointments/' . $appointment_id;

            return $this->create();
        }

        return false;
    }

    // Send appointment cancellation
    public function sendAppointmentCancellation($appointment_id, $reason = null) {
        $query = "SELECT a.*, u.full_name as patient_name, u.email as patient_email,
                         d.consultation_fee, doc_user.full_name as doctor_name
                  FROM appointments a
                  LEFT JOIN users u ON a.patient_id = u.id
                  LEFT JOIN doctors d ON a.doctor_id = d.id
                  LEFT JOIN users doc_user ON d.user_id = doc_user.id
                  WHERE a.id = :appointment_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":appointment_id", $appointment_id);
        $stmt->execute();

        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

        if($appointment) {
            $title = "تم إلغاء موعدك";
            $message = "تم إلغاء موعدك مع د. " . $appointment['doctor_name'] . " في " . 
                      $appointment['appointment_date'] . " في " . $appointment['appointment_time'];
            
            if($reason) {
                $message .= "\nالسبب: " . $reason;
            }

            $this->user_id = $appointment['patient_id'];
            $this->title = $title;
            $this->message = $message;
            $this->type = 'warning';
            $this->action_url = '/appointments';

            return $this->create();
        }

        return false;
    }
}
?>