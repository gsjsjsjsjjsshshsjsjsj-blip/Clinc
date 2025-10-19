<?php
/**
 * نظام إدارة المدن
 * Cities Management System
 */

require_once __DIR__ . '/../config/config.php';

class Cities {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * الحصول على جميع المدن
     * Get all cities
     */
    public function getAllCities($majorOnly = false) {
        try {
            $sql = "SELECT * FROM cities WHERE 1=1";
            
            if ($majorOnly) {
                $sql .= " AND is_major_city = 1";
            }
            
            $sql .= " ORDER BY name_ar";
            
            $stmt = $this->db->query($sql);
            return ['success' => true, 'cities' => $stmt->fetchAll()];
            
        } catch (Exception $e) {
            error_log("Get Cities Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ في جلب المدن'];
        }
    }
    
    /**
     * الحصول على المدن حسب المنطقة
     * Get cities by region
     */
    public function getCitiesByRegion($region = null) {
        try {
            if ($region) {
                $stmt = $this->db->prepare("
                    SELECT * FROM cities 
                    WHERE region_ar = ? OR region_en = ?
                    ORDER BY is_major_city DESC, name_ar
                ");
                $stmt->execute([$region, $region]);
            } else {
                $stmt = $this->db->query("
                    SELECT * FROM cities 
                    ORDER BY region_ar, is_major_city DESC, name_ar
                ");
            }
            
            return ['success' => true, 'cities' => $stmt->fetchAll()];
            
        } catch (Exception $e) {
            error_log("Get Cities by Region Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ في جلب المدن'];
        }
    }
    
    /**
     * الحصول على جميع المناطق
     * Get all regions
     */
    public function getAllRegions() {
        try {
            $stmt = $this->db->query("
                SELECT DISTINCT 
                    region_ar, 
                    region_en,
                    COUNT(*) as cities_count
                FROM cities 
                GROUP BY region_ar, region_en
                ORDER BY region_ar
            ");
            
            return ['success' => true, 'regions' => $stmt->fetchAll()];
            
        } catch (Exception $e) {
            error_log("Get Regions Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ في جلب المناطق'];
        }
    }
    
    /**
     * البحث عن مدن
     * Search cities
     */
    public function searchCities($query) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM cities 
                WHERE name_ar LIKE ? OR name_en LIKE ?
                ORDER BY is_major_city DESC, name_ar
                LIMIT 20
            ");
            $searchTerm = '%' . $query . '%';
            $stmt->execute([$searchTerm, $searchTerm]);
            
            return ['success' => true, 'cities' => $stmt->fetchAll()];
            
        } catch (Exception $e) {
            error_log("Search Cities Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ في البحث'];
        }
    }
    
    /**
     * الحصول على معلومات مدينة
     * Get city details
     */
    public function getCityDetails($cityId) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*,
                       (SELECT COUNT(*) FROM doctors WHERE city_id = c.id) as doctors_count
                FROM cities c
                WHERE c.id = ?
            ");
            $stmt->execute([$cityId]);
            $city = $stmt->fetch();
            
            if (!$city) {
                return ['success' => false, 'message' => 'المدينة غير موجودة'];
            }
            
            return ['success' => true, 'city' => $city];
            
        } catch (Exception $e) {
            error_log("Get City Details Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ في جلب معلومات المدينة'];
        }
    }
    
    /**
     * الحصول على المدن الرئيسية مع عدد الأطباء
     * Get major cities with doctor count
     */
    public function getMajorCitiesWithDoctors() {
        try {
            $stmt = $this->db->query("
                SELECT 
                    c.id,
                    c.name_ar,
                    c.name_en,
                    c.region_ar,
                    COUNT(d.id) as doctors_count
                FROM cities c
                LEFT JOIN doctors d ON c.id = d.city_id
                WHERE c.is_major_city = 1
                GROUP BY c.id
                ORDER BY doctors_count DESC, c.name_ar
            ");
            
            return ['success' => true, 'cities' => $stmt->fetchAll()];
            
        } catch (Exception $e) {
            error_log("Get Major Cities Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ في جلب المدن الرئيسية'];
        }
    }
}
?>
