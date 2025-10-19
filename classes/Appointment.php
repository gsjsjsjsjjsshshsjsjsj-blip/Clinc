<?php
/**
 * Appointment Class
 * Handles appointment booking and management
 */

require_once __DIR__ . '/../config/config.php';

class Appointment {
    private $conn;
    private $table_name = "appointments";

    public $id;
    public $patient_id;
    public $doctor_id;
    public $clinic_id;
    public $appointment_date;
    public $appointment_time;
    public $duration_minutes;
    public $status;
    public $consultation_type;
    public $notes;
    public $symptoms;
    public $diagnosis;
    public $prescription;
    public $total_fee;
    public $payment_status;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Book new appointment
    public function book() {
        // Check if time slot is available
        if(!$this->isTimeSlotAvailable()) {
            return false;
        }

        $query = "INSERT INTO " . $this->table_name . " 
                  (patient_id, doctor_id, clinic_id, appointment_date, appointment_time, 
                   duration_minutes, consultation_type, notes, symptoms, total_fee) 
                  VALUES (:patient_id, :doctor_id, :clinic_id, :appointment_date, :appointment_time, 
                          :duration_minutes, :consultation_type, :notes, :symptoms, :total_fee)";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->patient_id = htmlspecialchars(strip_tags($this->patient_id));
        $this->doctor_id = htmlspecialchars(strip_tags($this->doctor_id));
        $this->clinic_id = htmlspecialchars(strip_tags($this->clinic_id));
        $this->appointment_date = htmlspecialchars(strip_tags($this->appointment_date));
        $this->appointment_time = htmlspecialchars(strip_tags($this->appointment_time));
        $this->consultation_type = htmlspecialchars(strip_tags($this->consultation_type));
        $this->notes = htmlspecialchars(strip_tags($this->notes));
        $this->symptoms = htmlspecialchars(strip_tags($this->symptoms));

        $stmt->bindParam(":patient_id", $this->patient_id);
        $stmt->bindParam(":doctor_id", $this->doctor_id);
        $stmt->bindParam(":clinic_id", $this->clinic_id);
        $stmt->bindParam(":appointment_date", $this->appointment_date);
        $stmt->bindParam(":appointment_time", $this->appointment_time);
        $stmt->bindParam(":duration_minutes", $this->duration_minutes);
        $stmt->bindParam(":consultation_type", $this->consultation_type);
        $stmt->bindParam(":notes", $this->notes);
        $stmt->bindParam(":symptoms", $this->symptoms);
        $stmt->bindParam(":total_fee", $this->total_fee);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            
            // Send notification to patient
            $this->sendNotification($this->patient_id, 'تم حجز موعدك بنجاح', 
                'تم تأكيد حجز موعدك مع الطبيب في ' . $this->appointment_date . ' في ' . $this->appointment_time);
            
            return true;
        }
        return false;
    }

    // Check if time slot is available
    private function isTimeSlotAvailable() {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE doctor_id = :doctor_id 
                  AND appointment_date = :appointment_date 
                  AND appointment_time = :appointment_time 
                  AND status IN ('pending', 'confirmed')";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":doctor_id", $this->doctor_id);
        $stmt->bindParam(":appointment_date", $this->appointment_date);
        $stmt->bindParam(":appointment_time", $this->appointment_time);
        $stmt->execute();

        return $stmt->rowCount() == 0;
    }

    // Get appointments by patient
    public function getPatientAppointments($patient_id, $status = null) {
        $query = "SELECT a.*, d.consultation_fee, u.full_name as doctor_name, 
                         s.name_ar as specialization_name, c.name as clinic_name
                  FROM " . $this->table_name . " a
                  LEFT JOIN doctors d ON a.doctor_id = d.id
                  LEFT JOIN users u ON d.user_id = u.id
                  LEFT JOIN specializations s ON d.specialization_id = s.id
                  LEFT JOIN clinics c ON a.clinic_id = c.id
                  WHERE a.patient_id = :patient_id";

        if($status) {
            $query .= " AND a.status = :status";
        }

        $query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":patient_id", $patient_id);
        
        if($status) {
            $stmt->bindParam(":status", $status);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get appointments by doctor
    public function getDoctorAppointments($doctor_id, $date = null) {
        $query = "SELECT a.*, u.full_name as patient_name, u.phone as patient_phone
                  FROM " . $this->table_name . " a
                  LEFT JOIN users u ON a.patient_id = u.id
                  WHERE a.doctor_id = :doctor_id";

        if($date) {
            $query .= " AND a.appointment_date = :date";
        }

        $query .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":doctor_id", $doctor_id);
        
        if($date) {
            $stmt->bindParam(":date", $date);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Update appointment status
    public function updateStatus($appointment_id, $status) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = :status, updated_at = CURRENT_TIMESTAMP 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $appointment_id);

        if($stmt->execute()) {
            // Send notification
            $this->sendStatusNotification($appointment_id, $status);
            return true;
        }
        return false;
    }

    // Cancel appointment
    public function cancel($appointment_id, $reason = null) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = 'cancelled', notes = CONCAT(IFNULL(notes, ''), :reason), 
                      updated_at = CURRENT_TIMESTAMP 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $reason_text = $reason ? "\nسبب الإلغاء: " . $reason : "";
        $stmt->bindParam(":reason", $reason_text);
        $stmt->bindParam(":id", $appointment_id);

        return $stmt->execute();
    }

    // Get available time slots for a doctor on a specific date
    public function getAvailableTimeSlots($doctor_id, $date) {
        // Get doctor's working hours (this would be from doctor_clinics table)
        $working_hours = $this->getDoctorWorkingHours($doctor_id, $date);
        
        // Get booked appointments for the date
        $query = "SELECT appointment_time FROM " . $this->table_name . " 
                  WHERE doctor_id = :doctor_id 
                  AND appointment_date = :date 
                  AND status IN ('pending', 'confirmed')";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":doctor_id", $doctor_id);
        $stmt->bindParam(":date", $date);
        $stmt->execute();

        $booked_times = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Generate available time slots
        $available_slots = [];
        $start_time = strtotime($working_hours['start_time']);
        $end_time = strtotime($working_hours['end_time']);
        $interval = 30 * 60; // 30 minutes

        for($time = $start_time; $time < $end_time; $time += $interval) {
            $time_slot = date('H:i:s', $time);
            if(!in_array($time_slot, $booked_times)) {
                $available_slots[] = $time_slot;
            }
        }

        return $available_slots;
    }

    // Get doctor working hours
    private function getDoctorWorkingHours($doctor_id, $date) {
        // This is a simplified version - in reality, you'd check the doctor's schedule
        return [
            'start_time' => '09:00:00',
            'end_time' => '17:00:00'
        ];
    }

    // Send notification
    private function sendNotification($user_id, $title, $message) {
        $query = "INSERT INTO notifications (user_id, title, message, type) 
                  VALUES (:user_id, :title, :message, 'info')";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":message", $message);
        $stmt->execute();
    }

    // Send status notification
    private function sendStatusNotification($appointment_id, $status) {
        // Get appointment details
        $query = "SELECT a.*, u.full_name as patient_name, d.consultation_fee, 
                         doc_user.full_name as doctor_name
                  FROM " . $this->table_name . " a
                  LEFT JOIN users u ON a.patient_id = u.id
                  LEFT JOIN doctors d ON a.doctor_id = d.id
                  LEFT JOIN users doc_user ON d.user_id = doc_user.id
                  WHERE a.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $appointment_id);
        $stmt->execute();
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

        if($appointment) {
            $status_messages = [
                'confirmed' => 'تم تأكيد موعدك',
                'cancelled' => 'تم إلغاء موعدك',
                'completed' => 'تم إكمال موعدك'
            ];

            $title = $status_messages[$status] ?? 'تحديث حالة الموعد';
            $message = "موعدك مع د. " . $appointment['doctor_name'] . " في " . 
                      $appointment['appointment_date'] . " " . $appointment['appointment_time'];

            $this->sendNotification($appointment['patient_id'], $title, $message);
        }
    }
}
?>