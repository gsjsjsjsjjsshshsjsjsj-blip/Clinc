<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';
require_once '../config/config.php';
require_once '../classes/Appointment.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$database = new Database();
$db = $database->getConnection();
$appointment = new Appointment($db);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch($method) {
        case 'GET':
            switch($action) {
                case 'my-appointments':
                    handleGetMyAppointments($appointment);
                    break;
                case 'doctor-appointments':
                    handleGetDoctorAppointments($appointment);
                    break;
                case 'details':
                    handleGetAppointmentDetails($appointment);
                    break;
                case 'statistics':
                    handleGetAppointmentStatistics($appointment);
                    break;
                default:
                    send_json_response(['error' => 'إجراء غير صحيح'], 400);
            }
            break;
        case 'POST':
            switch($action) {
                case 'book':
                    handleBookAppointment($appointment);
                    break;
                default:
                    send_json_response(['error' => 'إجراء غير صحيح'], 400);
            }
            break;
        case 'PUT':
            switch($action) {
                case 'update-status':
                    handleUpdateAppointmentStatus($appointment);
                    break;
                case 'cancel':
                    handleCancelAppointment($appointment);
                    break;
                default:
                    send_json_response(['error' => 'إجراء غير صحيح'], 400);
            }
            break;
        default:
            send_json_response(['error' => 'طريقة طلب غير مدعومة'], 405);
    }
} catch(Exception $e) {
    error_log("Appointments API Error: " . $e->getMessage());
    send_json_response(['error' => 'حدث خطأ في الخادم'], 500);
}

/**
 * Handle get my appointments
 * معالجة الحصول على مواعيدي
 */
function handleGetMyAppointments($appointment) {
    check_auth();
    
    $status = $_GET['status'] ?? null;
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 10;

    $appointments = $appointment->getByPatientId($_SESSION['user_id'], $status, $page, $limit);
    
    send_json_response([
        'success' => true,
        'appointments' => $appointments,
        'pagination' => [
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total' => count($appointments)
        ]
    ]);
}

/**
 * Handle get doctor appointments
 * معالجة الحصول على مواعيد الطبيب
 */
function handleGetDoctorAppointments($appointment) {
    check_role(['doctor', 'admin']);
    
    $doctor_id = $_GET['doctor_id'] ?? $_SESSION['user_id'];
    $date = $_GET['date'] ?? null;
    $status = $_GET['status'] ?? null;
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 10;

    // إذا كان المستخدم طبيب، يجب أن يكون هو نفسه
    if($_SESSION['user_role'] == 'doctor') {
        $doctor_id = $_SESSION['user_id'];
    }

    $appointments = $appointment->getByDoctorId($doctor_id, $date, $status, $page, $limit);
    
    send_json_response([
        'success' => true,
        'appointments' => $appointments,
        'pagination' => [
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total' => count($appointments)
        ]
    ]);
}

/**
 * Handle get appointment details
 * معالجة الحصول على تفاصيل الموعد
 */
function handleGetAppointmentDetails($appointment) {
    check_auth();
    
    $appointment_id = $_GET['id'] ?? null;
    
    if(!$appointment_id) {
        send_json_response(['error' => 'معرف الموعد مطلوب'], 400);
    }

    $details = $appointment->getById($appointment_id);
    
    if($details) {
        // التحقق من أن المستخدم له صلاحية رؤية هذا الموعد
        if($_SESSION['user_role'] == 'patient' && $details['patient_id'] != $_SESSION['user_id']) {
            send_json_response(['error' => 'ليس لديك صلاحية لرؤية هذا الموعد'], 403);
        }
        
        if($_SESSION['user_role'] == 'doctor' && $details['doctor_id'] != $_SESSION['user_id']) {
            send_json_response(['error' => 'ليس لديك صلاحية لرؤية هذا الموعد'], 403);
        }
        
        send_json_response([
            'success' => true,
            'appointment' => $details
        ]);
    } else {
        send_json_response(['error' => 'الموعد غير موجود'], 404);
    }
}

/**
 * Handle book appointment
 * معالجة حجز الموعد
 */
function handleBookAppointment($appointment) {
    check_auth();
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    // التحقق من البيانات المطلوبة
    if(empty($data['doctor_id']) || empty($data['clinic_id']) || empty($data['appointment_date']) || empty($data['appointment_time'])) {
        send_json_response(['error' => 'جميع الحقول المطلوبة يجب ملؤها'], 400);
    }

    // التحقق من صحة التاريخ
    if(!validate_date($data['appointment_date'])) {
        send_json_response(['error' => 'التاريخ غير صحيح'], 400);
    }

    // التحقق من أن التاريخ ليس في الماضي
    if(strtotime($data['appointment_date']) < strtotime('today')) {
        send_json_response(['error' => 'لا يمكن حجز موعد في الماضي'], 400);
    }

    // إعداد بيانات الموعد
    $appointment->patient_id = $_SESSION['user_id'];
    $appointment->doctor_id = $data['doctor_id'];
    $appointment->clinic_id = $data['clinic_id'];
    $appointment->appointment_date = $data['appointment_date'];
    $appointment->appointment_time = $data['appointment_time'];
    $appointment->status = 'pending';
    $appointment->consultation_type = $data['consultation_type'] ?? 'in_person';
    $appointment->notes = $data['notes'] ?? null;
    $appointment->patient_notes = $data['patient_notes'] ?? null;

    $result = $appointment->create();
    
    if($result['success']) {
        send_json_response($result, 201);
    } else {
        send_json_response($result, 400);
    }
}

/**
 * Handle update appointment status
 * معالجة تحديث حالة الموعد
 */
function handleUpdateAppointmentStatus($appointment) {
    check_role(['doctor', 'admin']);
    
    $data = json_decode(file_get_contents("php://input"), true);
    $appointment_id = $_GET['id'] ?? null;
    
    if(!$appointment_id) {
        send_json_response(['error' => 'معرف الموعد مطلوب'], 400);
    }

    if(empty($data['status'])) {
        send_json_response(['error' => 'حالة الموعد مطلوبة'], 400);
    }

    $valid_statuses = ['pending', 'confirmed', 'cancelled', 'completed', 'no_show'];
    if(!in_array($data['status'], $valid_statuses)) {
        send_json_response(['error' => 'حالة الموعد غير صحيحة'], 400);
    }

    if($appointment->updateStatus($appointment_id, $data['status'], $data['notes'] ?? null)) {
        send_json_response([
            'success' => true,
            'message' => 'تم تحديث حالة الموعد بنجاح'
        ]);
    } else {
        send_json_response(['error' => 'فشل في تحديث حالة الموعد'], 500);
    }
}

/**
 * Handle cancel appointment
 * معالجة إلغاء الموعد
 */
function handleCancelAppointment($appointment) {
    check_auth();
    
    $data = json_decode(file_get_contents("php://input"), true);
    $appointment_id = $_GET['id'] ?? null;
    
    if(!$appointment_id) {
        send_json_response(['error' => 'معرف الموعد مطلوب'], 400);
    }

    // التحقق من أن المستخدم هو صاحب الموعد أو طبيب
    $appointment_details = $appointment->getById($appointment_id);
    if(!$appointment_details) {
        send_json_response(['error' => 'الموعد غير موجود'], 404);
    }

    if($_SESSION['user_role'] == 'patient' && $appointment_details['patient_id'] != $_SESSION['user_id']) {
        send_json_response(['error' => 'ليس لديك صلاحية لإلغاء هذا الموعد'], 403);
    }

    if($_SESSION['user_role'] == 'doctor' && $appointment_details['doctor_id'] != $_SESSION['user_id']) {
        send_json_response(['error' => 'ليس لديك صلاحية لإلغاء هذا الموعد'], 403);
    }

    if($appointment->cancel($appointment_id, $data['reason'] ?? null)) {
        send_json_response([
            'success' => true,
            'message' => 'تم إلغاء الموعد بنجاح'
        ]);
    } else {
        send_json_response(['error' => 'فشل في إلغاء الموعد'], 500);
    }
}

/**
 * Handle get appointment statistics
 * معالجة الحصول على إحصائيات المواعيد
 */
function handleGetAppointmentStatistics($appointment) {
    check_auth();
    
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];
    
    $statistics = $appointment->getStatistics($user_id, $user_role);
    
    send_json_response([
        'success' => true,
        'statistics' => $statistics
    ]);
}
?>