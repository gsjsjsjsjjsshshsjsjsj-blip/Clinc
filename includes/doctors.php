<?php
/**
 * نظام إدارة الأطباء
 * Doctors Management System
 */

require_once __DIR__ . '/../config/config.php';

class Doctors {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * البحث عن أطباء
     * Search for doctors
     */
    public function searchDoctors($filters = []) {
        try {
            $sql = "
                SELECT d.*, u.full_name, u.email, u.phone, u.avatar,
                       s.name_ar as specialty_name, s.icon as specialty_icon,
                       (SELECT COUNT(*) FROM appointments WHERE doctor_id = d.id AND status = 'completed') as completed_appointments
                FROM doctors d
                JOIN users u ON d.user_id = u.id
                JOIN specialties s ON d.specialty_id = s.id
                WHERE u.is_active = 1 AND d.is_verified = 1
            ";
            
            $params = [];
            
            // فلتر التخصص
            if (isset($filters['specialty_id']) && $filters['specialty_id']) {
                $sql .= " AND d.specialty_id = ?";
                $params[] = $filters['specialty_id'];
            }
            
            // فلتر المدينة (دعم البحث بالمعرف أو الاسم)
            if (isset($filters['city']) && $filters['city']) {
                if (is_numeric($filters['city'])) {
                    // البحث بمعرف المدينة
                    $sql .= " AND d.city_id = ?";
                    $params[] = $filters['city'];
                } else {
                    // البحث باسم المدينة
                    $sql .= " AND d.clinic_city LIKE ?";
                    $params[] = '%' . $filters['city'] . '%';
                }
            }
            
            // فلتر المنطقة
            if (isset($filters['region']) && $filters['region']) {
                $sql .= " AND EXISTS (SELECT 1 FROM cities c WHERE c.id = d.city_id AND c.region_ar LIKE ?)";
                $params[] = '%' . $filters['region'] . '%';
            }
            
            // فلتر الاسم
            if (isset($filters['name']) && $filters['name']) {
                $sql .= " AND u.full_name LIKE ?";
                $params[] = '%' . $filters['name'] . '%';
            }
            
            // فلتر التقييم الأدنى
            if (isset($filters['min_rating']) && $filters['min_rating']) {
                $sql .= " AND d.rating >= ?";
                $params[] = $filters['min_rating'];
            }
            
            // فلتر رسوم الاستشارة
            if (isset($filters['max_fee']) && $filters['max_fee']) {
                $sql .= " AND d.consultation_fee <= ?";
                $params[] = $filters['max_fee'];
            }
            
            // الترتيب
            $orderBy = isset($filters['sort']) ? $filters['sort'] : 'rating';
            switch ($orderBy) {
                case 'rating':
                    $sql .= " ORDER BY d.rating DESC";
                    break;
                case 'fee_low':
                    $sql .= " ORDER BY d.consultation_fee ASC";
                    break;
                case 'fee_high':
                    $sql .= " ORDER BY d.consultation_fee DESC";
                    break;
                case 'experience':
                    $sql .= " ORDER BY d.years_of_experience DESC";
                    break;
                default:
                    $sql .= " ORDER BY d.rating DESC";
            }
            
            // الحد الأقصى للنتائج
            $limit = isset($filters['limit']) ? (int)$filters['limit'] : 20;
            $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $doctors = $stmt->fetchAll();
            
            // إضافة معلومات الأوقات المتاحة
            foreach ($doctors as &$doctor) {
                $doctor['next_available'] = $this->getNextAvailableDate($doctor['id']);
            }
            
            return ['success' => true, 'doctors' => $doctors];
            
        } catch (Exception $e) {
            error_log("Search Doctors Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ في البحث'];
        }
    }
    
    /**
     * الحصول على معلومات الطبيب
     * Get doctor details
     */
    public function getDoctorDetails($doctorId) {
        try {
            $stmt = $this->db->prepare("
                SELECT d.*, u.full_name, u.email, u.phone, u.avatar,
                       s.name_ar as specialty_name, s.name_en as specialty_name_en,
                       (SELECT COUNT(*) FROM appointments WHERE doctor_id = d.id AND status = 'completed') as completed_appointments
                FROM doctors d
                JOIN users u ON d.user_id = u.id
                JOIN specialties s ON d.specialty_id = s.id
                WHERE d.id = ?
            ");
            $stmt->execute([$doctorId]);
            $doctor = $stmt->fetch();
            
            if (!$doctor) {
                return ['success' => false, 'message' => 'الطبيب غير موجود'];
            }
            
            // الحصول على جدول العمل
            $stmt = $this->db->prepare("
                SELECT * FROM doctor_schedules 
                WHERE doctor_id = ? AND is_active = 1
                ORDER BY day_of_week, start_time
            ");
            $stmt->execute([$doctorId]);
            $doctor['schedules'] = $stmt->fetchAll();
            
            // الحصول على التقييمات
            $doctor['reviews'] = $this->getDoctorReviews($doctorId);
            
            return ['success' => true, 'doctor' => $doctor];
            
        } catch (Exception $e) {
            error_log("Get Doctor Details Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ في جلب معلومات الطبيب'];
        }
    }
    
    /**
     * الحصول على تقييمات الطبيب
     * Get doctor reviews
     */
    public function getDoctorReviews($doctorId, $limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*, u.full_name as patient_name, u.avatar as patient_avatar,
                       a.appointment_date
                FROM reviews r
                JOIN patients p ON r.patient_id = p.id
                JOIN users u ON p.user_id = u.id
                JOIN appointments a ON r.appointment_id = a.id
                WHERE r.doctor_id = ? AND r.is_approved = 1
                ORDER BY r.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$doctorId, $limit]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get Reviews Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * إضافة تقييم
     * Add review
     */
    public function addReview($appointmentId, $patientId, $rating, $comment = null) {
        try {
            // التحقق من أن الموعد مكتمل وتابع للمريض
            $stmt = $this->db->prepare("
                SELECT doctor_id, status 
                FROM appointments 
                WHERE id = ? AND patient_id = ?
            ");
            $stmt->execute([$appointmentId, $patientId]);
            $appointment = $stmt->fetch();
            
            if (!$appointment) {
                return ['success' => false, 'message' => 'الموعد غير موجود'];
            }
            
            if ($appointment['status'] !== 'completed') {
                return ['success' => false, 'message' => 'لا يمكن تقييم موعد لم يكتمل'];
            }
            
            // التحقق من عدم وجود تقييم سابق
            $stmt = $this->db->prepare("SELECT id FROM reviews WHERE appointment_id = ?");
            $stmt->execute([$appointmentId]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'تم تقييم هذا الموعد مسبقاً'];
            }
            
            // بدء المعاملة
            $this->db->beginTransaction();
            
            // إضافة التقييم
            $stmt = $this->db->prepare("
                INSERT INTO reviews (appointment_id, doctor_id, patient_id, rating, comment, is_approved)
                VALUES (?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([$appointmentId, $appointment['doctor_id'], $patientId, $rating, $comment]);
            
            // تحديث تقييم الطبيب
            $this->updateDoctorRating($appointment['doctor_id']);
            
            // تأكيد المعاملة
            $this->db->commit();
            
            return ['success' => true, 'message' => 'تم إضافة التقييم بنجاح'];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Add Review Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ في إضافة التقييم'];
        }
    }
    
    /**
     * تحديث تقييم الطبيب
     * Update doctor rating
     */
    private function updateDoctorRating($doctorId) {
        try {
            $stmt = $this->db->prepare("
                SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews
                FROM reviews
                WHERE doctor_id = ? AND is_approved = 1
            ");
            $stmt->execute([$doctorId]);
            $result = $stmt->fetch();
            
            $stmt = $this->db->prepare("
                UPDATE doctors 
                SET rating = ?, total_reviews = ?
                WHERE id = ?
            ");
            $stmt->execute([
                round($result['avg_rating'], 2),
                $result['total_reviews'],
                $doctorId
            ]);
            
        } catch (Exception $e) {
            error_log("Update Rating Error: " . $e->getMessage());
        }
    }
    
    /**
     * الحصول على التاريخ المتاح القادم
     * Get next available date
     */
    private function getNextAvailableDate($doctorId) {
        try {
            // الحصول على أيام العمل
            $stmt = $this->db->prepare("
                SELECT DISTINCT day_of_week 
                FROM doctor_schedules 
                WHERE doctor_id = ? AND is_active = 1
            ");
            $stmt->execute([$doctorId]);
            $workDays = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($workDays)) {
                return null;
            }
            
            // البحث عن أول يوم متاح في الأيام القادمة
            for ($i = 0; $i < 30; $i++) {
                $date = date('Y-m-d', strtotime("+$i days"));
                $dayOfWeek = date('w', strtotime($date));
                
                if (in_array($dayOfWeek, $workDays)) {
                    return $date;
                }
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("Next Available Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * الحصول على جميع التخصصات
     * Get all specialties
     */
    public function getAllSpecialties() {
        try {
            $stmt = $this->db->query("
                SELECT s.*, 
                       (SELECT COUNT(*) FROM doctors WHERE specialty_id = s.id AND is_verified = 1) as doctors_count
                FROM specialties s
                WHERE s.is_active = 1
                ORDER BY s.name_ar
            ");
            return ['success' => true, 'specialties' => $stmt->fetchAll()];
            
        } catch (Exception $e) {
            error_log("Get Specialties Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ في جلب التخصصات'];
        }
    }
}
?>
