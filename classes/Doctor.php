<?php
/**
 * Doctor Class
 * Handles doctor management and search functionality
 */

require_once __DIR__ . '/../config/config.php';

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
    public $working_hours;
    public $is_available;
    public $rating;
    public $total_reviews;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Register new doctor
    public function register() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, specialization_id, license_number, experience_years, 
                   consultation_fee, bio, education, languages, working_hours) 
                  VALUES (:user_id, :specialization_id, :license_number, :experience_years, 
                          :consultation_fee, :bio, :education, :languages, :working_hours)";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->specialization_id = htmlspecialchars(strip_tags($this->specialization_id));
        $this->license_number = htmlspecialchars(strip_tags($this->license_number));
        $this->bio = htmlspecialchars(strip_tags($this->bio));
        $this->education = htmlspecialchars(strip_tags($this->education));

        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":specialization_id", $this->specialization_id);
        $stmt->bindParam(":license_number", $this->license_number);
        $stmt->bindParam(":experience_years", $this->experience_years);
        $stmt->bindParam(":consultation_fee", $this->consultation_fee);
        $stmt->bindParam(":bio", $this->bio);
        $stmt->bindParam(":education", $this->education);
        $stmt->bindParam(":languages", $this->languages);
        $stmt->bindParam(":working_hours", $this->working_hours);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Search doctors
    public function search($filters = []) {
        $query = "SELECT d.*, u.full_name, u.phone, u.profile_image, s.name_ar as specialization_name,
                         c.name as clinic_name, c.address, c.city
                  FROM " . $this->table_name . " d
                  LEFT JOIN users u ON d.user_id = u.id
                  LEFT JOIN specializations s ON d.specialization_id = s.id
                  LEFT JOIN doctor_clinics dc ON d.id = dc.doctor_id
                  LEFT JOIN clinics c ON dc.clinic_id = c.id
                  WHERE d.is_available = 1 AND u.is_active = 1";

        $params = [];

        // Add filters
        if(!empty($filters['specialization_id'])) {
            $query .= " AND d.specialization_id = :specialization_id";
            $params[':specialization_id'] = $filters['specialization_id'];
        }

        if(!empty($filters['city'])) {
            $query .= " AND c.city = :city";
            $params[':city'] = $filters['city'];
        }

        if(!empty($filters['search_term'])) {
            $query .= " AND (u.full_name LIKE :search_term OR s.name_ar LIKE :search_term)";
            $params[':search_term'] = '%' . $filters['search_term'] . '%';
        }

        if(!empty($filters['max_fee'])) {
            $query .= " AND d.consultation_fee <= :max_fee";
            $params[':max_fee'] = $filters['max_fee'];
        }

        // Add ordering
        if(!empty($filters['sort_by'])) {
            switch($filters['sort_by']) {
                case 'rating':
                    $query .= " ORDER BY d.rating DESC";
                    break;
                case 'fee_low':
                    $query .= " ORDER BY d.consultation_fee ASC";
                    break;
                case 'fee_high':
                    $query .= " ORDER BY d.consultation_fee DESC";
                    break;
                case 'experience':
                    $query .= " ORDER BY d.experience_years DESC";
                    break;
                default:
                    $query .= " ORDER BY d.rating DESC, d.total_reviews DESC";
            }
        } else {
            $query .= " ORDER BY d.rating DESC, d.total_reviews DESC";
        }

        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get doctor by ID
    public function getDoctorById($id) {
        $query = "SELECT d.*, u.full_name, u.phone, u.email, u.profile_image, 
                         s.name_ar as specialization_name, s.description as specialization_description
                  FROM " . $this->table_name . " d
                  LEFT JOIN users u ON d.user_id = u.id
                  LEFT JOIN specializations s ON d.specialization_id = s.id
                  WHERE d.id = :id AND d.is_available = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->user_id = $row['user_id'];
            $this->specialization_id = $row['specialization_id'];
            $this->license_number = $row['license_number'];
            $this->experience_years = $row['experience_years'];
            $this->consultation_fee = $row['consultation_fee'];
            $this->bio = $row['bio'];
            $this->education = $row['education'];
            $this->languages = $row['languages'];
            $this->working_hours = $row['working_hours'];
            $this->is_available = $row['is_available'];
            $this->rating = $row['rating'];
            $this->total_reviews = $row['total_reviews'];
            return $row;
        }
        return false;
    }

    // Get doctor's clinics
    public function getDoctorClinics($doctor_id) {
        $query = "SELECT c.*, dc.consultation_fee, dc.working_days, dc.working_hours
                  FROM doctor_clinics dc
                  LEFT JOIN clinics c ON dc.clinic_id = c.id
                  WHERE dc.doctor_id = :doctor_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":doctor_id", $doctor_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get doctor reviews
    public function getDoctorReviews($doctor_id, $limit = 10) {
        $query = "SELECT r.*, u.full_name as patient_name, u.profile_image
                  FROM reviews r
                  LEFT JOIN users u ON r.patient_id = u.id
                  WHERE r.doctor_id = :doctor_id
                  ORDER BY r.created_at DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":doctor_id", $doctor_id);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Update doctor rating
    public function updateRating($doctor_id) {
        $query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                  FROM reviews WHERE doctor_id = :doctor_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":doctor_id", $doctor_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $update_query = "UPDATE " . $this->table_name . " 
                         SET rating = :rating, total_reviews = :total_reviews 
                         WHERE id = :doctor_id";

        $update_stmt = $this->conn->prepare($update_query);
        $update_stmt->bindParam(":rating", $result['avg_rating']);
        $update_stmt->bindParam(":total_reviews", $result['total_reviews']);
        $update_stmt->bindParam(":doctor_id", $doctor_id);

        return $update_stmt->execute();
    }

    // Get all specializations
    public function getSpecializations() {
        $query = "SELECT * FROM specializations WHERE is_active = 1 ORDER BY name_ar";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get doctors by specialization
    public function getDoctorsBySpecialization($specialization_id) {
        $query = "SELECT d.*, u.full_name, u.profile_image, s.name_ar as specialization_name
                  FROM " . $this->table_name . " d
                  LEFT JOIN users u ON d.user_id = u.id
                  LEFT JOIN specializations s ON d.specialization_id = s.id
                  WHERE d.specialization_id = :specialization_id 
                  AND d.is_available = 1 AND u.is_active = 1
                  ORDER BY d.rating DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":specialization_id", $specialization_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Update doctor availability
    public function updateAvailability($doctor_id, $is_available) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_available = :is_available, updated_at = CURRENT_TIMESTAMP 
                  WHERE id = :doctor_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":is_available", $is_available, PDO::PARAM_BOOL);
        $stmt->bindParam(":doctor_id", $doctor_id);

        return $stmt->execute();
    }
}
?>