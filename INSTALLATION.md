# دليل التثبيت والتشغيل
## Installation and Setup Guide

---

## 📋 المتطلبات الأساسية

قبل البدء، تأكد من توفر:

### البرامج المطلوبة:
- ✅ **PHP 7.4** أو أحدث
- ✅ **MySQL 5.7** أو **MariaDB 10.3** أو أحدث
- ✅ **Apache** أو **Nginx** (أو خادم PHP المدمج للتطوير)
- ✅ **Composer** (اختياري)

### امتدادات PHP المطلوبة:
```bash
- php-pdo
- php-mysqli
- php-mbstring
- php-openssl
- php-json
- php-session
```

---

## 🚀 خطوات التثبيت

### الخطوة 1: تنزيل المشروع

#### باستخدام Git:
```bash
git clone https://github.com/yourusername/medical-appointments.git
cd medical-appointments
```

#### أو تنزيل مباشر:
قم بتنزيل ملف ZIP من GitHub وفك الضغط

---

### الخطوة 2: إعداد قاعدة البيانات

#### أ. إنشاء قاعدة البيانات

افتح phpMyAdmin أو MySQL Terminal وأنشئ قاعدة البيانات:

```sql
CREATE DATABASE medical_appointments 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;
```

#### ب. استيراد المخطط

##### باستخدام MySQL Terminal:
```bash
mysql -u root -p medical_appointments < database/schema.sql
```

##### باستخدام phpMyAdmin:
1. افتح phpMyAdmin
2. اختر قاعدة البيانات `medical_appointments`
3. اذهب إلى تبويب "Import"
4. اختر ملف `database/schema.sql`
5. اضغط "Go"

---

### الخطوة 3: تكوين الإعدادات

#### عدّل ملف `config/database.php`:

```php
<?php
// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');      // عنوان الخادم
define('DB_USER', 'root');           // اسم المستخدم
define('DB_PASS', '');               // كلمة المرور
define('DB_NAME', 'medical_appointments');  // اسم قاعدة البيانات
define('DB_CHARSET', 'utf8mb4');
?>
```

#### عدّل ملف `config/config.php` (اختياري):

```php
<?php
// عنوان الموقع
define('SITE_URL', 'http://localhost/medical-appointments');

// المنطقة الزمنية
define('TIMEZONE', 'Asia/Riyadh');

// الإشعارات
define('ENABLE_EMAIL_NOTIFICATIONS', false);  // غير مفعل افتراضياً
define('ENABLE_SMS_NOTIFICATIONS', false);
?>
```

---

### الخطوة 4: تعيين الصلاحيات

#### على Linux/Mac:
```bash
# تعيين صلاحيات المجلدات
chmod 755 -R assets/
chmod 755 -R uploads/
chmod 755 -R config/

# تعيين الملكية (اختياري)
chown -R www-data:www-data /path/to/medical-appointments
```

#### على Windows:
لا حاجة لتعيين صلاحيات خاصة

---

### الخطوة 5: تشغيل المشروع

#### أ. باستخدام XAMPP/WAMP/MAMP:

1. **انقل المشروع** إلى مجلد `htdocs` (XAMPP) أو `www` (WAMP)
   ```
   C:\xampp\htdocs\medical-appointments\
   ```

2. **شغّل Apache و MySQL** من لوحة تحكم XAMPP

3. **افتح المتصفح** على:
   ```
   http://localhost/medical-appointments/
   ```

#### ب. باستخدام خادم PHP المدمج (للتطوير فقط):

```bash
# في مجلد المشروع
php -S localhost:8000
```

ثم افتح المتصفح على:
```
http://localhost:8000
```

#### ج. باستخدام Docker (اختياري):

إذا كان لديك Docker:

```bash
docker-compose up -d
```

---

## 🧪 اختبار التثبيت

### 1. اختبر الاتصال بقاعدة البيانات

أنشئ ملف `test-db.php` في المجلد الرئيسي:

```php
<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "✅ الاتصال بقاعدة البيانات ناجح!";
} catch (Exception $e) {
    echo "❌ فشل الاتصال: " . $e->getMessage();
}
?>
```

افتح المتصفح على:
```
http://localhost/medical-appointments/test-db.php
```

### 2. جرّب الحسابات التجريبية

#### مدير النظام:
- **البريد**: `admin@shifa.sa`
- **كلمة المرور**: `Admin@123`

#### طبيب:
- **البريد**: `doctor@shifa.sa`
- **كلمة المرور**: `Doctor@123`

#### مريض:
- **البريد**: `patient@shifa.sa`
- **كلمة المرور**: `Patient@123`

---

## 🔧 حل المشاكل الشائعة

### المشكلة 1: خطأ في الاتصال بقاعدة البيانات

**الخطأ**: `Connection failed: Access denied`

**الحل**:
- تحقق من اسم المستخدم وكلمة المرور في `config/database.php`
- تأكد من أن خادم MySQL يعمل
- تحقق من أن قاعدة البيانات موجودة

### المشكلة 2: الصفحة فارغة أو خطأ 500

**الحل**:
```bash
# فعّل عرض الأخطاء مؤقتاً
# أضف في أول ملف config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### المشكلة 3: CSS لا يعمل

**الحل**:
- تحقق من مسار ملفات CSS في HTML
- تأكد من أن مجلد `assets` في المكان الصحيح
- امسح ذاكرة التخزين المؤقت للمتصفح (Ctrl+Shift+Delete)

### المشكلة 4: الجلسة لا تعمل

**الحل**:
```bash
# تحقق من مجلد الجلسات
php -i | grep session.save_path

# أنشئ المجلد إذا لم يكن موجوداً
mkdir -p /tmp/sessions
chmod 777 /tmp/sessions
```

---

## 📊 التحقق من نجاح التثبيت

قائمة التحقق:

- [ ] قاعدة البيانات تم إنشاؤها بنجاح
- [ ] الجداول موجودة (users, doctors, patients, appointments, etc.)
- [ ] الصفحة الرئيسية تظهر بشكل صحيح
- [ ] يمكن تسجيل الدخول بالحسابات التجريبية
- [ ] لوحات التحكم تعمل بشكل صحيح
- [ ] الإشعارات تظهر
- [ ] يمكن حجز موعد

---

## 🔒 إعدادات الأمان للإنتاج

قبل نشر المشروع على الإنترنت:

### 1. غيّر كلمات المرور التجريبية:

```sql
-- غيّر كلمة مرور المدير
UPDATE users 
SET password = 'new_hashed_password' 
WHERE email = 'admin@shifa.sa';
```

### 2. عطّل عرض الأخطاء:

```php
// في config/config.php
error_reporting(0);
ini_set('display_errors', 0);
define('ENVIRONMENT', 'production');
```

### 3. استخدم HTTPS:

تأكد من تشغيل الموقع على HTTPS في الإنتاج

### 4. احمِ ملفات التكوين:

```apache
# في .htaccess
<FilesMatch "^(config|database)\.php$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### 5. احدّث إعدادات قاعدة البيانات:

- استخدم مستخدم قاعدة بيانات منفصل (غير root)
- منح الصلاحيات المطلوبة فقط

---

## 📧 الدعم

إذا واجهت مشكلة:

1. راجع قسم [حل المشاكل](#-حل-المشاكل-الشائعة)
2. افتح Issue في GitHub
3. تواصل عبر البريد الإلكتروني

---

## 🎉 تهانينا!

تم تثبيت نظام شفاء لإدارة المواعيد الطبية بنجاح! 🚀

الخطوات التالية:
- أضف أطباء جدد
- خصّص التصميم حسب احتياجاتك
- فعّل نظام الإشعارات عبر البريد
- راجع التوثيق الكامل في README.md
