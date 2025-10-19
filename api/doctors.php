<?php
/**
 * Doctors API Endpoints
 * نقاط نهاية واجهة برمجة التطبيقات للأطباء
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../classes/Doctor.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$database = new Database();
$db = $database->getConnection();
$doctor = new Doctor($db);

$action = $_GET['action'] ?? '';
$response = ['success' => false, 'message' => '', 'data' => null];

try {
    switch ($action) {
        case 'search':
            $specialty_id = $_GET['specialty_id'] ?? null;
            $city = $_GET['city'] ?? null;
            $insurance_id = $_GET['insurance_id'] ?? null;
            $search = $_GET['search'] ?? null;
            $limit = intval($_GET['limit'] ?? 20);
            $offset = intval($_GET['offset'] ?? 0);
            
            $doctors = $doctor->getAllDoctors($specialty_id, $city, $insurance_id, $search, $limit, $offset);
            
            $response['success'] = true;
            $response['data'] = $doctors;
            break;

        case 'get_doctor':
            $doctor_id = $_GET['id'] ?? '';
            
            if (empty($doctor_id)) {
                throw new Exception('معرف الطبيب مطلوب');
            }
            
            $doctor_data = $doctor->getDoctorById($doctor_id);
            
            if ($doctor_data) {
                // Get doctor statistics
                $stats = $doctor->getDoctorStats($doctor_id);
                $doctor_data['stats'] = $stats;
                
                $response['success'] = true;
                $response['data'] = $doctor_data;
            } else {
                throw new Exception('الطبيب غير موجود');
            }
            break;

        case 'get_reviews':
            $doctor_id = $_GET['doctor_id'] ?? '';
            $limit = intval($_GET['limit'] ?? 10);
            $offset = intval($_GET['offset'] ?? 0);
            
            if (empty($doctor_id)) {
                throw new Exception('معرف الطبيب مطلوب');
            }
            
            $reviews = $doctor->getDoctorReviews($doctor_id, $limit, $offset);
            
            $response['success'] = true;
            $response['data'] = $reviews;
            break;

        case 'get_specialties':
            $query = "SELECT * FROM specialties WHERE is_active = 1 ORDER BY name_ar";
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $specialties = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response['success'] = true;
            $response['data'] = $specialties;
            break;

        case 'get_insurance_companies':
            $query = "SELECT * FROM insurance_companies WHERE is_active = 1 ORDER BY name_ar";
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $insurance_companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response['success'] = true;
            $response['data'] = $insurance_companies;
            break;

        case 'get_cities':
            $query = "SELECT DISTINCT city FROM users WHERE city IS NOT NULL AND city != '' ORDER BY city";
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $cities = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $response['success'] = true;
            $response['data'] = $cities;
            break;

        case 'get_featured':
            // Get top-rated doctors
            $query = "SELECT d.*, u.first_name, u.last_name, u.profile_image, u.city,
                             s.name_ar as specialty_name, s.icon as specialty_icon
                      FROM doctors d
                      INNER JOIN users u ON d.user_id = u.id
                      INNER JOIN specialties s ON d.specialty_id = s.id
                      WHERE d.is_available = 1 AND d.is_verified = 1 AND u.is_active = 1
                      ORDER BY d.rating DESC, d.total_reviews DESC
                      LIMIT 8";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $featured_doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response['success'] = true;
            $response['data'] = $featured_doctors;
            break;

        case 'get_availability':
            $doctor_id = $_GET['doctor_id'] ?? '';
            $date = $_GET['date'] ?? '';
            
            if (empty($doctor_id) || empty($date)) {
                throw new Exception('معرف الطبيب والتاريخ مطلوبان');
            }
            
            // Validate date format
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                throw new Exception('تنسيق التاريخ غير صحيح');
            }
            
            // Check if date is not in the past
            if (strtotime($date) < strtotime(date('Y-m-d'))) {
                throw new Exception('لا يمكن الاستعلام عن مواعيد في الماضي');
            }
            
            $availability = $doctor->getDoctorAvailability($doctor_id, $date);
            
            $response['success'] = true;
            $response['data'] = $availability;
            break;

        case 'get_working_days':
            $doctor_id = $_GET['doctor_id'] ?? '';
            
            if (empty($doctor_id)) {
                throw new Exception('معرف الطبيب مطلوب');
            }
            
            $query = "SELECT working_hours FROM doctors WHERE id = :doctor_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":doctor_id", $doctor_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $doctor_data = $stmt->fetch(PDO::FETCH_ASSOC);
                $working_hours = json_decode($doctor_data['working_hours'], true);
                
                // Get next 30 days with availability
                $available_days = [];
                $start_date = strtotime('today');
                
                for ($i = 0; $i < 30; $i++) {
                    $current_date = date('Y-m-d', $start_date + ($i * 24 * 60 * 60));
                    $day_of_week = strtolower(date('l', strtotime($current_date)));
                    
                    if (isset($working_hours[$day_of_week]) && $working_hours[$day_of_week]['is_working']) {
                        $available_days[] = [
                            'date' => $current_date,
                            'day_name' => $this->getDayNameInArabic($day_of_week),
                            'working_hours' => $working_hours[$day_of_week]
                        ];
                    }
                }
                
                $response['success'] = true;
                $response['data'] = $available_days;
            } else {
                throw new Exception('الطبيب غير موجود');
            }
            break;

        default:
            throw new Exception('عملية غير مدعومة');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

/**
 * Get day name in Arabic
 */
function getDayNameInArabic($day) {
    $days = [
        'sunday' => 'الأحد',
        'monday' => 'الاثنين',
        'tuesday' => 'الثلاثاء',
        'wednesday' => 'الأربعاء',
        'thursday' => 'الخميس',
        'friday' => 'الجمعة',
        'saturday' => 'السبت'
    ];
    
    return $days[$day] ?? $day;
}
?>