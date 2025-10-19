<?php
/**
 * لوحة تحكم الطبيب
 * Doctor Dashboard
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/appointments.php';
require_once __DIR__ . '/../../includes/notifications.php';

// التحقق من تسجيل الدخول
$auth = new Auth();
if (!$auth->checkSession() || !hasRole(ROLE_DOCTOR)) {
    redirect(SITE_URL . '/login.html');
}

// الحصول على معرف الطبيب
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$doctor = $stmt->fetch();

// الحصول على مواعيد اليوم
$appointments = new Appointments();
$today = date('Y-m-d');
$todayAppointments = $appointments->getDoctorAppointments($doctor['id'], $today);
$pendingAppointments = $appointments->getDoctorAppointments($doctor['id'], null, 'pending');

// الحصول على الإشعارات
$notifications = new Notifications();
$unreadCount = $notifications->getUnreadCount($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - طبيب - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
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
        }
        .appointment-card {
            border-radius: 0.75rem;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 1rem;
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
                        <h6 class="text-white-50">مرحباً د.</h6>
                        <h5><?php echo htmlspecialchars($_SESSION['user_name']); ?></h5>
                    </div>
                </div>
                <nav class="nav flex-column px-3">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="bi bi-grid-1x2-fill me-2"></i> لوحة التحكم
                    </a>
                    <a class="nav-link" href="appointments.php">
                        <i class="bi bi-calendar-check-fill me-2"></i> المواعيد
                    </a>
                    <a class="nav-link" href="patients.php">
                        <i class="bi bi-people-fill me-2"></i> المرضى
                    </a>
                    <a class="nav-link" href="schedule.php">
                        <i class="bi bi-clock-fill me-2"></i> جدول العمل
                    </a>
                    <a class="nav-link" href="profile.php">
                        <i class="bi bi-person-fill me-2"></i> الملف الشخصي
                    </a>
                    <hr class="text-white">
                    <a class="nav-link" href="../../api/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i> تسجيل الخروج
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 px-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>لوحة التحكم</h2>
                    <button class="btn btn-light position-relative">
                        <i class="bi bi-bell-fill"></i>
                        <?php if ($unreadCount['count'] > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $unreadCount['count']; ?>
                            </span>
                        <?php endif; ?>
                    </button>
                </div>

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">مواعيد اليوم</h6>
                                <h2 class="mb-0 text-primary"><?php echo count($todayAppointments['appointments']); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">بانتظار التأكيد</h6>
                                <h2 class="mb-0 text-warning"><?php echo count($pendingAppointments['appointments']); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">الإشعارات</h6>
                                <h2 class="mb-0 text-info"><?php echo $unreadCount['count']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">التقييم</h6>
                                <h2 class="mb-0 text-success">
                                    <i class="bi bi-star-fill"></i> 4.8
                                </h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Today's Appointments -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">مواعيد اليوم</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($todayAppointments['appointments'])): ?>
                                    <div class="text-center py-5">
                                        <i class="bi bi-calendar-x fs-1 text-muted"></i>
                                        <p class="text-muted mt-3">لا توجد مواعيد اليوم</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($todayAppointments['appointments'] as $appointment): ?>
                                        <div class="appointment-card card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between">
                                                    <div class="d-flex gap-3">
                                                        <img src="<?php echo $appointment['patient_avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($appointment['patient_name']); ?>" 
                                                             class="rounded-circle" width="50" height="50" alt="Patient">
                                                        <div>
                                                            <h6 class="mb-1"><?php echo htmlspecialchars($appointment['patient_name']); ?></h6>
                                                            <p class="text-muted small mb-1">
                                                                <i class="bi bi-clock"></i> 
                                                                <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                                            </p>
                                                            <?php if ($appointment['reason']): ?>
                                                                <p class="small mb-0"><?php echo htmlspecialchars($appointment['reason']); ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <?php if ($appointment['status'] === 'pending'): ?>
                                                            <button class="btn btn-sm btn-success" onclick="confirmAppointment(<?php echo $appointment['id']; ?>)">
                                                                تأكيد
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Approvals -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">بانتظار التأكيد</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($pendingAppointments['appointments'])): ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-check-circle fs-1 text-success"></i>
                                        <p class="text-muted mt-2 small">لا توجد مواعيد بانتظار التأكيد</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach (array_slice($pendingAppointments['appointments'], 0, 5) as $appointment): ?>
                                        <div class="border-bottom pb-2 mb-2">
                                            <h6 class="mb-1 small"><?php echo htmlspecialchars($appointment['patient_name']); ?></h6>
                                            <p class="text-muted small mb-1">
                                                <?php echo date('Y-m-d h:i A', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time'])); ?>
                                            </p>
                                            <button class="btn btn-sm btn-outline-success" onclick="confirmAppointment(<?php echo $appointment['id']; ?>)">
                                                تأكيد
                                            </button>
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
        function confirmAppointment(id) {
            if (confirm('هل تريد تأكيد هذا الموعد؟')) {
                // يمكن إضافة طلب AJAX هنا
                alert('تم تأكيد الموعد');
                location.reload();
            }
        }
    </script>
</body>
</html>
