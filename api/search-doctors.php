<?php
/**
 * البحث عن الأطباء
 * Search Doctors API
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/doctors.php';

header('Content-Type: application/json');

try {
    $filters = [
        'specialty_id' => $_GET['specialty'] ?? null,
        'city' => $_GET['location'] ?? null,
        'name' => $_GET['doctor_name'] ?? null,
        'min_rating' => $_GET['min_rating'] ?? null,
        'max_fee' => $_GET['max_fee'] ?? null,
        'sort' => $_GET['sort'] ?? 'rating',
        'limit' => $_GET['limit'] ?? 20,
        'offset' => $_GET['offset'] ?? 0
    ];
    
    $doctors = new Doctors();
    $result = $doctors->searchDoctors($filters);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Search Doctors API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
}
?>
