<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';
require_once '../config/config.php';
require_once '../classes/Doctor.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

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
                    handleSearchDoctors($doctor);
                    break;
                case 'profile':
                    handleGetDoctorProfile($doctor);
                    break;
                case 'schedule':
                    handleGetDoctorSchedule($doctor);
                    break;
                case 'available-slots':
                    handleGetAvailableSlots($doctor);
                    break;
                case 'specializations':
                    handleGetSpecializations($db);
                    break;
                default:
                    send_json_response(['error' => 'إجراء غير صحيح'], 400);
            }
            break;
        case 'POST':
            switch($action) {
                case 'create':
                    handleCreateDoctor($doctor);
                    break;
                default:
                    send_json_response(['error' => 'إجراء غير صحيح'], 400);
            }
            break;
        case 'PUT':
            switch($action) {
                case 'update':
                    handleUpdateDoctor($doctor);
                    break;
                default:
                    send_json_response(['error' => 'إجراء غير صحيح'], 400);
            }
            break;
        default:
            send_json_response(['error' => 'طريقة طلب غير مدعومة'], 405);
    }
} catch(Exception $e) {
    error_log("Doctors API Error: " . $e->getMessage());
    send_json_response(['error' => 'حدث خطأ في الخادم'], 500);
}

/**
 * Handle doctor search
 * معالجة البحث عن الأطباء
 */
function handleSearchDoctors($doctor) {
    $specialization_id = $_GET['specialization_id'] ?? null;
    $city = $_GET['city'] ?? null;
    $min_fee = $_GET['min_fee'] ?? null;
    $max_fee = $_GET['max_fee'] ?? null;
    $gender = $_GET['gender'] ?? null;
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 10;

    $results = $doctor->search($specialization_id, $city, $min_fee, $max_fee, $gender, $page, $limit);
    
    send_json_response([
        'success' => true,
        'doctors' => $results,
        'pagination' => [
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total' => count($results)
        ]
    ]);
}

/**
 * Handle get doctor profile
 * معالجة الحصول على ملف الطبيب
 */
function handleGetDoctorProfile($doctor) {
    $doctor_id = $_GET['id'] ?? null;
    
    if(!$doctor_id) {
        send_json_response(['error' => 'معرف الطبيب مطلوب'], 400);
    }

    $profile = $doctor->getById($doctor_id);
    
    if($profile) {
        send_json_response([
            'success' => true,
            'doctor' => $profile
        ]);
    } else {
        send_json_response(['error' => 'الطبيب غير موجود'], 404);
    }
}

/**
 * Handle get doctor schedule
 * معالجة الحصول على جدول الطبيب
 */
function handleGetDoctorSchedule($doctor) {
    $doctor_id = $_GET['doctor_id'] ?? null;
    $date = $_GET['date'] ?? null;
    
    if(!$doctor_id) {
        send_json_response(['error' => 'معرف الطبيب مطلوب'], 400);
    }

    $schedule = $doctor->getSchedule($doctor_id, $date);
    
    send_json_response([
        'success' => true,
        'schedule' => $schedule
    ]);
}

/**
 * Handle get available time slots
 * معالجة الحصول على الأوقات المتاحة
 */
function handleGetAvailableSlots($doctor) {
    $doctor_id = $_GET['doctor_id'] ?? null;
    $date = $_GET['date'] ?? null;
    
    if(!$doctor_id || !$date) {
        send_json_response(['error' => 'معرف الطبيب والتاريخ مطلوبان'], 400);
    }

    // التحقق من صحة التاريخ
    if(!validate_date($date)) {
        send_json_response(['error' => 'التاريخ غير صحيح'], 400);
    }

    $slots = $doctor->getAvailableSlots($doctor_id, $date);
    
    send_json_response([
        'success' => true,
        'available_slots' => $slots
    ]);
}

/**
 * Handle get specializations
 * معالجة الحصول على التخصصات
 */
function handleGetSpecializations($db) {
    $query = "SELECT id, name_ar, name_en, description, icon 
              FROM specializations 
              WHERE is_active = 1 
              ORDER BY name_ar";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $specializations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    send_json_response([
        'success' => true,
        'specializations' => $specializations
    ]);
}

/**
 * Handle create doctor
 * معالجة إنشاء طبيب
 */
function handleCreateDoctor($doctor) {
    check_role(['admin']);
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    // التحقق من البيانات المطلوبة
    if(empty($data['user_id']) || empty($data['specialization_id']) || empty($data['license_number'])) {
        send_json_response(['error' => 'جميع الحقول المطلوبة يجب ملؤها'], 400);
    }

    // إعداد بيانات الطبيب
    $doctor->user_id = $data['user_id'];
    $doctor->specialization_id = $data['specialization_id'];
    $doctor->license_number = $data['license_number'];
    $doctor->experience_years = $data['experience_years'] ?? 0;
    $doctor->consultation_fee = $data['consultation_fee'] ?? 0;
    $doctor->bio = $data['bio'] ?? null;
    $doctor->education = $data['education'] ?? null;
    $doctor->languages = json_encode($data['languages'] ?? []);

    if($doctor->create()) {
        send_json_response([
            'success' => true,
            'message' => 'تم إنشاء الطبيب بنجاح',
            'doctor_id' => $doctor->id
        ], 201);
    } else {
        send_json_response(['error' => 'فشل في إنشاء الطبيب'], 500);
    }
}

/**
 * Handle update doctor
 * معالجة تحديث الطبيب
 */
function handleUpdateDoctor($doctor) {
    check_auth();
    
    $data = json_decode(file_get_contents("php://input"), true);
    $doctor_id = $_GET['id'] ?? null;
    
    if(!$doctor_id) {
        send_json_response(['error' => 'معرف الطبيب مطلوب'], 400);
    }

    // التحقق من أن المستخدم هو الطبيب نفسه أو مدير
    if($_SESSION['user_role'] != 'admin') {
        $doctor_profile = $doctor->getByUserId($_SESSION['user_id']);
        if(!$doctor_profile || $doctor_profile['id'] != $doctor_id) {
            send_json_response(['error' => 'ليس لديك صلاحية لتحديث هذا الطبيب'], 403);
        }
    }

    // إعداد بيانات التحديث
    $doctor->id = $doctor_id;
    $doctor->specialization_id = $data['specialization_id'] ?? null;
    $doctor->experience_years = $data['experience_years'] ?? null;
    $doctor->consultation_fee = $data['consultation_fee'] ?? null;
    $doctor->bio = $data['bio'] ?? null;
    $doctor->education = $data['education'] ?? null;
    $doctor->languages = json_encode($data['languages'] ?? []);
    $doctor->is_available = $data['is_available'] ?? null;

    if($doctor->update()) {
        send_json_response([
            'success' => true,
            'message' => 'تم تحديث الطبيب بنجاح'
        ]);
    } else {
        send_json_response(['error' => 'فشل في تحديث الطبيب'], 500);
    }
}
?>