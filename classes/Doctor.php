<?php
require_once 'config/database.php';
require_once 'config/config.php';

/**
 * Doctor Class
 * فئة الطبيب
 */
class Doctor {
    private $conn;
    private $table_name = "doctors";

    public $id;
    public $user_id;
    public $specialization_id;
    public $license_number;
    public $experience_years;
    public $consultation_fee;
    public $bio;
    public $education;
    public $languages;
    public $rating;
    public $total_reviews;
    public $is_available;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create new doctor
     * إنشاء طبيب جديد
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, specialization_id, license_number, experience_years, 
                   consultation_fee, bio, education, languages) 
                  VALUES (:user_id, :specialization_id, :license_number, :experience_years, 
                          :consultation_fee, :bio, :education, :languages)";

        $stmt = $this->conn->prepare($query);

        // تنظيف البيانات
        $this->license_number = sanitize_input($this->license_number);
        $this->bio = sanitize_input($this->bio);
        $this->education = sanitize_input($this->education);

        // ربط المعاملات
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':specialization_id', $this->specialization_id);
        $stmt->bindParam(':license_number', $this->license_number);
        $stmt->bindParam(':experience_years', $this->experience_years);
        $stmt->bindParam(':consultation_fee', $this->consultation_fee);
        $stmt->bindParam(':bio', $this->bio);
        $stmt->bindParam(':education', $this->education);
        $stmt->bindParam(':languages', $this->languages);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    /**
     * Get doctor by ID
     * الحصول على الطبيب بالمعرف
     */
    public function getById($id) {
        $query = "SELECT d.*, u.full_name, u.email, u.phone, u.gender, 
                         s.name_ar as specialization_name, s.name_en as specialization_name_en
                  FROM " . $this->table_name . " d
                  JOIN users u ON d.user_id = u.id
                  JOIN specializations s ON d.specialization_id = s.id
                  WHERE d.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * Get doctor by user ID
     * الحصول على الطبيب بمعرف المستخدم
     */
    public function getByUserId($user_id) {
        $query = "SELECT d.*, u.full_name, u.email, u.phone, u.gender, 
                         s.name_ar as specialization_name, s.name_en as specialization_name_en
                  FROM " . $this->table_name . " d
                  JOIN users u ON d.user_id = u.id
                  JOIN specializations s ON d.specialization_id = s.id
                  WHERE d.user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * Search doctors
     * البحث عن الأطباء
     */
    public function search($specialization_id = null, $city = null, $min_fee = null, $max_fee = null, $gender = null, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        $where_conditions = ["d.is_available = 1", "u.is_active = 1"];
        $params = [];

        if($specialization_id) {
            $where_conditions[] = "d.specialization_id = :specialization_id";
            $params[':specialization_id'] = $specialization_id;
        }

        if($city) {
            $where_conditions[] = "c.city = :city";
            $params[':city'] = $city;
        }

        if($min_fee) {
            $where_conditions[] = "d.consultation_fee >= :min_fee";
            $params[':min_fee'] = $min_fee;
        }

        if($max_fee) {
            $where_conditions[] = "d.consultation_fee <= :max_fee";
            $params[':max_fee'] = $max_fee;
        }

        if($gender) {
            $where_conditions[] = "u.gender = :gender";
            $params[':gender'] = $gender;
        }

        $where_clause = implode(" AND ", $where_conditions);

        $query = "SELECT d.*, u.full_name, u.email, u.phone, u.gender, 
                         s.name_ar as specialization_name, s.name_en as specialization_name_en,
                         c.name as clinic_name, c.city, c.address
                  FROM " . $this->table_name . " d
                  JOIN users u ON d.user_id = u.id
                  JOIN specializations s ON d.specialization_id = s.id
                  LEFT JOIN doctor_schedules ds ON d.id = ds.doctor_id
                  LEFT JOIN clinics c ON ds.clinic_id = c.id
                  WHERE " . $where_clause . "
                  GROUP BY d.id
                  ORDER BY d.rating DESC, d.total_reviews DESC
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
     * Get doctor schedule
     * الحصول على جدول الطبيب
     */
    public function getSchedule($doctor_id, $date = null) {
        $where_clause = "ds.doctor_id = :doctor_id AND ds.is_active = 1";
        $params = [':doctor_id' => $doctor_id];

        if($date) {
            $where_clause .= " AND DATE(ds.created_at) <= :date";
            $params[':date'] = $date;
        }

        $query = "SELECT ds.*, c.name as clinic_name, c.address, c.city
                  FROM doctor_schedules ds
                  JOIN clinics c ON ds.clinic_id = c.id
                  WHERE " . $where_clause . "
                  ORDER BY ds.day_of_week, ds.start_time";

        $stmt = $this->conn->prepare($query);
        
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get available time slots for a specific date
     * الحصول على الأوقات المتاحة لتاريخ محدد
     */
    public function getAvailableSlots($doctor_id, $date) {
        $day_of_week = strtolower(date('l', strtotime($date)));
        
        // الحصول على جدول الطبيب لهذا اليوم
        $query = "SELECT ds.*, c.name as clinic_name
                  FROM doctor_schedules ds
                  JOIN clinics c ON ds.clinic_id = c.id
                  WHERE ds.doctor_id = :doctor_id 
                  AND ds.day_of_week = :day_of_week 
                  AND ds.is_active = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':doctor_id', $doctor_id);
        $stmt->bindParam(':day_of_week', $day_of_week);
        $stmt->execute();

        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $available_slots = [];

        foreach($schedules as $schedule) {
            // الحصول على المواعيد المحجوزة لهذا التاريخ
            $booked_query = "SELECT appointment_time 
                            FROM appointments 
                            WHERE doctor_id = :doctor_id 
                            AND appointment_date = :date 
                            AND status IN ('pending', 'confirmed')";

            $booked_stmt = $this->conn->prepare($booked_query);
            $booked_stmt->bindParam(':doctor_id', $doctor_id);
            $booked_stmt->bindParam(':date', $date);
            $booked_stmt->execute();

            $booked_times = array_column($booked_stmt->fetchAll(PDO::FETCH_ASSOC), 'appointment_time');

            // توليد الأوقات المتاحة
            $start_time = strtotime($schedule['start_time']);
            $end_time = strtotime($schedule['end_time']);
            $slot_duration = $schedule['slot_duration'] * 60; // تحويل إلى ثواني

            for($time = $start_time; $time < $end_time; $time += $slot_duration) {
                $time_str = date('H:i:s', $time);
                if(!in_array($time_str, $booked_times)) {
                    $available_slots[] = [
                        'time' => $time_str,
                        'clinic_name' => $schedule['clinic_name'],
                        'clinic_id' => $schedule['clinic_id']
                    ];
                }
            }
        }

        return $available_slots;
    }

    /**
     * Update doctor profile
     * تحديث ملف الطبيب
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET specialization_id = :specialization_id, 
                      experience_years = :experience_years,
                      consultation_fee = :consultation_fee,
                      bio = :bio,
                      education = :education,
                      languages = :languages,
                      is_available = :is_available,
                      updated_at = CURRENT_TIMESTAMP 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $this->bio = sanitize_input($this->bio);
        $this->education = sanitize_input($this->education);

        $stmt->bindParam(':specialization_id', $this->specialization_id);
        $stmt->bindParam(':experience_years', $this->experience_years);
        $stmt->bindParam(':consultation_fee', $this->consultation_fee);
        $stmt->bindParam(':bio', $this->bio);
        $stmt->bindParam(':education', $this->education);
        $stmt->bindParam(':languages', $this->languages);
        $stmt->bindParam(':is_available', $this->is_available);
        $stmt->bindParam(':id', $this->id);

        return $stmt->execute();
    }

    /**
     * Get doctor statistics
     * الحصول على إحصائيات الطبيب
     */
    public function getStatistics($doctor_id) {
        $query = "SELECT 
                    COUNT(a.id) as total_appointments,
                    COUNT(CASE WHEN a.status = 'completed' THEN 1 END) as completed_appointments,
                    COUNT(CASE WHEN a.status = 'pending' THEN 1 END) as pending_appointments,
                    COUNT(CASE WHEN a.status = 'cancelled' THEN 1 END) as cancelled_appointments,
                    AVG(r.rating) as average_rating,
                    COUNT(r.id) as total_reviews
                  FROM doctors d
                  LEFT JOIN appointments a ON d.id = a.doctor_id
                  LEFT JOIN reviews r ON d.id = r.doctor_id
                  WHERE d.id = :doctor_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':doctor_id', $doctor_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>