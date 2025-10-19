<?php
/**
 * Appointments API Endpoints
 * نقاط نهاية واجهة برمجة التطبيقات للمواعيد
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Appointment.php';
require_once '../classes/Doctor.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$database = new Database();
$db = $database->getConnection();
$appointment = new Appointment($db);
$doctor = new Doctor($db);

$action = $_GET['action'] ?? '';
$response = ['success' => false, 'message' => '', 'data' => null];

try {
    // Check if user is logged in for most actions
    if (!in_array($action, ['get_availability']) && !User::isLoggedIn()) {
        throw new Exception('يجب تسجيل الدخول أولاً');
    }

    switch ($action) {
        case 'book':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            session_start();
            
            // Validate required fields
            $required_fields = ['doctor_id', 'appointment_date', 'appointment_time'];
            foreach ($required_fields as $field) {
                if (empty($input[$field])) {
                    throw new Exception("الحقل {$field} مطلوب");
                }
            }
            
            // Set appointment properties
            $appointment->patient_id = $_SESSION['user_id'];
            $appointment->doctor_id = $input['doctor_id'];
            $appointment->appointment_date = $input['appointment_date'];
            $appointment->appointment_time = $input['appointment_time'];
            $appointment->duration_minutes = $input['duration_minutes'] ?? 30;
            $appointment->appointment_type = $input['appointment_type'] ?? 'consultation';
            $appointment->notes = $input['notes'] ?? '';
            $appointment->symptoms = $input['symptoms'] ?? '';
            $appointment->insurance_id = $input['insurance_id'] ?? null;
            $appointment->total_fee = $input['total_fee'];
            
            $result = $appointment->bookAppointment();
            $response = $result;
            break;

        case 'get_user_appointments':
            session_start();
            $user_id = $_SESSION['user_id'];
            $role = $_SESSION['user_role'];
            $status = $_GET['status'] ?? null;
            $limit = intval($_GET['limit'] ?? 20);
            $offset = intval($_GET['offset'] ?? 0);
            
            // For doctors, get doctor ID
            if ($role === 'doctor') {
                $query = "SELECT id FROM doctors WHERE user_id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":user_id", $user_id);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $doctor_data = $stmt->fetch(PDO::FETCH_ASSOC);
                    $user_id = $doctor_data['id'];
                }
            }
            
            $appointments = $appointment->getUserAppointments($user_id, $role, $status, $limit, $offset);
            
            $response['success'] = true;
            $response['data'] = $appointments;
            break;

        case 'get_appointment_details':
            $appointment_id = $_GET['id'] ?? '';
            
            if (empty($appointment_id)) {
                throw new Exception('معرف الموعد مطلوب');
            }
            
            $details = $appointment->getAppointmentDetails($appointment_id);
            
            if ($details) {
                session_start();
                // Check if user has permission to view this appointment
                if ($details['patient_id'] != $_SESSION['user_id'] && 
                    $details['doctor_user_id'] != $_SESSION['user_id'] &&
                    $_SESSION['user_role'] != 'admin') {
                    throw new Exception('غير مسموح لك بعرض هذا الموعد');
                }
                
                $response['success'] = true;
                $response['data'] = $details;
            } else {
                throw new Exception('الموعد غير موجود');
            }
            break;

        case 'update_status':
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                throw new Exception('Method not allowed');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $appointment_id = $input['appointment_id'] ?? '';
            $new_status = $input['status'] ?? '';
            $notes = $input['notes'] ?? null;
            
            if (empty($appointment_id) || empty($new_status)) {
                throw new Exception('معرف الموعد والحالة الجديدة مطلوبان');
            }
            
            session_start();
            
            // Check permissions (only doctors and admins can update status)
            if (!in_array($_SESSION['user_role'], ['doctor', 'admin'])) {
                throw new Exception('غير مسموح لك بتحديث حالة الموعد');
            }
            
            if ($appointment->updateAppointmentStatus($appointment_id, $new_status, $notes)) {
                $response['success'] = true;
                $response['message'] = 'تم تحديث حالة الموعد بنجاح';
            } else {
                throw new Exception('فشل في تحديث حالة الموعد');
            }
            break;

        case 'cancel':
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                throw new Exception('Method not allowed');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $appointment_id = $input['appointment_id'] ?? '';
            $reason = $input['reason'] ?? null;
            
            if (empty($appointment_id)) {
                throw new Exception('معرف الموعد مطلوب');
            }
            
            session_start();
            $result = $appointment->cancelAppointment($appointment_id, $_SESSION['user_id'], $reason);
            $response = $result;
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
                throw new Exception('لا يمكن حجز موعد في الماضي');
            }
            
            $availability = $doctor->getDoctorAvailability($doctor_id, $date);
            
            $response['success'] = true;
            $response['data'] = $availability;
            break;

        case 'get_stats':
            session_start();
            $user_id = $_SESSION['user_id'];
            $role = $_SESSION['user_role'];
            
            if ($role === 'doctor') {
                // Get doctor ID
                $query = "SELECT id FROM doctors WHERE user_id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":user_id", $user_id);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $doctor_data = $stmt->fetch(PDO::FETCH_ASSOC);
                    $stats = $appointment->getAppointmentStats(null, $doctor_data['id']);
                } else {
                    throw new Exception('ملف الطبيب غير موجود');
                }
            } else {
                $stats = $appointment->getAppointmentStats($user_id);
            }
            
            $response['success'] = true;
            $response['data'] = $stats;
            break;

        case 'reschedule':
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                throw new Exception('Method not allowed');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $appointment_id = $input['appointment_id'] ?? '';
            $new_date = $input['new_date'] ?? '';
            $new_time = $input['new_time'] ?? '';
            
            if (empty($appointment_id) || empty($new_date) || empty($new_time)) {
                throw new Exception('جميع البيانات مطلوبة لإعادة الجدولة');
            }
            
            session_start();
            
            // Get appointment details to check permissions
            $details = $appointment->getAppointmentDetails($appointment_id);
            
            if (!$details) {
                throw new Exception('الموعد غير موجود');
            }
            
            // Check permissions
            if ($details['patient_id'] != $_SESSION['user_id'] && 
                $details['doctor_user_id'] != $_SESSION['user_id'] &&
                $_SESSION['user_role'] != 'admin') {
                throw new Exception('غير مسموح لك بإعادة جدولة هذا الموعد');
            }
            
            // Check if new time slot is available
            $temp_appointment = new Appointment($db);
            $temp_appointment->doctor_id = $details['doctor_id'];
            $temp_appointment->appointment_date = $new_date;
            $temp_appointment->appointment_time = $new_time;
            
            if (!$temp_appointment->isTimeSlotAvailable()) {
                throw new Exception('الموعد الجديد غير متاح');
            }
            
            // Update appointment
            $query = "UPDATE appointments SET appointment_date = :new_date, appointment_time = :new_time 
                      WHERE id = :appointment_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":new_date", $new_date);
            $stmt->bindParam(":new_time", $new_time);
            $stmt->bindParam(":appointment_id", $appointment_id);
            
            if ($stmt->execute()) {
                // Send notification about rescheduling
                require_once '../classes/Notification.php';
                $notification = new Notification($db);
                
                $message = "تم تغيير موعدك مع د. {$details['doctor_name']} إلى {$new_date} الساعة {$new_time}";
                $notification->sendNotification($details['patient_id'], 'تغيير موعد', $message, 'appointment', $appointment_id);
                
                $response['success'] = true;
                $response['message'] = 'تم تغيير موعد الحجز بنجاح';
            } else {
                throw new Exception('فشل في تغيير موعد الحجز');
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
?>