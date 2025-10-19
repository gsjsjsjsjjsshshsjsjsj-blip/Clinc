<?php
/**
 * لوحة تحكم المريض
 * Patient Dashboard
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/appointments.php';
require_once __DIR__ . '/../../includes/notifications.php';

// التحقق من تسجيل الدخول
$auth = new Auth();
if (!$auth->checkSession() || !hasRole(ROLE_PATIENT)) {
    redirect(SITE_URL . '/login.html');
}

// الحصول على معرف المريض
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patient = $stmt->fetch();

// الحصول على المواعيد
$appointments = new Appointments();
$upcomingAppointments = $appointments->getPatientAppointments($patient['id'], null, true);
$allAppointments = $appointments->getPatientAppointments($patient['id']);

// الحصول على الإشعارات
$notifications = new Notifications();
$unreadCount = $notifications->getUnreadCount($_SESSION['user_id']);
$recentNotifications = $notifications->getUserNotifications($_SESSION['user_id'], false, 5);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 1rem 1.5rem;
            margin: 0.25rem 0;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }
        .stat-card {
            border-radius: 1rem;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .appointment-card {
            border-radius: 0.75rem;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 1rem;
        }
        .badge-status {
            padding: 0.5rem 1rem;
            border-radius: 50px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-4">
                    <h3 class="mb-4"><i class="bi bi-activity"></i> شفاء</h3>
                    <div class="mb-4">
                        <h6 class="text-white-50">مرحباً</h6>
                        <h5><?php echo htmlspecialchars($_SESSION['user_name']); ?></h5>
                    </div>
                </div>
                <nav class="nav flex-column px-3">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="bi bi-grid-1x2-fill me-2"></i> لوحة التحكم
                    </a>
                    <a class="nav-link" href="appointments.php">
                        <i class="bi bi-calendar-check-fill me-2"></i> مواعيدي
                    </a>
                    <a class="nav-link" href="book-appointment.php">
                        <i class="bi bi-plus-circle-fill me-2"></i> حجز موعد جديد
                    </a>
                    <a class="nav-link" href="profile.php">
                        <i class="bi bi-person-fill me-2"></i> الملف الشخصي
                    </a>
                    <a class="nav-link" href="medical-records.php">
                        <i class="bi bi-file-medical-fill me-2"></i> السجل الطبي
                    </a>
                    <hr class="text-white">
                    <a class="nav-link" href="../../api/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i> تسجيل الخروج
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 px-4 py-4">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>لوحة التحكم</h2>
                    <div class="position-relative">
                        <button class="btn btn-light position-relative" onclick="toggleNotifications()">
                            <i class="bi bi-bell-fill"></i>
                            <?php if ($unreadCount['count'] > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $unreadCount['count']; ?>
                                </span>
                            <?php endif; ?>
                        </button>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">المواعيد القادمة</h6>
                                        <h2 class="mb-0"><?php echo count($upcomingAppointments['appointments']); ?></h2>
                                    </div>
                                    <div class="fs-1 text-primary">
                                        <i class="bi bi-calendar-event"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">إجمالي المواعيد</h6>
                                        <h2 class="mb-0"><?php echo count($allAppointments['appointments']); ?></h2>
                                    </div>
                                    <div class="fs-1 text-success">
                                        <i class="bi bi-check-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">الإشعارات الجديدة</h6>
                                        <h2 class="mb-0"><?php echo $unreadCount['count']; ?></h2>
                                    </div>
                                    <div class="fs-1 text-warning">
                                        <i class="bi bi-bell"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Appointments -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">المواعيد القادمة</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($upcomingAppointments['appointments'])): ?>
                                    <div class="text-center py-5">
                                        <i class="bi bi-calendar-x fs-1 text-muted"></i>
                                        <p class="text-muted mt-3">لا توجد مواعيد قادمة</p>
                                        <a href="book-appointment.php" class="btn btn-primary">احجز موعداً الآن</a>
                                    </div>
                                <?php else: ?>
                                    <?php foreach (array_slice($upcomingAppointments['appointments'], 0, 3) as $appointment): ?>
                                        <div class="appointment-card card mb-3">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="d-flex gap-3">
                                                        <img src="<?php echo $appointment['doctor_avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($appointment['doctor_name']); ?>" 
                                                             class="rounded-circle" width="60" height="60" alt="Doctor">
                                                        <div>
                                                            <h6 class="mb-1"><?php echo htmlspecialchars($appointment['doctor_name']); ?></h6>
                                                            <p class="text-muted mb-2 small"><?php echo htmlspecialchars($appointment['specialty_name']); ?></p>
                                                            <div class="small">
                                                                <i class="bi bi-calendar me-1"></i>
                                                                <?php echo date('Y-m-d', strtotime($appointment['appointment_date'])); ?>
                                                                <i class="bi bi-clock me-1 ms-3"></i>
                                                                <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <span class="badge badge-status bg-<?php 
                                                        echo $appointment['status'] === 'confirmed' ? 'success' : 
                                                            ($appointment['status'] === 'pending' ? 'warning' : 'secondary'); 
                                                    ?>">
                                                        <?php 
                                                            $statusText = [
                                                                'pending' => 'بانتظار التأكيد',
                                                                'confirmed' => 'مؤكد',
                                                                'cancelled' => 'ملغي',
                                                                'completed' => 'مكتمل'
                                                            ];
                                                            echo $statusText[$appointment['status']]; 
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="text-center mt-3">
                                        <a href="appointments.php" class="btn btn-outline-primary">عرض جميع المواعيد</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Notifications -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">الإشعارات الأخيرة</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recentNotifications['notifications'])): ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-bell-slash fs-1 text-muted"></i>
                                        <p class="text-muted mt-2 small">لا توجد إشعارات</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recentNotifications['notifications'] as $notification): ?>
                                        <div class="border-bottom pb-3 mb-3">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <h6 class="mb-1 small"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                                <?php if (!$notification['is_read']): ?>
                                                    <span class="badge bg-primary rounded-pill">جديد</span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-muted small mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                            <small class="text-muted"><?php echo date('Y-m-d h:i A', strtotime($notification['created_at'])); ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleNotifications() {
            // يمكن إضافة منطق لعرض الإشعارات في نافذة منبثقة
            alert('عرض الإشعارات');
        }
    </script>
</body>
</html>
