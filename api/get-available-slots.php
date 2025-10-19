<?php
/**
 * الحصول على الأوقات المتاحة
 * Get Available Time Slots API
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/appointments.php';

header('Content-Type: application/json');

try {
    $doctorId = $_GET['doctor_id'] ?? '';
    $date = $_GET['date'] ?? '';
    
    if (empty($doctorId) || empty($date)) {
        echo json_encode(['success' => false, 'message' => 'معرف الطبيب والتاريخ مطلوبان']);
        exit;
    }
    
    $appointments = new Appointments();
    $result = $appointments->getAvailableSlots($doctorId, $date);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Get Slots API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
}
?>
