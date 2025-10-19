<?php
/**
 * Reports API
 * API التقارير والإحصائيات
 */

require_once '../config/config.php';
require_once '../classes/User.php';
require_once '../classes/Doctor.php';
require_once '../classes/Appointment.php';

// التحقق من المصادقة
check_auth();

// التحقق من صلاحية المدير
check_role(['admin']);

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'general':
            handleGeneralStatistics();
            break;
        case 'charts':
            handleCharts();
            break;
        case 'top-doctors':
            handleTopDoctors();
            break;
        case 'popular-specializations':
            handlePopularSpecializations();
            break;
        default:
            send_json_response(['error' => 'إجراء غير صحيح'], 400);
    }
} catch (Exception $e) {
    error_log("Reports API Error: " . $e->getMessage());
    send_json_response(['error' => 'حدث خطأ في الخادم'], 500);
}

function handleGeneralStatistics() {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    $db = (new Database())->getConnection();
    
    // إحصائيات المواعيد
    $appointmentsQuery = "SELECT 
        COUNT(*) as total_appointments,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
        SUM(CASE WHEN DATE(appointment_date) = CURDATE() THEN 1 ELSE 0 END) as today_appointments,
        SUM(CASE WHEN DATE(appointment_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) as tomorrow_appointments,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_appointments
        FROM appointments 
        WHERE appointment_date BETWEEN :start_date AND :end_date";
    
    $stmt = $db->prepare($appointmentsQuery);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $appointmentsStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // إحصائيات المستخدمين
    $usersQuery = "SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_users_this_month
        FROM users";
    
    $stmt = $db->prepare($usersQuery);
    $stmt->execute();
    $usersStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // إحصائيات الأطباء
    $doctorsQuery = "SELECT 
        COUNT(*) as total_doctors,
        SUM(CASE WHEN d.is_active = 1 THEN 1 ELSE 0 END) as active_doctors,
        COUNT(DISTINCT d.specialization_id) as specializations_count
        FROM doctors d
        JOIN users u ON d.user_id = u.id";
    
    $stmt = $db->prepare($doctorsQuery);
    $stmt->execute();
    $doctorsStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // الإيرادات
    $revenueQuery = "SELECT 
        COALESCE(SUM(d.consultation_fee), 0) as total_revenue
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        WHERE a.status = 'completed' 
        AND a.appointment_date BETWEEN :start_date AND :end_date";
    
    $stmt = $db->prepare($revenueQuery);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $revenueStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // التقييمات
    $reviewsQuery = "SELECT 
        AVG(rating) as average_rating,
        COUNT(*) as total_reviews
        FROM reviews r
        JOIN appointments a ON r.appointment_id = a.id
        WHERE a.appointment_date BETWEEN :start_date AND :end_date";
    
    $stmt = $db->prepare($reviewsQuery);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $reviewsStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // حساب المعدلات
    $completionRate = $appointmentsStats['total_appointments'] > 0 
        ? round(($appointmentsStats['completed_appointments'] / $appointmentsStats['total_appointments']) * 100, 1)
        : 0;
    
    $cancellationRate = $appointmentsStats['total_appointments'] > 0 
        ? round(($appointmentsStats['cancelled_appointments'] / $appointmentsStats['total_appointments']) * 100, 1)
        : 0;
    
    // حساب الاتجاهات (مقارنة بالفترة السابقة)
    $previousStartDate = date('Y-m-d', strtotime($startDate . ' -30 days'));
    $previousEndDate = date('Y-m-d', strtotime($endDate . ' -30 days'));
    
    $previousAppointmentsQuery = "SELECT COUNT(*) as count FROM appointments WHERE appointment_date BETWEEN :start_date AND :end_date";
    $stmt = $db->prepare($previousAppointmentsQuery);
    $stmt->bindParam(':start_date', $previousStartDate);
    $stmt->bindParam(':end_date', $previousEndDate);
    $stmt->execute();
    $previousAppointments = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $appointmentsTrend = $previousAppointments > 0 
        ? round((($appointmentsStats['total_appointments'] - $previousAppointments) / $previousAppointments) * 100, 1)
        : 0;
    
    $statistics = array_merge(
        $appointmentsStats,
        $usersStats,
        $doctorsStats,
        $revenueStats,
        $reviewsStats,
        [
            'completion_rate' => $completionRate,
            'cancellation_rate' => $cancellationRate,
            'appointments_trend' => $appointmentsTrend,
            'revenue_trend' => 0, // يمكن حسابها لاحقاً
            'patients_trend' => 0, // يمكن حسابها لاحقاً
            'doctors_trend' => 0   // يمكن حسابها لاحقاً
        ]
    );
    
    send_json_response(['success' => true, 'statistics' => $statistics]);
}

function handleCharts() {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    $db = (new Database())->getConnection();
    
    // المواعيد الشهرية
    $monthlyAppointmentsQuery = "SELECT 
        DATE_FORMAT(appointment_date, '%Y-%m') as month,
        COUNT(*) as count
        FROM appointments 
        WHERE appointment_date BETWEEN :start_date AND :end_date
        GROUP BY DATE_FORMAT(appointment_date, '%Y-%m')
        ORDER BY month";
    
    $stmt = $db->prepare($monthlyAppointmentsQuery);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $monthlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $monthlyAppointments = [
        'labels' => array_column($monthlyData, 'month'),
        'values' => array_column($monthlyData, 'count')
    ];
    
    // التخصصات
    $specializationsQuery = "SELECT 
        s.name,
        COUNT(a.id) as count
        FROM specializations s
        LEFT JOIN doctors d ON s.id = d.specialization_id
        LEFT JOIN appointments a ON d.id = a.doctor_id AND a.appointment_date BETWEEN :start_date AND :end_date
        GROUP BY s.id, s.name
        HAVING count > 0
        ORDER BY count DESC
        LIMIT 6";
    
    $stmt = $db->prepare($specializationsQuery);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $specializationsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $specializations = [
        'labels' => array_column($specializationsData, 'name'),
        'values' => array_column($specializationsData, 'count')
    ];
    
    // الإيرادات الشهرية
    $monthlyRevenueQuery = "SELECT 
        DATE_FORMAT(a.appointment_date, '%Y-%m') as month,
        SUM(d.consultation_fee) as revenue
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        WHERE a.status = 'completed' 
        AND a.appointment_date BETWEEN :start_date AND :end_date
        GROUP BY DATE_FORMAT(a.appointment_date, '%Y-%m')
        ORDER BY month";
    
    $stmt = $db->prepare($monthlyRevenueQuery);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $revenueData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $monthlyRevenue = [
        'labels' => array_column($revenueData, 'month'),
        'values' => array_column($revenueData, 'revenue')
    ];
    
    // حالة المواعيد
    $statusQuery = "SELECT 
        status,
        COUNT(*) as count
        FROM appointments 
        WHERE appointment_date BETWEEN :start_date AND :end_date
        GROUP BY status";
    
    $stmt = $db->prepare($statusQuery);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $statusData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $statusLabels = [];
    $statusValues = [];
    $statusTranslations = [
        'pending' => 'معلق',
        'confirmed' => 'مؤكد',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغي'
    ];
    
    foreach ($statusData as $status) {
        $statusLabels[] = $statusTranslations[$status['status']] ?? $status['status'];
        $statusValues[] = $status['count'];
    }
    
    $appointmentStatus = [
        'labels' => $statusLabels,
        'values' => $statusValues
    ];
    
    send_json_response([
        'success' => true,
        'charts' => [
            'monthly_appointments' => $monthlyAppointments,
            'specializations' => $specializations,
            'monthly_revenue' => $monthlyRevenue,
            'appointment_status' => $appointmentStatus
        ]
    ]);
}

function handleTopDoctors() {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    $db = (new Database())->getConnection();
    
    $query = "SELECT 
        d.id,
        u.full_name,
        s.name as specialization_name,
        COUNT(a.id) as appointments_count,
        AVG(r.rating) as average_rating
        FROM doctors d
        JOIN users u ON d.user_id = u.id
        JOIN specializations s ON d.specialization_id = s.id
        LEFT JOIN appointments a ON d.id = a.doctor_id AND a.appointment_date BETWEEN :start_date AND :end_date
        LEFT JOIN reviews r ON a.id = r.appointment_id
        GROUP BY d.id, u.full_name, s.name
        HAVING appointments_count > 0
        ORDER BY appointments_count DESC
        LIMIT 10";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    send_json_response(['success' => true, 'doctors' => $doctors]);
}

function handlePopularSpecializations() {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    $db = (new Database())->getConnection();
    
    $query = "SELECT 
        s.id,
        s.name,
        COUNT(a.id) as appointments_count,
        COUNT(DISTINCT d.id) as doctors_count
        FROM specializations s
        LEFT JOIN doctors d ON s.id = d.specialization_id
        LEFT JOIN appointments a ON d.id = a.doctor_id AND a.appointment_date BETWEEN :start_date AND :end_date
        GROUP BY s.id, s.name
        HAVING appointments_count > 0
        ORDER BY appointments_count DESC
        LIMIT 10";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $specializations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    send_json_response(['success' => true, 'specializations' => $specializations]);
}
?>