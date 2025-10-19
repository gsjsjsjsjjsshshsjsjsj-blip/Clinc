<?php
/**
 * Appointment Class - فئة المواعيد
 * Handles appointment booking and management
 */

require_once '../config/database.php';

class Appointment {
    private $conn;
    private $table_name = "appointments";

    // Appointment properties
    public $id;
    public $patient_id;
    public $doctor_id;
    public $appointment_date;
    public $appointment_time;
    public $duration_minutes;
    public $status;
    public $appointment_type;
    public $notes;
    public $symptoms;
    public $insurance_id;
    public $total_fee;
    public $paid_amount;
    public $payment_status;
    public $payment_method;
    public $confirmation_code;
    public $reminder_sent;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Book new appointment - حجز موعد جديد
     */
    public function bookAppointment() {
        // Check if time slot is available
        if (!$this->isTimeSlotAvailable()) {
            return ['success' => false, 'message' => 'الموعد المحدد غير متاح'];
        }

        // Generate confirmation code
        $this->confirmation_code = $this->generateConfirmationCode();

        $query = "INSERT INTO " . $this->table_name . " 
                  SET patient_id=:patient_id, doctor_id=:doctor_id, appointment_date=:appointment_date,
                      appointment_time=:appointment_time, duration_minutes=:duration_minutes,
                      appointment_type=:appointment_type, notes=:notes, symptoms=:symptoms,
                      insurance_id=:insurance_id, total_fee=:total_fee, confirmation_code=:confirmation_code";

        $stmt = $this->conn->prepare($query);

        // Sanitize data
        $this->notes = htmlspecialchars(strip_tags($this->notes));
        $this->symptoms = htmlspecialchars(strip_tags($this->symptoms));

        $stmt->bindParam(":patient_id", $this->patient_id);
        $stmt->bindParam(":doctor_id", $this->doctor_id);
        $stmt->bindParam(":appointment_date", $this->appointment_date);
        $stmt->bindParam(":appointment_time", $this->appointment_time);
        $stmt->bindParam(":duration_minutes", $this->duration_minutes);
        $stmt->bindParam(":appointment_type", $this->appointment_type);
        $stmt->bindParam(":notes", $this->notes);
        $stmt->bindParam(":symptoms", $this->symptoms);
        $stmt->bindParam(":insurance_id", $this->insurance_id);
        $stmt->bindParam(":total_fee", $this->total_fee);
        $stmt->bindParam(":confirmation_code", $this->confirmation_code);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            
            // Send notifications
            $this->sendBookingNotifications();
            
            return [
                'success' => true, 
                'message' => 'تم حجز الموعد بنجاح',
                'appointment_id' => $this->id,
                'confirmation_code' => $this->confirmation_code
            ];
        }

        return ['success' => false, 'message' => 'حدث خطأ أثناء حجز الموعد'];
    }

    /**
     * Check if time slot is available - التحقق من توفر الموعد
     */
    private function isTimeSlotAvailable() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE doctor_id = :doctor_id AND appointment_date = :appointment_date 
                  AND appointment_time = :appointment_time 
                  AND status IN ('pending', 'confirmed')";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":doctor_id", $this->doctor_id);
        $stmt->bindParam(":appointment_date", $this->appointment_date);
        $stmt->bindParam(":appointment_time", $this->appointment_time);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] == 0;
    }

    /**
     * Generate confirmation code - توليد رمز التأكيد
     */
    private function generateConfirmationCode() {
        do {
            $code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
            $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE confirmation_code = :code";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":code", $code);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        } while ($result['count'] > 0);

        return $code;
    }

    /**
     * Send booking notifications - إرسال إشعارات الحجز
     */
    private function sendBookingNotifications() {
        require_once 'Notification.php';
        $notification = new Notification($this->conn);

        // Get appointment details
        $appointment_details = $this->getAppointmentDetails($this->id);
        
        // Notify patient
        $patient_message = "تم حجز موعدك مع د. {$appointment_details['doctor_name']} في {$appointment_details['appointment_date']} الساعة {$appointment_details['appointment_time']}. رمز التأكيد: {$this->confirmation_code}";
        $notification->sendNotification($this->patient_id, 'تأكيد حجز الموعد', $patient_message, 'appointment', $this->id);

        // Notify doctor
        $doctor_message = "تم حجز موعد جديد معك من قبل {$appointment_details['patient_name']} في {$appointment_details['appointment_date']} الساعة {$appointment_details['appointment_time']}";
        $notification->sendNotification($appointment_details['doctor_user_id'], 'موعد جديد', $doctor_message, 'appointment', $this->id);
    }

    /**
     * Get appointment details - الحصول على تفاصيل الموعد
     */
    public function getAppointmentDetails($appointment_id) {
        $query = "SELECT a.*, 
                         CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                         p.phone as patient_phone, p.email as patient_email,
                         CONCAT(d_user.first_name, ' ', d_user.last_name) as doctor_name,
                         d_user.id as doctor_user_id,
                         d.clinic_name, d.clinic_address, d.clinic_phone,
                         s.name_ar as specialty_name,
                         ic.name_ar as insurance_name
                  FROM " . $this->table_name . " a
                  INNER JOIN users p ON a.patient_id = p.id
                  INNER JOIN doctors d ON a.doctor_id = d.id
                  INNER JOIN users d_user ON d.user_id = d_user.id
                  INNER JOIN specialties s ON d.specialty_id = s.id
                  LEFT JOIN insurance_companies ic ON a.insurance_id = ic.id
                  WHERE a.id = :appointment_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":appointment_id", $appointment_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get user appointments - الحصول على مواعيد المستخدم
     */
    public function getUserAppointments($user_id, $role = 'patient', $status = null, $limit = 20, $offset = 0) {
        $where_field = ($role == 'patient') ? 'a.patient_id' : 'a.doctor_id';
        
        $query = "SELECT a.*, 
                         CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                         CONCAT(d_user.first_name, ' ', d_user.last_name) as doctor_name,
                         d.clinic_name, s.name_ar as specialty_name,
                         ic.name_ar as insurance_name
                  FROM " . $this->table_name . " a
                  INNER JOIN users p ON a.patient_id = p.id
                  INNER JOIN doctors d ON a.doctor_id = d.id
                  INNER JOIN users d_user ON d.user_id = d_user.id
                  INNER JOIN specialties s ON d.specialty_id = s.id
                  LEFT JOIN insurance_companies ic ON a.insurance_id = ic.id
                  WHERE $where_field = :user_id";

        $params = [':user_id' => $user_id];

        if ($status) {
            $query .= " AND a.status = :status";
            $params[':status'] = $status;
        }

        $query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update appointment status - تحديث حالة الموعد
     */
    public function updateAppointmentStatus($appointment_id, $new_status, $notes = null) {
        $query = "UPDATE " . $this->table_name . " SET status = :status";
        
        if ($notes) {
            $query .= ", notes = :notes";
        }
        
        $query .= " WHERE id = :appointment_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $new_status);
        $stmt->bindParam(":appointment_id", $appointment_id);
        
        if ($notes) {
            $stmt->bindParam(":notes", $notes);
        }

        if ($stmt->execute()) {
            // Send status update notification
            $this->sendStatusUpdateNotification($appointment_id, $new_status);
            return true;
        }

        return false;
    }

    /**
     * Send status update notification - إرسال إشعار تحديث الحالة
     */
    private function sendStatusUpdateNotification($appointment_id, $new_status) {
        require_once 'Notification.php';
        $notification = new Notification($this->conn);

        $appointment_details = $this->getAppointmentDetails($appointment_id);
        
        $status_messages = [
            'confirmed' => 'تم تأكيد موعدك',
            'cancelled' => 'تم إلغاء موعدك',
            'completed' => 'تم إنجاز موعدك',
            'no_show' => 'لم تحضر لموعدك المحدد'
        ];

        if (isset($status_messages[$new_status])) {
            $message = $status_messages[$new_status] . " مع د. {$appointment_details['doctor_name']} في {$appointment_details['appointment_date']}";
            $notification->sendNotification($appointment_details['patient_id'], 'تحديث حالة الموعد', $message, 'appointment', $appointment_id);
        }
    }

    /**
     * Cancel appointment - إلغاء الموعد
     */
    public function cancelAppointment($appointment_id, $user_id, $reason = null) {
        // Check if user can cancel this appointment
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :appointment_id AND 
                  (patient_id = :user_id OR doctor_id IN (SELECT id FROM doctors WHERE user_id = :user_id))";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":appointment_id", $appointment_id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            return ['success' => false, 'message' => 'غير مسموح لك بإلغاء هذا الموعد'];
        }

        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if appointment can be cancelled (not in the past and not already completed)
        $appointment_datetime = $appointment['appointment_date'] . ' ' . $appointment['appointment_time'];
        if (strtotime($appointment_datetime) <= time() || $appointment['status'] == 'completed') {
            return ['success' => false, 'message' => 'لا يمكن إلغاء هذا الموعد'];
        }

        // Update appointment status
        $notes = $reason ? "سبب الإلغاء: " . $reason : null;
        if ($this->updateAppointmentStatus($appointment_id, 'cancelled', $notes)) {
            return ['success' => true, 'message' => 'تم إلغاء الموعد بنجاح'];
        }

        return ['success' => false, 'message' => 'حدث خطأ أثناء إلغاء الموعد'];
    }

    /**
     * Get appointment statistics - الحصول على إحصائيات المواعيد
     */
    public function getAppointmentStats($user_id = null, $doctor_id = null) {
        $stats = [];
        $where_clause = "";
        $params = [];

        if ($user_id) {
            $where_clause = "WHERE patient_id = :user_id";
            $params[':user_id'] = $user_id;
        } elseif ($doctor_id) {
            $where_clause = "WHERE doctor_id = :doctor_id";
            $params[':doctor_id'] = $doctor_id;
        }

        // Total appointments
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " " . $where_clause;
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Appointments by status
        $statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
        foreach ($statuses as $status) {
            $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " " . $where_clause . 
                     ($where_clause ? " AND" : "WHERE") . " status = :status";
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':status', $status);
            $stmt->execute();
            $stats[$status] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        }

        return $stats;
    }
}
?>