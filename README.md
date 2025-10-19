# نظام المواعيد الطبية - شفاء
## Medical Appointment System - Shifa

نظام شامل لإدارة المواعيد الطبية يربط المرضى بالأطباء ويوفر تجربة حجز مواعيد سلسة وآمنة.

A comprehensive medical appointment management system that connects patients with doctors and provides a seamless and secure booking experience.

## المميزات الرئيسية / Key Features

### 🏥 للمرضى / For Patients
- **حجز المواعيد بسهولة** - Easy appointment booking
- **البحث عن الأطباء** - Doctor search and filtering
- **إدارة المواعيد** - Appointment management
- **الإشعارات الفورية** - Real-time notifications
- **الملف الشخصي** - Personal profile management

### 👨‍⚕️ للأطباء / For Doctors
- **إدارة الجدول الزمني** - Schedule management
- **عرض المواعيد** - View appointments
- **تحديث حالة المواعيد** - Update appointment status
- **الإحصائيات** - Statistics and analytics
- **الملف المهني** - Professional profile

### 👨‍💼 للإدارة / For Administrators
- **إدارة المستخدمين** - User management
- **إدارة الأطباء** - Doctor management
- **إدارة المواعيد** - Appointment management
- **التقارير والإحصائيات** - Reports and analytics
- **إدارة النظام** - System administration

## التقنيات المستخدمة / Technologies Used

### الواجهة الأمامية / Frontend
- **HTML5** - Semantic markup
- **CSS3** - Modern styling with Bootstrap 5
- **JavaScript (ES6+)** - Interactive functionality
- **Bootstrap 5** - Responsive design framework
- **Bootstrap Icons** - Icon library

### الواجهة الخلفية / Backend
- **PHP 8+** - Server-side scripting
- **MySQL 8+** - Database management
- **PDO** - Database abstraction layer
- **RESTful API** - API architecture

### الأمان / Security
- **Prepared Statements** - SQL injection prevention
- **Password Hashing** - Secure password storage
- **Input Validation** - Data sanitization
- **Session Management** - Secure authentication
- **CSRF Protection** - Cross-site request forgery prevention

## متطلبات النظام / System Requirements

### الخادم / Server
- **PHP 8.0+** - PHP version requirement
- **MySQL 8.0+** - Database version requirement
- **Apache/Nginx** - Web server
- **SSL Certificate** - HTTPS support (recommended)

### المتصفحات المدعومة / Supported Browsers
- **Chrome 90+**
- **Firefox 88+**
- **Safari 14+**
- **Edge 90+**

## التثبيت والإعداد / Installation & Setup

### 1. تحميل المشروع / Download Project
```bash
git clone https://github.com/yourusername/medical-appointment-system.git
cd medical-appointment-system
```

### 2. إعداد قاعدة البيانات / Database Setup
```sql
-- إنشاء قاعدة البيانات
CREATE DATABASE medical_appointments;
USE medical_appointments;

-- تشغيل ملف SQL
SOURCE database/schema.sql;
```

### 3. إعداد التكوين / Configuration Setup
```php
// تحديث ملف config/database.php
private $host = 'localhost';
private $db_name = 'medical_appointments';
private $username = 'your_username';
private $password = 'your_password';
```

### 4. إعداد الصلاحيات / Set Permissions
```bash
# إعطاء صلاحيات الكتابة للمجلدات
chmod 755 uploads/
chmod 755 logs/
```

### 5. تشغيل الخادم / Start Server
```bash
# باستخدام PHP built-in server
php -S localhost:8000

# أو باستخدام Apache/Nginx
# قم بتوجيه المجلد إلى web root
```

## هيكل المشروع / Project Structure

```
medical-appointment-system/
├── api/                    # API endpoints
│   ├── auth.php           # Authentication API
│   ├── doctors.php        # Doctors API
│   ├── appointments.php   # Appointments API
│   └── notifications.php  # Notifications API
├── classes/               # PHP classes
│   ├── User.php          # User management
│   ├── Doctor.php        # Doctor management
│   └── Appointment.php   # Appointment management
├── config/               # Configuration files
│   ├── database.php      # Database configuration
│   └── config.php        # Application configuration
├── database/             # Database files
│   └── schema.sql        # Database schema
├── js/                   # JavaScript files
│   └── app.js           # Main application JS
├── patient/              # Patient pages
│   ├── dashboard.html   # Patient dashboard
│   └── book-appointment.html
├── doctor/               # Doctor pages
│   └── dashboard.html   # Doctor dashboard
├── admin/                # Admin pages
│   └── dashboard.html   # Admin dashboard
├── uploads/              # File uploads
├── logs/                 # Log files
├── style.css            # Main stylesheet
└── README.md            # This file
```

## الاستخدام / Usage

### 1. تسجيل الدخول / Login
- انتقل إلى `/login.html`
- أدخل البريد الإلكتروني وكلمة المرور
- اختر نوع الحساب (مريض/طبيب/مدير)

### 2. حجز موعد / Book Appointment
- سجل الدخول كـ مريض
- اختر التخصص الطبي
- اختر الطبيب
- اختر التاريخ والوقت
- أكمل الحجز

### 3. إدارة المواعيد / Manage Appointments
- **للمرضى**: عرض وتعديل مواعيدهم
- **للأطباء**: إدارة جدولهم ومواعيدهم
- **للإدارة**: إدارة جميع المواعيد

## API Documentation / توثيق API

### Authentication / المصادقة
```http
POST /api/auth.php?action=login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password123"
}
```

### Get Doctors / الحصول على الأطباء
```http
GET /api/doctors.php?action=search&specialization_id=1&city=Riyadh
```

### Book Appointment / حجز موعد
```http
POST /api/appointments.php?action=book
Content-Type: application/json

{
    "doctor_id": 1,
    "clinic_id": 1,
    "appointment_date": "2024-01-15",
    "appointment_time": "10:00:00",
    "consultation_type": "in_person"
}
```

## الأمان / Security

### حماية البيانات / Data Protection
- تشفير كلمات المرور باستخدام `password_hash()`
- استخدام Prepared Statements لمنع SQL Injection
- تنظيف وتصحيح جميع المدخلات
- حماية من CSRF attacks

### إدارة الجلسات / Session Management
- جلسات آمنة مع timeout
- تسجيل خروج تلقائي
- حماية من session hijacking

## الاختبار / Testing

### اختبار الوحدة / Unit Testing
```bash
# تشغيل اختبارات PHP
phpunit tests/
```

### اختبار التكامل / Integration Testing
```bash
# اختبار API endpoints
curl -X POST http://localhost/api/auth.php?action=login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123"}'
```

## المساهمة / Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## الترخيص / License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## الدعم / Support

للحصول على الدعم أو الإبلاغ عن مشاكل:
For support or to report issues:

- **Email**: support@shifa.com
- **GitHub Issues**: [Create an issue](https://github.com/yourusername/medical-appointment-system/issues)

## التحديثات المستقبلية / Future Updates

### المميزات المخططة / Planned Features
- **تطبيق الهاتف المحمول** - Mobile application
- **نظام الدفع الإلكتروني** - Electronic payment system
- **التكامل مع أنظمة المستشفيات** - Hospital system integration
- **الذكاء الاصطناعي** - AI-powered recommendations
- **التقارير المتقدمة** - Advanced reporting

### التحسينات المخططة / Planned Improvements
- **تحسين الأداء** - Performance optimization
- **تحسين الأمان** - Security enhancements
- **تحسين تجربة المستخدم** - UX improvements
- **دعم متعدد اللغات** - Multi-language support

---

**تم تطوير هذا النظام بواسطة فريق شفاء**  
**Developed by the Shifa Team**

© 2024 Medical Appointment System. All rights reserved.