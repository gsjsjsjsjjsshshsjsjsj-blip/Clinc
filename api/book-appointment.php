<?php
/**
 * حجز موعد
 * Book Appointment API
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/appointments.php';

header('Content-Type: application/json');

// التحقق من تسجيل الدخول
$auth = new Auth();
if (!$auth->checkSession() || !hasRole(ROLE_PATIENT)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول كمريض']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير مسموحة']);
    exit;
}

try {
    // الحصول على معرف المريض
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id FROM patients WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $patient = $stmt->fetch();
    
    if (!$patient) {
        echo json_encode(['success' => false, 'message' => 'بيانات المريض غير موجودة']);
        exit;
    }
    
    $doctorId = $_POST['doctor_id'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $reason = $_POST['reason'] ?? null;
    $consultationType = $_POST['consultation_type'] ?? 'in_person';
    
    if (empty($doctorId) || empty($date) || empty($time)) {
        echo json_encode(['success' => false, 'message' => 'يرجى إدخال جميع الحقول المطلوبة']);
        exit;
    }
    
    $appointments = new Appointments();
    $result = $appointments->bookAppointment(
        $patient['id'],
        $doctorId,
        $date,
        $time,
        $reason,
        $consultationType
    );
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Book Appointment API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
}
?>
