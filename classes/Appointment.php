<?php
require_once 'config/database.php';
require_once 'config/config.php';

/**
 * Appointment Class
 * فئة المواعيد
 */
class Appointment {
    private $conn;
    private $table_name = "appointments";

    public $id;
    public $patient_id;
    public $doctor_id;
    public $clinic_id;
    public $appointment_date;
    public $appointment_time;
    public $status;
    public $consultation_type;
    public $notes;
    public $patient_notes;
    public $doctor_notes;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create new appointment
     * إنشاء موعد جديد
     */
    public function create() {
        // التحقق من توفر الموعد
        if(!$this->isTimeSlotAvailable()) {
            return ['success' => false, 'message' => 'هذا الموعد غير متاح'];
        }

        $query = "INSERT INTO " . $this->table_name . " 
                  (patient_id, doctor_id, clinic_id, appointment_date, appointment_time, 
                   status, consultation_type, notes, patient_notes) 
                  VALUES (:patient_id, :doctor_id, :clinic_id, :appointment_date, :appointment_time, 
                          :status, :consultation_type, :notes, :patient_notes)";

        $stmt = $this->conn->prepare($query);

        // تنظيف البيانات
        $this->notes = sanitize_input($this->notes);
        $this->patient_notes = sanitize_input($this->patient_notes);

        // ربط المعاملات
        $stmt->bindParam(':patient_id', $this->patient_id);
        $stmt->bindParam(':doctor_id', $this->doctor_id);
        $stmt->bindParam(':clinic_id', $this->clinic_id);
        $stmt->bindParam(':appointment_date', $this->appointment_date);
        $stmt->bindParam(':appointment_time', $this->appointment_time);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':consultation_type', $this->consultation_type);
        $stmt->bindParam(':notes', $this->notes);
        $stmt->bindParam(':patient_notes', $this->patient_notes);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            
            // إرسال إشعار للمريض
            $this->sendNotification($this->patient_id, 'appointment_confirmed', 
                'تم حجز موعدك بنجاح', 
                'تم تأكيد موعدك بتاريخ ' . $this->appointment_date . ' في الساعة ' . $this->appointment_time);
            
            return ['success' => true, 'message' => 'تم حجز الموعد بنجاح', 'appointment_id' => $this->id];
        }
        return ['success' => false, 'message' => 'حدث خطأ في حجز الموعد'];
    }

    /**
     * Check if time slot is available
     * التحقق من توفر الموعد
     */
    private function isTimeSlotAvailable() {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE doctor_id = :doctor_id 
                  AND appointment_date = :appointment_date 
                  AND appointment_time = :appointment_time 
                  AND status IN ('pending', 'confirmed')";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':doctor_id', $this->doctor_id);
        $stmt->bindParam(':appointment_date', $this->appointment_date);
        $stmt->bindParam(':appointment_time', $this->appointment_time);
        $stmt->execute();

        return $stmt->rowCount() == 0;
    }

    /**
     * Get appointment by ID
     * الحصول على الموعد بالمعرف
     */
    public function getById($id) {
        $query = "SELECT a.*, 
                         u_p.full_name as patient_name, u_p.email as patient_email, u_p.phone as patient_phone,
                         u_d.full_name as doctor_name, u_d.email as doctor_email,
                         s.name_ar as specialization_name,
                         c.name as clinic_name, c.address as clinic_address, c.city as clinic_city
                  FROM " . $this->table_name . " a
                  JOIN users u_p ON a.patient_id = u_p.id
                  JOIN doctors d ON a.doctor_id = d.id
                  JOIN users u_d ON d.user_id = u_d.id
                  JOIN specializations s ON d.specialization_id = s.id
                  JOIN clinics c ON a.clinic_id = c.id
                  WHERE a.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * Get appointments by patient ID
     * الحصول على مواعيد المريض
     */
    public function getByPatientId($patient_id, $status = null, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        $where_clause = "a.patient_id = :patient_id";
        $params = [':patient_id' => $patient_id];

        if($status) {
            $where_clause .= " AND a.status = :status";
            $params[':status'] = $status;
        }

        $query = "SELECT a.*, 
                         u_d.full_name as doctor_name,
                         s.name_ar as specialization_name,
                         c.name as clinic_name, c.city as clinic_city
                  FROM " . $this->table_name . " a
                  JOIN doctors d ON a.doctor_id = d.id
                  JOIN users u_d ON d.user_id = u_d.id
                  JOIN specializations s ON d.specialization_id = s.id
                  JOIN clinics c ON a.clinic_id = c.id
                  WHERE " . $where_clause . "
                  ORDER BY a.appointment_date DESC, a.appointment_time DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get appointments by doctor ID
     * الحصول على مواعيد الطبيب
     */
    public function getByDoctorId($doctor_id, $date = null, $status = null, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        $where_conditions = ["a.doctor_id = :doctor_id"];
        $params = [':doctor_id' => $doctor_id];

        if($date) {
            $where_conditions[] = "a.appointment_date = :date";
            $params[':date'] = $date;
        }

        if($status) {
            $where_conditions[] = "a.status = :status";
            $params[':status'] = $status;
        }

        $where_clause = implode(" AND ", $where_conditions);

        $query = "SELECT a.*, 
                         u_p.full_name as patient_name, u_p.phone as patient_phone,
                         c.name as clinic_name, c.city as clinic_city
                  FROM " . $this->table_name . " a
                  JOIN users u_p ON a.patient_id = u_p.id
                  JOIN clinics c ON a.clinic_id = c.id
                  WHERE " . $where_clause . "
                  ORDER BY a.appointment_date ASC, a.appointment_time ASC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update appointment status
     * تحديث حالة الموعد
     */
    public function updateStatus($id, $status, $notes = null) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = :status, updated_at = CURRENT_TIMESTAMP";
        
        if($notes) {
            $query .= ", doctor_notes = :notes";
        }
        
        $query .= " WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        
        if($notes) {
            $notes = sanitize_input($notes);
            $stmt->bindParam(':notes', $notes);
        }

        if($stmt->execute()) {
            // إرسال إشعار للمريض
            $appointment = $this->getById($id);
            if($appointment) {
                $this->sendStatusNotification($appointment['patient_id'], $status, $appointment);
            }
            return true;
        }
        return false;
    }

    /**
     * Cancel appointment
     * إلغاء الموعد
     */
    public function cancel($id, $reason = null) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = 'cancelled', 
                      patient_notes = :reason,
                      updated_at = CURRENT_TIMESTAMP 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $reason = sanitize_input($reason);
        $stmt->bindParam(':reason', $reason);
        $stmt->bindParam(':id', $id);

        if($stmt->execute()) {
            // إرسال إشعار للمريض
            $appointment = $this->getById($id);
            if($appointment) {
                $this->sendNotification($appointment['patient_id'], 'appointment_cancelled', 
                    'تم إلغاء موعدك', 
                    'تم إلغاء موعدك بتاريخ ' . $appointment['appointment_date'] . ' في الساعة ' . $appointment['appointment_time']);
            }
            return true;
        }
        return false;
    }

    /**
     * Send notification
     * إرسال إشعار
     */
    private function sendNotification($user_id, $type, $title, $message) {
        $query = "INSERT INTO notifications (user_id, title, message, type) 
                  VALUES (:user_id, :title, :message, :type)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':type', $type);

        return $stmt->execute();
    }

    /**
     * Send status notification
     * إرسال إشعار حالة الموعد
     */
    private function sendStatusNotification($user_id, $status, $appointment) {
        $status_messages = [
            'confirmed' => ['title' => 'تم تأكيد موعدك', 'message' => 'تم تأكيد موعدك بتاريخ ' . $appointment['appointment_date']],
            'cancelled' => ['title' => 'تم إلغاء موعدك', 'message' => 'تم إلغاء موعدك بتاريخ ' . $appointment['appointment_date']],
            'completed' => ['title' => 'تم إكمال موعدك', 'message' => 'تم إكمال موعدك بتاريخ ' . $appointment['appointment_date']]
        ];

        if(isset($status_messages[$status])) {
            $this->sendNotification($user_id, 'appointment_' . $status, 
                $status_messages[$status]['title'], 
                $status_messages[$status]['message']);
        }
    }

    /**
     * Get appointment statistics
     * الحصول على إحصائيات المواعيد
     */
    public function getStatistics($user_id, $user_role) {
        $where_clause = "";
        $params = [':user_id' => $user_id];

        if($user_role == 'patient') {
            $where_clause = "a.patient_id = :user_id";
        } elseif($user_role == 'doctor') {
            $where_clause = "d.user_id = :user_id";
        }

        $query = "SELECT 
                    COUNT(a.id) as total_appointments,
                    COUNT(CASE WHEN a.status = 'pending' THEN 1 END) as pending_appointments,
                    COUNT(CASE WHEN a.status = 'confirmed' THEN 1 END) as confirmed_appointments,
                    COUNT(CASE WHEN a.status = 'completed' THEN 1 END) as completed_appointments,
                    COUNT(CASE WHEN a.status = 'cancelled' THEN 1 END) as cancelled_appointments,
                    COUNT(CASE WHEN a.appointment_date >= CURDATE() THEN 1 END) as upcoming_appointments
                  FROM appointments a
                  " . ($user_role == 'doctor' ? "JOIN doctors d ON a.doctor_id = d.id" : "") . "
                  WHERE " . $where_clause;

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>