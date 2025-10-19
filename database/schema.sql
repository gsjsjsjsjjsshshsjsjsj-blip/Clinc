-- Medical Appointments System Database Schema
-- مخطط قاعدة بيانات نظام المواعيد الطبية

CREATE DATABASE IF NOT EXISTS medical_appointments CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE medical_appointments;

-- جدول المستخدمين (Users Table)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    date_of_birth DATE,
    gender ENUM('male', 'female') NOT NULL,
    national_id VARCHAR(20) UNIQUE,
    address TEXT,
    city VARCHAR(50),
    role ENUM('patient', 'doctor', 'admin') DEFAULT 'patient',
    profile_image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- جدول التخصصات الطبية (Medical Specialties Table)
CREATE TABLE specialties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_ar VARCHAR(100) NOT NULL,
    name_en VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول الأطباء (Doctors Table)
CREATE TABLE doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    specialty_id INT NOT NULL,
    license_number VARCHAR(50) UNIQUE NOT NULL,
    experience_years INT DEFAULT 0,
    consultation_fee DECIMAL(10,2) NOT NULL,
    bio TEXT,
    education TEXT,
    languages VARCHAR(255),
    clinic_name VARCHAR(100),
    clinic_address TEXT,
    clinic_phone VARCHAR(20),
    working_hours JSON,
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    is_verified BOOLEAN DEFAULT FALSE,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (specialty_id) REFERENCES specialties(id)
);

-- جدول شركات التأمين (Insurance Companies Table)
CREATE TABLE insurance_companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_ar VARCHAR(100) NOT NULL,
    name_en VARCHAR(100) NOT NULL,
    logo VARCHAR(255),
    contact_info JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول علاقة الأطباء بشركات التأمين (Doctor Insurance Relations)
CREATE TABLE doctor_insurance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    insurance_id INT NOT NULL,
    discount_percentage DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (insurance_id) REFERENCES insurance_companies(id),
    UNIQUE KEY unique_doctor_insurance (doctor_id, insurance_id)
);

-- جدول المواعيد (Appointments Table)
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    duration_minutes INT DEFAULT 30,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'pending',
    appointment_type ENUM('consultation', 'follow_up', 'emergency') DEFAULT 'consultation',
    notes TEXT,
    symptoms TEXT,
    insurance_id INT NULL,
    total_fee DECIMAL(10,2) NOT NULL,
    paid_amount DECIMAL(10,2) DEFAULT 0.00,
    payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
    payment_method ENUM('cash', 'card', 'insurance', 'online') NULL,
    confirmation_code VARCHAR(10) UNIQUE,
    reminder_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id),
    FOREIGN KEY (insurance_id) REFERENCES insurance_companies(id),
    INDEX idx_appointment_date_time (appointment_date, appointment_time),
    INDEX idx_doctor_date (doctor_id, appointment_date),
    INDEX idx_patient_appointments (patient_id, appointment_date)
);

-- جدول التقييمات (Reviews Table)
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT,
    is_anonymous BOOLEAN DEFAULT FALSE,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id),
    FOREIGN KEY (patient_id) REFERENCES users(id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id),
    UNIQUE KEY unique_appointment_review (appointment_id)
);

-- جدول الإشعارات (Notifications Table)
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('appointment', 'reminder', 'system', 'payment') NOT NULL,
    related_id INT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_notifications (user_id, is_read, created_at)
);

-- جدول جلسات المستخدمين (User Sessions Table)
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session_token (session_token),
    INDEX idx_expires_at (expires_at)
);

-- جدول إعدادات النظام (System Settings Table)
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- إدراج البيانات الأساسية (Initial Data)

-- التخصصات الطبية
INSERT INTO specialties (name_ar, name_en, description, icon) VALUES
('طب الأسرة', 'Family Medicine', 'طب الرعاية الصحية الأولية للعائلة', 'bi-house-heart'),
('طب الأطفال', 'Pediatrics', 'الرعاية الطبية للأطفال والمراهقين', 'bi-person-hearts'),
('طب النساء والولادة', 'Obstetrics & Gynecology', 'صحة المرأة والحمل والولادة', 'bi-gender-female'),
('طب القلب', 'Cardiology', 'تشخيص وعلاج أمراض القلب والأوعية الدموية', 'bi-heart-pulse'),
('طب الأسنان', 'Dentistry', 'صحة الفم والأسنان', 'bi-emoji-smile'),
('طب العيون', 'Ophthalmology', 'تشخيص وعلاج أمراض العين', 'bi-eye'),
('طب الأنف والأذن والحنجرة', 'ENT', 'أمراض الأذن والأنف والحنجرة', 'bi-soundwave'),
('طب الجلدية', 'Dermatology', 'أمراض الجلد والشعر والأظافر', 'bi-bandaid'),
('طب العظام', 'Orthopedics', 'أمراض وإصابات العظام والمفاصل', 'bi-bandaid-fill'),
('الطب النفسي', 'Psychiatry', 'الصحة النفسية والعقلية', 'bi-brain');

-- شركات التأمين
INSERT INTO insurance_companies (name_ar, name_en, logo) VALUES
('بوبا العربية', 'Bupa Arabia', 'bupa-logo.png'),
('التعاونية للتأمين', 'Tawuniya', 'tawuniya-logo.png'),
('ميدغلف', 'Medgulf', 'medgulf-logo.png'),
('الراجحي تكافل', 'Al Rajhi Takaful', 'rajhi-logo.png'),
('أليانز إس إف', 'Allianz SF', 'allianz-logo.png');

-- إعدادات النظام
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('site_name', 'شفاء - نظام المواعيد الطبية', 'اسم الموقع'),
('appointment_duration', '30', 'مدة الموعد الافتراضية بالدقائق'),
('booking_advance_days', '30', 'عدد الأيام المسموح بها لحجز موعد مسبق'),
('reminder_hours', '24', 'عدد الساعات قبل الموعد لإرسال التذكير'),
('max_daily_appointments', '20', 'الحد الأقصى للمواعيد اليومية للطبيب الواحد'),
('system_email', 'noreply@shifa-medical.com', 'البريد الإلكتروني للنظام'),
('support_phone', '+966500000000', 'رقم الدعم الفني');

-- إنشاء مستخدم مدير افتراضي
INSERT INTO users (first_name, last_name, email, phone, password_hash, gender, role, is_active, email_verified) VALUES
('مدير', 'النظام', 'admin@shifa-medical.com', '+966500000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'male', 'admin', TRUE, TRUE);