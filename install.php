<?php
/**
 * Medical Appointment System - Installation Script
 * نظام المواعيد الطبية - سكريبت التثبيت
 */

// Check if already installed
if (file_exists('config/installed.lock')) {
    die('النظام مثبت بالفعل. لحذف التثبيت، احذف ملف config/installed.lock');
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تثبيت نظام شفاء</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: linear-gradient(135deg, #10B981, #059669); min-height: 100vh; }
        .install-card { background: white; border-radius: 1rem; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .step { padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; }
        .step.active { background: #f0fdfa; border: 2px solid #10B981; }
        .step.completed { background: #d1fae5; border: 2px solid #10B981; }
        .step.error { background: #fee2e2; border: 2px solid #ef4444; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="install-card p-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-activity text-primary" style="font-size: 3rem;"></i>
                        <h2 class="mt-3">تثبيت نظام شفاء</h2>
                        <p class="text-muted">نظام المواعيد الطبية المتكامل</p>
                    </div>

                    <?php
                    $step = 1;
                    $errors = [];
                    $success = true;

                    // Step 1: Check PHP version
                    echo '<div class="step ' . (version_compare(PHP_VERSION, '8.0.0', '>=') ? 'completed' : 'error') . '">';
                    echo '<h5><i class="bi bi-check-circle"></i> فحص إصدار PHP</h5>';
                    if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
                        echo '<p class="text-success">✓ PHP ' . PHP_VERSION . ' - متوافق</p>';
                    } else {
                        echo '<p class="text-danger">✗ PHP ' . PHP_VERSION . ' - يتطلب PHP 8.0 أو أحدث</p>';
                        $errors[] = 'إصدار PHP غير متوافق';
                        $success = false;
                    }
                    echo '</div>';

                    // Step 2: Check extensions
                    echo '<div class="step ' . (extension_loaded('pdo') && extension_loaded('pdo_mysql') ? 'completed' : 'error') . '">';
                    echo '<h5><i class="bi bi-check-circle"></i> فحص الإضافات المطلوبة</h5>';
                    $extensions = ['pdo', 'pdo_mysql', 'json', 'session'];
                    $all_extensions_ok = true;
                    foreach ($extensions as $ext) {
                        if (extension_loaded($ext)) {
                            echo '<p class="text-success">✓ ' . $ext . ' - متوفر</p>';
                        } else {
                            echo '<p class="text-danger">✗ ' . $ext . ' - غير متوفر</p>';
                            $all_extensions_ok = false;
                        }
                    }
                    if (!$all_extensions_ok) {
                        $errors[] = 'إضافات PHP مطلوبة غير متوفرة';
                        $success = false;
                    }
                    echo '</div>';

                    // Step 3: Check directory permissions
                    echo '<div class="step ' . (is_writable('.') ? 'completed' : 'error') . '">';
                    echo '<h5><i class="bi bi-check-circle"></i> فحص صلاحيات المجلدات</h5>';
                    if (is_writable('.')) {
                        echo '<p class="text-success">✓ المجلد الحالي قابل للكتابة</p>';
                    } else {
                        echo '<p class="text-danger">✗ المجلد الحالي غير قابل للكتابة</p>';
                        $errors[] = 'صلاحيات المجلد غير كافية';
                        $success = false;
                    }
                    echo '</div>';

                    // Step 4: Database connection test
                    if (isset($_POST['db_host'])) {
                        $db_host = $_POST['db_host'];
                        $db_name = $_POST['db_name'];
                        $db_user = $_POST['db_user'];
                        $db_pass = $_POST['db_pass'];
                        
                        echo '<div class="step ' . ($success ? 'active' : 'error') . '">';
                        echo '<h5><i class="bi bi-database"></i> اختبار اتصال قاعدة البيانات</h5>';
                        
                        try {
                            $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
                            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                            
                            // Create database if not exists
                            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                            $pdo->exec("USE `$db_name`");
                            
                            // Import schema
                            $schema = file_get_contents('database/schema.sql');
                            $pdo->exec($schema);
                            
                            echo '<p class="text-success">✓ تم الاتصال بقاعدة البيانات بنجاح</p>';
                            echo '<p class="text-success">✓ تم إنشاء قاعدة البيانات</p>';
                            echo '<p class="text-success">✓ تم استيراد هيكل قاعدة البيانات</p>';
                            
                            // Update config file
                            $config_content = file_get_contents('config/database.php');
                            $config_content = str_replace("'localhost'", "'$db_host'", $config_content);
                            $config_content = str_replace("'medical_appointments'", "'$db_name'", $config_content);
                            $config_content = str_replace("'root'", "'$db_user'", $config_content);
                            $config_content = str_replace("''", "'$db_pass'", $config_content);
                            
                            file_put_contents('config/database.php', $config_content);
                            
                            // Create admin user
                            $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, is_active, email_verified) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmt->execute(['مدير النظام', 'admin@shifa.com', $admin_password, 'admin', 1, 1]);
                            
                            // Create lock file
                            file_put_contents('config/installed.lock', date('Y-m-d H:i:s'));
                            
                            echo '<div class="alert alert-success mt-3">';
                            echo '<h6>تم التثبيت بنجاح!</h6>';
                            echo '<p>بيانات تسجيل الدخول الافتراضية:</p>';
                            echo '<p><strong>البريد الإلكتروني:</strong> admin@shifa.com</p>';
                            echo '<p><strong>كلمة المرور:</strong> admin123</p>';
                            echo '<p class="text-warning"><strong>تأكد من تغيير كلمة المرور بعد تسجيل الدخول الأول!</strong></p>';
                            echo '<a href="index.html" class="btn btn-primary">الذهاب للصفحة الرئيسية</a>';
                            echo '</div>';
                            
                        } catch (PDOException $e) {
                            echo '<p class="text-danger">✗ خطأ في الاتصال: ' . $e->getMessage() . '</p>';
                            $errors[] = 'فشل في الاتصال بقاعدة البيانات';
                            $success = false;
                        }
                        echo '</div>';
                    }

                    if (!$success && !isset($_POST['db_host'])) {
                        echo '<div class="alert alert-danger">';
                        echo '<h6>يوجد أخطاء يجب إصلاحها قبل المتابعة:</h6>';
                        echo '<ul>';
                        foreach ($errors as $error) {
                            echo '<li>' . $error . '</li>';
                        }
                        echo '</ul>';
                        echo '</div>';
                    }

                    if ($success && !isset($_POST['db_host'])) {
                        echo '<div class="step active">';
                        echo '<h5><i class="bi bi-database"></i> إعداد قاعدة البيانات</h5>';
                        echo '<form method="POST">';
                        echo '<div class="row">';
                        echo '<div class="col-md-6 mb-3">';
                        echo '<label class="form-label">خادم قاعدة البيانات</label>';
                        echo '<input type="text" class="form-control" name="db_host" value="localhost" required>';
                        echo '</div>';
                        echo '<div class="col-md-6 mb-3">';
                        echo '<label class="form-label">اسم قاعدة البيانات</label>';
                        echo '<input type="text" class="form-control" name="db_name" value="medical_appointments" required>';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="row">';
                        echo '<div class="col-md-6 mb-3">';
                        echo '<label class="form-label">اسم المستخدم</label>';
                        echo '<input type="text" class="form-control" name="db_user" value="root" required>';
                        echo '</div>';
                        echo '<div class="col-md-6 mb-3">';
                        echo '<label class="form-label">كلمة المرور</label>';
                        echo '<input type="password" class="form-control" name="db_pass">';
                        echo '</div>';
                        echo '</div>';
                        echo '<button type="submit" class="btn btn-primary">تثبيت النظام</button>';
                        echo '</form>';
                        echo '</div>';
                    }
                    ?>

                </div>
            </div>
        </div>
    </div>
</body>
</html>