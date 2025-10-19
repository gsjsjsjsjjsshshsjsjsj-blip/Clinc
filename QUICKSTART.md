# دليل البدء السريع ⚡
## Quick Start Guide

---

## في 5 دقائق فقط! 🚀

### 1️⃣ قاعدة البيانات (دقيقة واحدة)

```bash
# افتح MySQL
mysql -u root -p

# أنشئ قاعدة البيانات
CREATE DATABASE medical_appointments CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# استورد المخطط
USE medical_appointments;
SOURCE database/schema.sql;

# أو استخدم phpMyAdmin
```

---

### 2️⃣ الإعدادات (30 ثانية)

افتح `config/database.php` وعدّل:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // اسم المستخدم
define('DB_PASS', '');            // كلمة المرور
define('DB_NAME', 'medical_appointments');
```

---

### 3️⃣ التشغيل (30 ثانية)

#### باستخدام XAMPP:
1. ضع المشروع في `C:\xampp\htdocs\`
2. شغّل Apache و MySQL
3. افتح: `http://localhost/medical-appointments/`

#### باستخدام PHP Built-in Server:
```bash
php -S localhost:8000
```
افتح: `http://localhost:8000/`

---

### 4️⃣ جرّب النظام! (3 دقائق)

#### سجل دخول كمريض:
```
البريد: patient@shifa.sa
كلمة المرور: Patient@123
```

#### سجل دخول كطبيب:
```
البريد: doctor@shifa.sa
كلمة المرور: Doctor@123
```

#### سجل دخول كمدير:
```
البريد: admin@shifa.sa
كلمة المرور: Admin@123
```

---

## ✅ قائمة التحقق السريعة

- [ ] قاعدة البيانات تعمل
- [ ] ملف config/database.php معدّل
- [ ] الصفحة الرئيسية تظهر
- [ ] يمكنك تسجيل الدخول

---

## 🎯 الخطوات التالية

1. **غيّر كلمات المرور الافتراضية**
2. **أضف أطباء جدد** من لوحة تحكم المدير
3. **خصّص التصميم** حسب احتياجاتك
4. **اقرأ الدليل الكامل** في `README.md`

---

## ⚠️ ملاحظات مهمة

- هذا النظام للتطوير والتجربة
- قبل النشر على الإنترنت، راجع `INSTALLATION.md`
- لا تستخدم الحسابات التجريبية في الإنتاج

---

## 💡 نصائح سريعة

### اختبر الاتصال بقاعدة البيانات:
```php
<?php
require_once 'config/database.php';
$db = Database::getInstance()->getConnection();
echo "✅ متصل!";
?>
```

### امسح ذاكرة التخزين المؤقت:
```
Ctrl + Shift + Delete (في المتصفح)
```

### شاهد أخطاء PHP:
```bash
tail -f /var/log/apache2/error.log  # Linux
# أو
tail -f C:\xampp\apache\logs\error.log  # Windows XAMPP
```

---

## 🆘 مشاكل شائعة

### لا تظهر الصفحة؟
- تحقق من تشغيل Apache
- تأكد من المسار الصحيح

### خطأ في قاعدة البيانات؟
- راجع اسم المستخدم وكلمة المرور
- تأكد من تشغيل MySQL

### CSS لا يعمل؟
- امسح ذاكرة المتصفح
- تحقق من مسار الملفات

---

## 📚 المزيد من المساعدة

- دليل التثبيت الكامل: `INSTALLATION.md`
- التوثيق الشامل: `README.md`
- الدعم: افتح Issue في GitHub

---

**🎉 استمتع باستخدام نظام شفاء!**
