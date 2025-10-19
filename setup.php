<?php
/**
 * Medical Appointment System Setup Script
 * سكريبت إعداد نظام المواعيد الطبية
 */

// إعدادات قاعدة البيانات
$db_config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'medical_appointments'
];

// إعدادات التطبيق
$app_config = [
    'app_name' => 'شفاء - نظام المواعيد الطبية',
    'app_url' => 'http://localhost',
    'admin_email' => 'admin@shifa.com'
];

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>إعداد نظام المواعيد الطبية</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css' rel='stylesheet'>
    <style>
        body { background-color: #f8f9fa; }
        .setup-card { max-width: 800px; margin: 2rem auto; }
        .step { margin-bottom: 2rem; }
        .step-number { 
            width: 40px; height: 40px; 
            background-color: #10B981; 
            color: white; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-weight: bold; 
            margin-left: 1rem; 
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='setup-card card shadow'>
            <div class='card-header bg-primary text-white text-center'>
                <h2><i class='bi bi-heart-pulse-fill'></i> إعداد نظام المواعيد الطبية</h2>
            </div>
            <div class='card-body'>";

// الخطوة 1: التحقق من متطلبات النظام
echo "<div class='step'>
    <div class='d-flex align-items-center mb-3'>
        <div class='step-number'>1</div>
        <h4>التحقق من متطلبات النظام</h4>
    </div>
    <div class='row'>";

// التحقق من PHP
$php_version = phpversion();
$php_ok = version_compare($php_version, '8.0.0', '>=');
echo "<div class='col-md-6 mb-2'>
    <div class='d-flex justify-content-between'>
        <span>إصدار PHP:</span>
        <span class='badge " . ($php_ok ? 'bg-success' : 'bg-danger') . "'>$php_version</span>
    </div>
</div>";

// التحقق من MySQL
$mysql_ok = extension_loaded('pdo_mysql');
echo "<div class='col-md-6 mb-2'>
    <div class='d-flex justify-content-between'>
        <span>MySQL PDO:</span>
        <span class='badge " . ($mysql_ok ? 'bg-success' : 'bg-danger') . "'>" . ($mysql_ok ? 'متوفر' : 'غير متوفر') . "</span>
    </div>
</div>";

// التحقق من الصلاحيات
$uploads_writable = is_writable('uploads') || mkdir('uploads', 0755, true);
$logs_writable = is_writable('logs') || mkdir('logs', 0755, true);

echo "<div class='col-md-6 mb-2'>
    <div class='d-flex justify-content-between'>
        <span>مجلد التحميلات:</span>
        <span class='badge " . ($uploads_writable ? 'bg-success' : 'bg-danger') . "'>" . ($uploads_writable ? 'قابل للكتابة' : 'غير قابل للكتابة') . "</span>
    </div>
</div>";

echo "<div class='col-md-6 mb-2'>
    <div class='d-flex justify-content-between'>
        <span>مجلد السجلات:</span>
        <span class='badge " . ($logs_writable ? 'bg-success' : 'bg-danger') . "'>" . ($logs_writable ? 'قابل للكتابة' : 'غير قابل للكتابة') . "</span>
    </div>
</div>";

$requirements_ok = $php_ok && $mysql_ok && $uploads_writable && $logs_writable;

if (!$requirements_ok) {
    echo "<div class='alert alert-danger mt-3'>
        <h5>خطأ في المتطلبات</h5>
        <p>يرجى التأكد من تثبيت جميع المتطلبات المطلوبة قبل المتابعة.</p>
    </div>";
} else {
    echo "<div class='alert alert-success mt-3'>
        <h5>جميع المتطلبات متوفرة</h5>
        <p>يمكنك المتابعة إلى الخطوة التالية.</p>
    </div>";
}

echo "</div></div>";

// الخطوة 2: إعداد قاعدة البيانات
if ($requirements_ok) {
    echo "<div class='step'>
        <div class='d-flex align-items-center mb-3'>
            <div class='step-number'>2</div>
            <h4>إعداد قاعدة البيانات</h4>
        </div>";

    try {
        // الاتصال بقاعدة البيانات
        $pdo = new PDO("mysql:host={$db_config['host']}", $db_config['username'], $db_config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // إنشاء قاعدة البيانات
        $pdo->exec("CREATE DATABASE IF NOT EXISTS {$db_config['database']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE {$db_config['database']}");

        // قراءة وتشغيل ملف SQL
        $sql_file = 'database/schema.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            $pdo->exec($sql);
            
            echo "<div class='alert alert-success'>
                <h5>تم إنشاء قاعدة البيانات بنجاح</h5>
                <p>تم إنشاء جميع الجداول والبيانات الأولية.</p>
            </div>";

            // إنشاء مستخدم مدير افتراضي
            $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(['مدير النظام', 'admin@shifa.com', $admin_password, 'admin', 1]);

            echo "<div class='alert alert-info'>
                <h5>تم إنشاء حساب المدير الافتراضي</h5>
                <p><strong>البريد الإلكتروني:</strong> admin@shifa.com</p>
                <p><strong>كلمة المرور:</strong> admin123</p>
                <p class='text-danger'>يرجى تغيير كلمة المرور بعد تسجيل الدخول الأول.</p>
            </div>";

        } else {
            echo "<div class='alert alert-warning'>
                <h5>ملف قاعدة البيانات غير موجود</h5>
                <p>يرجى التأكد من وجود ملف database/schema.sql</p>
            </div>";
        }

    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>
            <h5>خطأ في قاعدة البيانات</h5>
            <p>خطأ: " . $e->getMessage() . "</p>
        </div>";
    }

    echo "</div>";
}

// الخطوة 3: إعداد ملفات التكوين
if ($requirements_ok) {
    echo "<div class='step'>
        <div class='d-flex align-items-center mb-3'>
            <div class='step-number'>3</div>
            <h4>إعداد ملفات التكوين</h4>
        </div>";

    // تحديث ملف قاعدة البيانات
    $db_config_content = "<?php
/**
 * Database Configuration
 * إعدادات قاعدة البيانات
 */

class Database {
    private \$host = '{$db_config['host']}';
    private \$db_name = '{$db_config['database']}';
    private \$username = '{$db_config['username']}';
    private \$password = '{$db_config['password']}';
    private \$charset = 'utf8mb4';
    public \$conn;

    /**
     * Get database connection
     * الحصول على اتصال قاعدة البيانات
     */
    public function getConnection() {
        \$this->conn = null;
        
        try {
            \$dsn = \"mysql:host=\" . \$this->host . \";dbname=\" . \$this->db_name . \";charset=\" . \$this->charset;
            \$options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => \"SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci\"
            ];
            
            \$this->conn = new PDO(\$dsn, \$this->username, \$this->password, \$options);
        } catch(PDOException \$exception) {
            error_log(\"Connection error: \" . \$exception->getMessage());
            throw new Exception(\"Database connection failed\");
        }
        
        return \$this->conn;
    }
}
?>";

    if (file_put_contents('config/database.php', $db_config_content)) {
        echo "<div class='alert alert-success'>
            <h5>تم تحديث إعدادات قاعدة البيانات</h5>
            <p>تم حفظ إعدادات الاتصال بقاعدة البيانات.</p>
        </div>";
    } else {
        echo "<div class='alert alert-danger'>
            <h5>فشل في تحديث إعدادات قاعدة البيانات</h5>
            <p>يرجى التحقق من صلاحيات الكتابة في مجلد config/</p>
        </div>";
    }

    echo "</div>";
}

// الخطوة 4: اختبار النظام
if ($requirements_ok) {
    echo "<div class='step'>
        <div class='d-flex align-items-center mb-3'>
            <div class='step-number'>4</div>
            <h4>اختبار النظام</h4>
        </div>";

    echo "<div class='row'>
        <div class='col-md-6'>
            <div class='card'>
                <div class='card-body text-center'>
                    <i class='bi bi-person-check fs-1 text-primary mb-3'></i>
                    <h5>تسجيل الدخول</h5>
                    <p class='text-muted'>اختبر تسجيل الدخول بحساب المدير</p>
                    <a href='login.html' class='btn btn-primary'>تسجيل الدخول</a>
                </div>
            </div>
        </div>
        <div class='col-md-6'>
            <div class='card'>
                <div class='card-body text-center'>
                    <i class='bi bi-house fs-1 text-success mb-3'></i>
                    <h5>الصفحة الرئيسية</h5>
                    <p class='text-muted'>انتقل إلى الصفحة الرئيسية</p>
                    <a href='index.html' class='btn btn-success'>الصفحة الرئيسية</a>
                </div>
            </div>
        </div>
    </div>";

    echo "</div>";
}

// الخطوة 5: معلومات إضافية
echo "<div class='step'>
    <div class='d-flex align-items-center mb-3'>
        <div class='step-number'>5</div>
        <h4>معلومات إضافية</h4>
    </div>
    <div class='alert alert-info'>
        <h5>تم إكمال الإعداد بنجاح!</h5>
        <p>نظام المواعيد الطبية جاهز للاستخدام. إليك بعض المعلومات المهمة:</p>
        <ul class='mb-0'>
            <li><strong>حساب المدير:</strong> admin@shifa.com / admin123</li>
            <li><strong>الصفحة الرئيسية:</strong> index.html</li>
            <li><strong>تسجيل الدخول:</strong> login.html</li>
            <li><strong>التوثيق:</strong> README.md</li>
        </ul>
    </div>
</div>";

echo "</div>
        </div>
    </div>
</body>
</html>";
?>