<?php
/**
 * إدارة المدن
 * Cities API
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/cities.php';

header('Content-Type: application/json');

try {
    $cities = new Cities();
    $action = $_GET['action'] ?? 'all';
    
    switch ($action) {
        case 'all':
            // الحصول على جميع المدن
            $majorOnly = isset($_GET['major_only']) && $_GET['major_only'] == '1';
            $result = $cities->getAllCities($majorOnly);
            break;
            
        case 'by_region':
            // الحصول على المدن حسب المنطقة
            $region = $_GET['region'] ?? null;
            $result = $cities->getCitiesByRegion($region);
            break;
            
        case 'regions':
            // الحصول على جميع المناطق
            $result = $cities->getAllRegions();
            break;
            
        case 'search':
            // البحث عن مدن
            $query = $_GET['q'] ?? '';
            if (empty($query)) {
                echo json_encode(['success' => false, 'message' => 'يرجى إدخال كلمة البحث']);
                exit;
            }
            $result = $cities->searchCities($query);
            break;
            
        case 'details':
            // تفاصيل مدينة
            $cityId = $_GET['city_id'] ?? '';
            if (empty($cityId)) {
                echo json_encode(['success' => false, 'message' => 'معرف المدينة مطلوب']);
                exit;
            }
            $result = $cities->getCityDetails($cityId);
            break;
            
        case 'major_with_doctors':
            // المدن الرئيسية مع عدد الأطباء
            $result = $cities->getMajorCitiesWithDoctors();
            break;
            
        default:
            $result = ['success' => false, 'message' => 'إجراء غير صحيح'];
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Cities API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
}
?>
