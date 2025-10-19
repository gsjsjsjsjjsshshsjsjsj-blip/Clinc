<?php
/**
 * Doctors API Endpoints
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Doctor.php';

$database = new Database();
$db = $database->getConnection();
$doctor = new Doctor($db);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch($method) {
        case 'GET':
            switch($action) {
                case 'search':
                    searchDoctors($doctor);
                    break;
                case 'specializations':
                    getSpecializations($doctor);
                    break;
                case 'by_specialization':
                    getDoctorsBySpecialization($doctor);
                    break;
                case 'details':
                    getDoctorDetails($doctor);
                    break;
                case 'reviews':
                    getDoctorReviews($doctor);
                    break;
                default:
                    throw new Exception('Invalid action');
            }
            break;
        case 'POST':
            switch($action) {
                case 'register':
                    registerDoctor($doctor);
                    break;
                default:
                    throw new Exception('Invalid action');
            }
            break;
        default:
            throw new Exception('Method not allowed');
    }
} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function searchDoctors($doctor) {
    $filters = [
        'specialization_id' => $_GET['specialization_id'] ?? null,
        'city' => $_GET['city'] ?? null,
        'search_term' => $_GET['search'] ?? null,
        'max_fee' => $_GET['max_fee'] ?? null,
        'sort_by' => $_GET['sort_by'] ?? null
    ];

    // Remove empty filters
    $filters = array_filter($filters, function($value) {
        return $value !== null && $value !== '';
    });

    $doctors = $doctor->search($filters);
    
    echo json_encode([
        'success' => true,
        'doctors' => $doctors,
        'total' => count($doctors)
    ]);
}

function getSpecializations($doctor) {
    $specializations = $doctor->getSpecializations();
    
    echo json_encode([
        'success' => true,
        'specializations' => $specializations
    ]);
}

function getDoctorsBySpecialization($doctor) {
    $specialization_id = $_GET['specialization_id'] ?? null;
    
    if(!$specialization_id) {
        throw new Exception('معرف التخصص مطلوب');
    }

    $doctors = $doctor->getDoctorsBySpecialization($specialization_id);
    
    echo json_encode([
        'success' => true,
        'doctors' => $doctors
    ]);
}

function getDoctorDetails($doctor) {
    $doctor_id = $_GET['doctor_id'] ?? null;
    
    if(!$doctor_id) {
        throw new Exception('معرف الطبيب مطلوب');
    }

    $doctor_details = $doctor->getDoctorById($doctor_id);
    
    if($doctor_details) {
        // Get doctor's clinics
        $clinics = $doctor->getDoctorClinics($doctor_id);
        $doctor_details['clinics'] = $clinics;
        
        echo json_encode([
            'success' => true,
            'doctor' => $doctor_details
        ]);
    } else {
        throw new Exception('الطبيب غير موجود');
    }
}

function getDoctorReviews($doctor) {
    $doctor_id = $_GET['doctor_id'] ?? null;
    $limit = $_GET['limit'] ?? 10;
    
    if(!$doctor_id) {
        throw new Exception('معرف الطبيب مطلوب');
    }

    $reviews = $doctor->getDoctorReviews($doctor_id, $limit);
    
    echo json_encode([
        'success' => true,
        'reviews' => $reviews
    ]);
}

function registerDoctor($doctor) {
    // Check if user is logged in
    if(!User::isLoggedIn()) {
        throw new Exception('يجب تسجيل الدخول أولاً');
    }

    $data = json_decode(file_get_contents("php://input"), true);
    
    // Validate required fields
    $required_fields = ['specialization_id', 'license_number', 'consultation_fee'];
    foreach($required_fields as $field) {
        if(empty($data[$field])) {
            throw new Exception("حقل {$field} مطلوب");
        }
    }

    // Set doctor data
    $doctor->user_id = $_SESSION['user_id'];
    $doctor->specialization_id = $data['specialization_id'];
    $doctor->license_number = $data['license_number'];
    $doctor->experience_years = $data['experience_years'] ?? 0;
    $doctor->consultation_fee = $data['consultation_fee'];
    $doctor->bio = $data['bio'] ?? null;
    $doctor->education = $data['education'] ?? null;
    $doctor->languages = json_encode($data['languages'] ?? []);
    $doctor->working_hours = json_encode($data['working_hours'] ?? []);

    if($doctor->register()) {
        // Update user role to doctor
        $update_query = "UPDATE users SET role = 'doctor' WHERE id = :user_id";
        $stmt = $db->prepare($update_query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();

        echo json_encode([
            'success' => true,
            'message' => 'تم تسجيل الطبيب بنجاح',
            'doctor_id' => $doctor->id
        ]);
    } else {
        throw new Exception('فشل في تسجيل الطبيب');
    }
}
?>