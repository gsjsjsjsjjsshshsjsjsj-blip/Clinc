<?php
/**
 * Doctor Class - فئة الطبيب
 * Handles doctor-related operations
 */

require_once '../config/database.php';

class Doctor {
    private $conn;
    private $table_name = "doctors";

    // Doctor properties
    public $id;
    public $user_id;
    public $specialty_id;
    public $license_number;
    public $experience_years;
    public $consultation_fee;
    public $bio;
    public $education;
    public $languages;
    public $clinic_name;
    public $clinic_address;
    public $clinic_phone;
    public $working_hours;
    public $rating;
    public $total_reviews;
    public $is_verified;
    public $is_available;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all doctors with filters - الحصول على جميع الأطباء مع الفلاتر
     */
    public function getAllDoctors($specialty_id = null, $city = null, $insurance_id = null, $search = null, $limit = 20, $offset = 0) {
        $query = "SELECT d.*, u.first_name, u.last_name, u.profile_image, u.city,
                         s.name_ar as specialty_name, s.icon as specialty_icon,
                         AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
                  FROM " . $this->table_name . " d
                  INNER JOIN users u ON d.user_id = u.id
                  INNER JOIN specialties s ON d.specialty_id = s.id
                  LEFT JOIN reviews r ON d.id = r.doctor_id AND r.is_approved = 1
                  WHERE d.is_available = 1 AND d.is_verified = 1 AND u.is_active = 1";

        $params = [];

        // Add filters
        if ($specialty_id) {
            $query .= " AND d.specialty_id = :specialty_id";
            $params[':specialty_id'] = $specialty_id;
        }

        if ($city) {
            $query .= " AND u.city LIKE :city";
            $params[':city'] = "%$city%";
        }

        if ($search) {
            $query .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR s.name_ar LIKE :search)";
            $params[':search'] = "%$search%";
        }

        if ($insurance_id) {
            $query .= " AND EXISTS (SELECT 1 FROM doctor_insurance di WHERE di.doctor_id = d.id AND di.insurance_id = :insurance_id)";
            $params[':insurance_id'] = $insurance_id;
        }

        $query .= " GROUP BY d.id ORDER BY d.rating DESC, d.total_reviews DESC LIMIT :limit OFFSET :offset";

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
     * Get doctor by ID - الحصول على الطبيب بالمعرف
     */
    public function getDoctorById($id) {
        $query = "SELECT d.*, u.first_name, u.last_name, u.email, u.phone, u.profile_image, u.city,
                         s.name_ar as specialty_name, s.name_en as specialty_name_en, s.icon as specialty_icon
                  FROM " . $this->table_name . " d
                  INNER JOIN users u ON d.user_id = u.id
                  INNER JOIN specialties s ON d.specialty_id = s.id
                  WHERE d.id = :id AND d.is_available = 1 AND u.is_active = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return false;
    }

    /**
     * Get doctor availability - الحصول على مواعيد الطبيب المتاحة
     */
    public function getDoctorAvailability($doctor_id, $date) {
        // Get doctor's working hours
        $query = "SELECT working_hours FROM " . $this->table_name . " WHERE id = :doctor_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":doctor_id", $doctor_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            return [];
        }
        
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
        $working_hours = json_decode($doctor['working_hours'], true);
        
        $day_of_week = strtolower(date('l', strtotime($date)));
        
        if (!isset($working_hours[$day_of_week]) || !$working_hours[$day_of_week]['is_working']) {
            return [];
        }
        
        // Get booked appointments for this date
        $query = "SELECT appointment_time, duration_minutes 
                  FROM appointments 
                  WHERE doctor_id = :doctor_id AND appointment_date = :date 
                  AND status IN ('pending', 'confirmed')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":doctor_id", $doctor_id);
        $stmt->bindParam(":date", $date);
        $stmt->execute();
        
        $booked_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Generate available time slots
        $available_slots = $this->generateTimeSlots(
            $working_hours[$day_of_week]['start_time'],
            $working_hours[$day_of_week]['end_time'],
            $working_hours[$day_of_week]['break_start'] ?? null,
            $working_hours[$day_of_week]['break_end'] ?? null,
            $booked_slots
        );
        
        return $available_slots;
    }

    /**
     * Generate time slots - توليد الفترات الزمنية المتاحة
     */
    private function generateTimeSlots($start_time, $end_time, $break_start = null, $break_end = null, $booked_slots = []) {
        $slots = [];
        $slot_duration = 30; // 30 minutes per slot
        
        $current_time = strtotime($start_time);
        $end_timestamp = strtotime($end_time);
        $break_start_timestamp = $break_start ? strtotime($break_start) : null;
        $break_end_timestamp = $break_end ? strtotime($break_end) : null;
        
        while ($current_time < $end_timestamp) {
            $slot_time = date('H:i:s', $current_time);
            
            // Skip break time
            if ($break_start_timestamp && $break_end_timestamp &&
                $current_time >= $break_start_timestamp && $current_time < $break_end_timestamp) {
                $current_time = $break_end_timestamp;
                continue;
            }
            
            // Check if slot is booked
            $is_booked = false;
            foreach ($booked_slots as $booked) {
                $booked_start = strtotime($booked['appointment_time']);
                $booked_end = $booked_start + ($booked['duration_minutes'] * 60);
                
                if ($current_time >= $booked_start && $current_time < $booked_end) {
                    $is_booked = true;
                    break;
                }
            }
            
            if (!$is_booked) {
                $slots[] = [
                    'time' => $slot_time,
                    'display_time' => date('g:i A', $current_time),
                    'available' => true
                ];
            }
            
            $current_time += ($slot_duration * 60);
        }
        
        return $slots;
    }

    /**
     * Get doctor reviews - الحصول على تقييمات الطبيب
     */
    public function getDoctorReviews($doctor_id, $limit = 10, $offset = 0) {
        $query = "SELECT r.*, u.first_name, u.last_name, u.profile_image
                  FROM reviews r
                  INNER JOIN users u ON r.patient_id = u.id
                  WHERE r.doctor_id = :doctor_id AND r.is_approved = 1
                  ORDER BY r.created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":doctor_id", $doctor_id);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get doctor statistics - الحصول على إحصائيات الطبيب
     */
    public function getDoctorStats($doctor_id) {
        $stats = [];
        
        // Total appointments
        $query = "SELECT COUNT(*) as total_appointments FROM appointments WHERE doctor_id = :doctor_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":doctor_id", $doctor_id);
        $stmt->execute();
        $stats['total_appointments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_appointments'];
        
        // Completed appointments
        $query = "SELECT COUNT(*) as completed_appointments FROM appointments 
                  WHERE doctor_id = :doctor_id AND status = 'completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":doctor_id", $doctor_id);
        $stmt->execute();
        $stats['completed_appointments'] = $stmt->fetch(PDO::FETCH_ASSOC)['completed_appointments'];
        
        // Average rating
        $query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                  FROM reviews WHERE doctor_id = :doctor_id AND is_approved = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":doctor_id", $doctor_id);
        $stmt->execute();
        $rating_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['avg_rating'] = round($rating_data['avg_rating'], 1);
        $stats['total_reviews'] = $rating_data['total_reviews'];
        
        return $stats;
    }

    /**
     * Register new doctor - تسجيل طبيب جديد
     */
    public function registerDoctor() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET user_id=:user_id, specialty_id=:specialty_id, license_number=:license_number,
                      experience_years=:experience_years, consultation_fee=:consultation_fee,
                      bio=:bio, education=:education, languages=:languages,
                      clinic_name=:clinic_name, clinic_address=:clinic_address, clinic_phone=:clinic_phone,
                      working_hours=:working_hours";

        $stmt = $this->conn->prepare($query);

        // Sanitize data
        $this->bio = htmlspecialchars(strip_tags($this->bio));
        $this->education = htmlspecialchars(strip_tags($this->education));
        $this->clinic_name = htmlspecialchars(strip_tags($this->clinic_name));
        $this->clinic_address = htmlspecialchars(strip_tags($this->clinic_address));

        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":specialty_id", $this->specialty_id);
        $stmt->bindParam(":license_number", $this->license_number);
        $stmt->bindParam(":experience_years", $this->experience_years);
        $stmt->bindParam(":consultation_fee", $this->consultation_fee);
        $stmt->bindParam(":bio", $this->bio);
        $stmt->bindParam(":education", $this->education);
        $stmt->bindParam(":languages", $this->languages);
        $stmt->bindParam(":clinic_name", $this->clinic_name);
        $stmt->bindParam(":clinic_address", $this->clinic_address);
        $stmt->bindParam(":clinic_phone", $this->clinic_phone);
        $stmt->bindParam(":working_hours", $this->working_hours);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }
}
?>