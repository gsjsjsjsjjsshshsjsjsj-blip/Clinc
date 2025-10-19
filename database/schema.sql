-- ===================================
-- نظام إدارة المواعيد الطبية
-- Medical Appointments Management System
-- ===================================

-- إنشاء قاعدة البيانات
CREATE DATABASE IF NOT EXISTS medical_appointments CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE medical_appointments;

-- ===================================
-- جدول المستخدمين (Users)
-- ===================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    role ENUM('admin', 'doctor', 'patient') NOT NULL DEFAULT 'patient',
    avatar VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    email_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(64) DEFAULT NULL,
    reset_token VARCHAR(64) DEFAULT NULL,
    reset_token_expiry DATETIME DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    login_attempts INT DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- جدول معلومات المرضى (Patients)
-- ===================================
CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    national_id VARCHAR(20) DEFAULT NULL,
    date_of_birth DATE DEFAULT NULL,
    gender ENUM('male', 'female') DEFAULT NULL,
    blood_type VARCHAR(5) DEFAULT NULL,
    insurance_company VARCHAR(255) DEFAULT NULL,
    insurance_number VARCHAR(100) DEFAULT NULL,
    medical_history TEXT DEFAULT NULL,
    allergies TEXT DEFAULT NULL,
    chronic_diseases TEXT DEFAULT NULL,
    emergency_contact_name VARCHAR(255) DEFAULT NULL,
    emergency_contact_phone VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- جدول التخصصات الطبية (Specialties)
-- ===================================
CREATE TABLE IF NOT EXISTS specialties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_ar VARCHAR(255) NOT NULL,
    name_en VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    icon VARCHAR(100) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- جدول معلومات الأطباء (Doctors)
-- ===================================
CREATE TABLE IF NOT EXISTS doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    specialty_id INT NOT NULL,
    license_number VARCHAR(100) NOT NULL UNIQUE,
    bio TEXT DEFAULT NULL,
    qualifications TEXT DEFAULT NULL,
    years_of_experience INT DEFAULT 0,
    consultation_fee DECIMAL(10, 2) DEFAULT 0.00,
    rating DECIMAL(3, 2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    clinic_name VARCHAR(255) DEFAULT NULL,
    clinic_address TEXT DEFAULT NULL,
    clinic_city VARCHAR(100) DEFAULT NULL,
    clinic_phone VARCHAR(20) DEFAULT NULL,
    latitude DECIMAL(10, 8) DEFAULT NULL,
    longitude DECIMAL(11, 8) DEFAULT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (specialty_id) REFERENCES specialties(id),
    UNIQUE KEY unique_user_id (user_id),
    INDEX idx_specialty (specialty_id),
    INDEX idx_city (clinic_city),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- جدول أوقات عمل الأطباء (Doctor Schedules)
-- ===================================
CREATE TABLE IF NOT EXISTS doctor_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '0=Sunday, 6=Saturday',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    slot_duration INT DEFAULT 30 COMMENT 'Duration in minutes',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    INDEX idx_doctor_day (doctor_id, day_of_week)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- جدول المواعيد (Appointments)
-- ===================================
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed', 'no_show') DEFAULT 'pending',
    consultation_type ENUM('in_person', 'video_call', 'phone_call') DEFAULT 'in_person',
    reason TEXT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    cancellation_reason TEXT DEFAULT NULL,
    cancelled_by INT DEFAULT NULL,
    cancelled_at DATETIME DEFAULT NULL,
    confirmed_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (cancelled_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_patient (patient_id),
    INDEX idx_doctor (doctor_id),
    INDEX idx_date (appointment_date),
    INDEX idx_status (status),
    INDEX idx_doctor_date (doctor_id, appointment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- جدول التقييمات والمراجعات (Reviews)
-- ===================================
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    doctor_id INT NOT NULL,
    patient_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT DEFAULT NULL,
    is_approved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    UNIQUE KEY unique_appointment_review (appointment_id),
    INDEX idx_doctor (doctor_id),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- جدول الإشعارات (Notifications)
-- ===================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('appointment', 'reminder', 'cancellation', 'system', 'review') DEFAULT 'system',
    related_id INT DEFAULT NULL COMMENT 'ID of related entity (appointment, review, etc.)',
    is_read TINYINT(1) DEFAULT 0,
    read_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- جدول سجل الأنشطة (Activity Log)
-- ===================================
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) DEFAULT NULL,
    entity_id INT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    details TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- إدراج بيانات تجريبية
-- Insert Sample Data
-- ===================================

-- إدراج التخصصات الطبية
INSERT INTO specialties (name_ar, name_en, description, icon) VALUES
('طب الأسنان', 'Dentistry', 'تشخيص وعلاج أمراض الفم والأسنان', 'bi-tooth'),
('طب العيون', 'Ophthalmology', 'تشخيص وعلاج أمراض العين', 'bi-eye'),
('طب الأطفال', 'Pediatrics', 'الرعاية الصحية للأطفال', 'bi-hospital'),
('طب القلب', 'Cardiology', 'تشخيص وعلاج أمراض القلب والأوعية الدموية', 'bi-heart-pulse'),
('الجلدية', 'Dermatology', 'تشخيص وعلاج أمراض الجلد', 'bi-bandaid'),
('العظام', 'Orthopedics', 'تشخيص وعلاج أمراض العظام والمفاصل', 'bi-activity'),
('النساء والولادة', 'Obstetrics and Gynecology', 'الرعاية الصحية للنساء والحوامل', 'bi-person'),
('الطب الباطني', 'Internal Medicine', 'تشخيص وعلاج الأمراض الباطنية', 'bi-clipboard-pulse'),
('الأنف والأذن والحنجرة', 'ENT', 'تشخيص وعلاج أمراض الأنف والأذن والحنجرة', 'bi-mic'),
('الطب النفسي', 'Psychiatry', 'تشخيص وعلاج الأمراض النفسية', 'bi-brain');

-- إدراج مستخدم مدير النظام
INSERT INTO users (email, password, full_name, phone, role, is_active, email_verified) VALUES
('admin@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'مدير النظام', '0500000000', 'admin', 1, 1);
-- كلمة المرور: Admin@123

-- إدراج مستخدم طبيب تجريبي
INSERT INTO users (email, password, full_name, phone, role, is_active, email_verified) VALUES
('doctor@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. أحمد محمد', '0501234567', 'doctor', 1, 1);
-- كلمة المرور: Doctor@123

-- إدراج بيانات الطبيب
INSERT INTO doctors (user_id, specialty_id, license_number, bio, years_of_experience, consultation_fee, rating, total_reviews, clinic_name, clinic_address, clinic_city, clinic_phone) VALUES
(2, 1, 'LIC-2024-001', 'طبيب أسنان متخصص مع خبرة 10 سنوات في جراحة الفم والأسنان', 10, 250.00, 4.8, 150, 'عيادة الابتسامة الساطعة', 'شارع التحلية، حي النزهة', 'الرياض', '0112345678');

-- إدراج أوقات عمل الطبيب (الأحد - الخميس)
INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, slot_duration) VALUES
(1, 0, '09:00:00', '13:00:00', 30), -- الأحد
(1, 0, '16:00:00', '20:00:00', 30),
(1, 1, '09:00:00', '13:00:00', 30), -- الاثنين
(1, 1, '16:00:00', '20:00:00', 30),
(1, 2, '09:00:00', '13:00:00', 30), -- الثلاثاء
(1, 2, '16:00:00', '20:00:00', 30),
(1, 3, '09:00:00', '13:00:00', 30), -- الأربعاء
(1, 3, '16:00:00', '20:00:00', 30),
(1, 4, '09:00:00', '13:00:00', 30); -- الخميس

-- إدراج مستخدم مريض تجريبي
INSERT INTO users (email, password, full_name, phone, role, is_active, email_verified) VALUES
('patient@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'خالد عبدالله', '0509876543', 'patient', 1, 1);
-- كلمة المرور: Patient@123

-- إدراج بيانات المريض
INSERT INTO patients (user_id, national_id, date_of_birth, gender, blood_type, insurance_company, emergency_contact_name, emergency_contact_phone) VALUES
(3, '1234567890', '1990-01-01', 'male', 'O+', 'بوبا العربية', 'فاطمة محمد', '0501111111');
