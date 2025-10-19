<?php
/**
 * نظام إدارة المواعيد
 * Appointments Management System
 */

require_once __DIR__ . '/../config/config.php';

class Appointments {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * حجز موعد جديد
     * Book new appointment
     */
    public function bookAppointment($patientId, $doctorId, $date, $time, $reason = null, $consultationType = 'in_person') {
        try {
            // التحقق من توفر الموعد
            if (!$this->isSlotAvailable($doctorId, $date, $time)) {
                return ['success' => false, 'message' => 'الموعد غير متاح'];
            }
            
            // التحقق من أن التاريخ في المستقبل
            $appointmentDateTime = $date . ' ' . $time;
            if (strtotime($appointmentDateTime) <= time()) {
                return ['success' => false, 'message' => 'لا يمكن حجز موعد في الماضي'];
            }
            
            // بدء المعاملة
            $this->db->beginTransaction();
            
            // إدراج الموعد
            $stmt = $this->db->prepare("
                INSERT INTO appointments 
                (patient_id, doctor_id, appointment_date, appointment_time, status, consultation_type, reason)
                VALUES (?, ?, ?, ?, 'pending', ?, ?)
            ");
            $stmt->execute([$patientId, $doctorId, $date, $time, $consultationType, $reason]);
            $appointmentId = $this->db->lastInsertId();
            
            // إرسال إشعار للطبيب
            $this->sendNotification(
                $this->getDoctorUserId($doctorId),
                'موعد جديد',
                'لديك موعد جديد بانتظار التأكيد',
                'appointment',
                $appointmentId
            );
            
            // إرسال إشعار للمريض
            $this->sendNotification(
                $this->getPatientUserId($patientId),
                'تم حجز الموعد',
                'تم حجز موعدك بنجاح وبانتظار تأكيد الطبيب',
                'appointment',
                $appointmentId
            );
            
            // تأكيد المعاملة
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'تم حجز الموعد بنجاح',
                'appointment_id' => $appointmentId
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Booking Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ في حجز الموعد'];
        }
    }
    
    /**
     * تأكيد الموعد (من قبل الطبيب)
     * Confirm appointment (by doctor)
     */
    public function confirmAppointment($appointmentId, $doctorUserId) {
        try {
            // التحقق من صلاحيات الطبيب
            $stmt = $this->db->prepare("
                SELECT a.id, a.patient_id, d.user_id as doctor_user_id
                FROM appointments a
                JOIN doctors d ON a.doctor_id = d.id
                WHERE a.id = ? AND d.user_id = ? AND a.status = 'pending'
            ");
            $stmt->execute([$appointmentId, $doctorUserId]);
            $appointment = $stmt->fetch();
            
            if (!$appointment) {
                return ['success' => false, 'message' => 'الموعد غير موجود أو تم تأكيده مسبقاً'];
            }
            
            // تحديث حالة الموعد
            $stmt = $this->db->prepare("
                UPDATE appointments 
                SET status = 'confirmed', confirmed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$appointmentId]);
            
            // إرسال إشعار للمريض
            $this->sendNotification(
                $this->getPatientUserId($appointment['patient_id']),
                'تم تأكيد موعدك',
                'تم تأكيد موعدك من قبل الطبيب',
                'appointment',
                $appointmentId
            );
            
            return ['success' => true, 'message' => 'تم تأكيد الموعد بنجاح'];
            
        } catch (Exception $e) {
            error_log("Confirm Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ في تأكيد الموعد'];
        }
    }
    
    /**
     * إلغاء الموعد
     * Cancel appointment
     */
    public function cancelAppointment($appointmentId, $userId, $reason = null) {
        try {
            // الحصول على معلومات الموعد
            $stmt = $this->db->prepare("
                SELECT a.*, p.user_id as patient_user_id, d.user_id as doctor_user_id
                FROM appointments a
                JOIN patients p ON a.patient_id = p.id
                JOIN doctors d ON a.doctor_id = d.id
                WHERE a.id = ?
            ");
            $stmt->execute([$appointmentId]);
            $appointment = $stmt->fetch();
            
            if (!$appointment) {
                return ['success' => false, 'message' => 'الموعد غير موجود'];
            }
            
            // التحقق من الصلاحيات
            if ($appointment['patient_user_id'] != $userId && 
                $appointment['doctor_user_id'] != $userId && 
                !hasRole(ROLE_ADMIN)) {
                return ['success' => false, 'message' => 'ليس لديك صلاحية إلغاء هذا الموعد'];
            }
            
            // التحقق من حالة الموعد
            if ($appointment['status'] === 'cancelled' || $appointment['status'] === 'completed') {
                return ['success' => false, 'message' => 'لا يمكن إلغاء هذا الموعد'];
            }
            
            // تحديث حالة الموعد
            $stmt = $this->db->prepare("
                UPDATE appointments 
                SET status = 'cancelled', cancellation_reason = ?, 
                    cancelled_by = ?, cancelled_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$reason, $userId, $appointmentId]);
            
            // إرسال إشعارات
            if ($appointment['patient_user_id'] != $userId) {
                $this->sendNotification(
                    $appointment['patient_user_id'],
                    'تم إلغاء موعدك',
                    'تم إلغاء موعدك. ' . ($reason ?? ''),
                    'cancellation',
                    $appointmentId
                );
            }
            
            if ($appointment['doctor_user_id'] != $userId) {
                $this->sendNotification(
                    $appointment['doctor_user_id'],
                    'تم إلغاء موعد',
                    'تم إلغاء أحد مواعيدك. ' . ($reason ?? ''),
                    'cancellation',
                    $appointmentId
                );
            }
            
            return ['success' => true, 'message' => 'تم إلغاء الموعد بنجاح'];
            
        } catch (Exception $e) {
            error_log("Cancel Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ في إلغاء الموعد'];
        }
    }
    
    /**
     * الحصول على مواعيد المريض
     * Get patient appointments
     */
    public function getPatientAppointments($patientId, $status = null, $upcoming = false) {
        try {
            $sql = "
                SELECT a.*, 
                       u.full_name as doctor_name, u.avatar as doctor_avatar,
                       d.consultation_fee, d.clinic_name, d.clinic_address,
                       s.name_ar as specialty_name
                FROM appointments a
                JOIN doctors d ON a.doctor_id = d.id
                JOIN users u ON d.user_id = u.id
                JOIN specialties s ON d.specialty_id = s.id
                WHERE a.patient_id = ?
            ";
            
            $params = [$patientId];
            
            if ($status) {
                $sql .= " AND a.status = ?";
                $params[] = $status;
            }
            
            if ($upcoming) {
                $sql .= " AND CONCAT(a.appointment_date, ' ', a.appointment_time) >= NOW()";
            }
            
            $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return ['success' => true, 'appointments' => $stmt->fetchAll()];
            
        } catch (Exception $e) {
            error_log("Get Appointments Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ في جلب المواعيد'];
        }
    }
    
    /**
     * الحصول على مواعيد الطبيب
     * Get doctor appointments
     */
    public function getDoctorAppointments($doctorId, $date = null, $status = null) {
        try {
            $sql = "
                SELECT a.*, 
                       u.full_name as patient_name, u.phone as patient_phone, u.avatar as patient_avatar,
                       p.national_id, p.date_of_birth, p.gender, p.blood_type
                FROM appointments a
                JOIN patients p ON a.patient_id = p.id
                JOIN users u ON p.user_id = u.id
                WHERE a.doctor_id = ?
            ";
            
            $params = [$doctorId];
            
            if ($date) {
                $sql .= " AND a.appointment_date = ?";
                $params[] = $date;
            }
            
            if ($status) {
                $sql .= " AND a.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return ['success' => true, 'appointments' => $stmt->fetchAll()];
            
        } catch (Exception $e) {
            error_log("Get Doctor Appointments Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ في جلب المواعيد'];
        }
    }
    
    /**
     * التحقق من توفر الموعد
     * Check slot availability
     */
    private function isSlotAvailable($doctorId, $date, $time) {
        try {
            // التحقق من عدم وجود موعد في نفس الوقت
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM appointments
                WHERE doctor_id = ? 
                AND appointment_date = ? 
                AND appointment_time = ?
                AND status IN ('pending', 'confirmed')
            ");
            $stmt->execute([$doctorId, $date, $time]);
            $result = $stmt->fetch();
            
            return $result['count'] == 0;
            
        } catch (Exception $e) {
            error_log("Slot Check Error: " . $e->getMessage());
            return false;
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
        } catch (Exception $e) {
            error_log("Notification Error: " . $e->getMessage());
        }
    }
    
    /**
     * الحصول على معرف مستخدم الطبيب
     */
    private function getDoctorUserId($doctorId) {
        $stmt = $this->db->prepare("SELECT user_id FROM doctors WHERE id = ?");
        $stmt->execute([$doctorId]);
        $result = $stmt->fetch();
        return $result ? $result['user_id'] : null;
    }
    
    /**
     * الحصول على معرف مستخدم المريض
     */
    private function getPatientUserId($patientId) {
        $stmt = $this->db->prepare("SELECT user_id FROM patients WHERE id = ?");
        $stmt->execute([$patientId]);
        $result = $stmt->fetch();
        return $result ? $result['user_id'] : null;
    }
    
    /**
     * الحصول على الأوقات المتاحة لطبيب في يوم معين
     * Get available time slots for a doctor on a specific date
     */
    public function getAvailableSlots($doctorId, $date) {
        try {
            // الحصول على يوم الأسبوع
            $dayOfWeek = date('w', strtotime($date));
            
            // الحصول على جدول عمل الطبيب
            $stmt = $this->db->prepare("
                SELECT start_time, end_time, slot_duration
                FROM doctor_schedules
                WHERE doctor_id = ? AND day_of_week = ? AND is_active = 1
            ");
            $stmt->execute([$doctorId, $dayOfWeek]);
            $schedules = $stmt->fetchAll();
            
            if (empty($schedules)) {
                return ['success' => true, 'slots' => []];
            }
            
            // الحصول على المواعيد المحجوزة
            $stmt = $this->db->prepare("
                SELECT appointment_time
                FROM appointments
                WHERE doctor_id = ? AND appointment_date = ? 
                AND status IN ('pending', 'confirmed')
            ");
            $stmt->execute([$doctorId, $date]);
            $bookedSlots = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // توليد الأوقات المتاحة
            $availableSlots = [];
            foreach ($schedules as $schedule) {
                $currentTime = strtotime($schedule['start_time']);
                $endTime = strtotime($schedule['end_time']);
                $duration = $schedule['slot_duration'] * 60; // تحويل لثوان
                
                while ($currentTime < $endTime) {
                    $timeSlot = date('H:i:s', $currentTime);
                    
                    // التحقق من أن الوقت غير محجوز وفي المستقبل
                    if (!in_array($timeSlot, $bookedSlots) && 
                        strtotime($date . ' ' . $timeSlot) > time()) {
                        $availableSlots[] = date('H:i', $currentTime);
                    }
                    
                    $currentTime += $duration;
                }
            }
            
            return ['success' => true, 'slots' => $availableSlots];
            
        } catch (Exception $e) {
            error_log("Get Slots Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ في جلب الأوقات المتاحة'];
        }
    }
}
?>
