<?php
/**
 * Appointments API Endpoints
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Appointment.php';

$database = new Database();
$db = $database->getConnection();
$appointment = new Appointment($db);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Check authentication
if(!User::isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'يجب تسجيل الدخول أولاً'
    ]);
    exit();
}

try {
    switch($method) {
        case 'POST':
            switch($action) {
                case 'book':
                    bookAppointment($appointment);
                    break;
                default:
                    throw new Exception('Invalid action');
            }
            break;
        case 'GET':
            switch($action) {
                case 'my_appointments':
                    getMyAppointments($appointment);
                    break;
                case 'doctor_appointments':
                    getDoctorAppointments($appointment);
                    break;
                case 'available_slots':
                    getAvailableSlots($appointment);
                    break;
                default:
                    throw new Exception('Invalid action');
            }
            break;
        case 'PUT':
            switch($action) {
                case 'update_status':
                    updateAppointmentStatus($appointment);
                    break;
                case 'cancel':
                    cancelAppointment($appointment);
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

function bookAppointment($appointment) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Validate required fields
    $required_fields = ['doctor_id', 'appointment_date', 'appointment_time'];
    foreach($required_fields as $field) {
        if(empty($data[$field])) {
            throw new Exception("حقل {$field} مطلوب");
        }
    }

    // Validate date format
    $appointment_date = DateTime::createFromFormat('Y-m-d', $data['appointment_date']);
    if(!$appointment_date || $appointment_date->format('Y-m-d') !== $data['appointment_date']) {
        throw new Exception("تاريخ الموعد غير صحيح");
    }

    // Check if appointment date is not in the past
    if($appointment_date < new DateTime('today')) {
        throw new Exception("لا يمكن حجز موعد في تاريخ سابق");
    }

    // Set appointment data
    $appointment->patient_id = $_SESSION['user_id'];
    $appointment->doctor_id = $data['doctor_id'];
    $appointment->clinic_id = $data['clinic_id'] ?? null;
    $appointment->appointment_date = $data['appointment_date'];
    $appointment->appointment_time = $data['appointment_time'];
    $appointment->duration_minutes = $data['duration_minutes'] ?? 30;
    $appointment->consultation_type = $data['consultation_type'] ?? 'in_person';
    $appointment->notes = $data['notes'] ?? null;
    $appointment->symptoms = $data['symptoms'] ?? null;
    $appointment->total_fee = $data['total_fee'] ?? 0;

    if($appointment->book()) {
        echo json_encode([
            'success' => true,
            'message' => 'تم حجز الموعد بنجاح',
            'appointment_id' => $appointment->id
        ]);
    } else {
        throw new Exception('فشل في حجز الموعد - قد يكون الوقت محجوز بالفعل');
    }
}

function getMyAppointments($appointment) {
    $status = $_GET['status'] ?? null;
    $appointments = $appointment->getPatientAppointments($_SESSION['user_id'], $status);
    
    echo json_encode([
        'success' => true,
        'appointments' => $appointments
    ]);
}

function getDoctorAppointments($appointment) {
    if(!User::hasRole('doctor')) {
        throw new Exception('غير مصرح لك بالوصول لهذه البيانات');
    }

    $date = $_GET['date'] ?? null;
    $appointments = $appointment->getDoctorAppointments($_SESSION['user_id'], $date);
    
    echo json_encode([
        'success' => true,
        'appointments' => $appointments
    ]);
}

function getAvailableSlots($appointment) {
    $doctor_id = $_GET['doctor_id'] ?? null;
    $date = $_GET['date'] ?? null;

    if(!$doctor_id || !$date) {
        throw new Exception('معرف الطبيب والتاريخ مطلوبان');
    }

    $slots = $appointment->getAvailableTimeSlots($doctor_id, $date);
    
    echo json_encode([
        'success' => true,
        'available_slots' => $slots
    ]);
}

function updateAppointmentStatus($appointment) {
    if(!User::hasRole('doctor') && !User::hasRole('admin')) {
        throw new Exception('غير مصرح لك بتعديل حالة الموعد');
    }

    $data = json_decode(file_get_contents("php://input"), true);
    
    if(empty($data['appointment_id']) || empty($data['status'])) {
        throw new Exception('معرف الموعد والحالة مطلوبان');
    }

    $valid_statuses = ['pending', 'confirmed', 'cancelled', 'completed', 'no_show'];
    if(!in_array($data['status'], $valid_statuses)) {
        throw new Exception('حالة الموعد غير صحيحة');
    }

    if($appointment->updateStatus($data['appointment_id'], $data['status'])) {
        echo json_encode([
            'success' => true,
            'message' => 'تم تحديث حالة الموعد بنجاح'
        ]);
    } else {
        throw new Exception('فشل في تحديث حالة الموعد');
    }
}

function cancelAppointment($appointment) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(empty($data['appointment_id'])) {
        throw new Exception('معرف الموعد مطلوب');
    }

    $reason = $data['reason'] ?? null;

    if($appointment->cancel($data['appointment_id'], $reason)) {
        echo json_encode([
            'success' => true,
            'message' => 'تم إلغاء الموعد بنجاح'
        ]);
    } else {
        throw new Exception('فشل في إلغاء الموعد');
    }
}
?>