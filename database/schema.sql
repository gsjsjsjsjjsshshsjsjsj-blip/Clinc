-- Medical Appointment System Database Schema
-- نظام المواعيد الطبية - مخطط قاعدة البيانات

CREATE DATABASE IF NOT EXISTS medical_appointments;
USE medical_appointments;

-- جدول المستخدمين (المرضى والأطباء والمديرين)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('male', 'female') NOT NULL,
    role ENUM('patient', 'doctor', 'admin') NOT NULL DEFAULT 'patient',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- جدول التخصصات الطبية
CREATE TABLE specializations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name_ar VARCHAR(255) NOT NULL,
    name_en VARCHAR(255) NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول الأطباء (معلومات إضافية للأطباء)
CREATE TABLE doctors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    specialization_id INT NOT NULL,
    license_number VARCHAR(100) UNIQUE NOT NULL,
    experience_years INT DEFAULT 0,
    consultation_fee DECIMAL(10,2) NOT NULL,
    bio TEXT,
    education TEXT,
    languages JSON,
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (specialization_id) REFERENCES specializations(id)
);

-- جدول العيادات/المستشفيات
CREATE TABLE clinics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(255),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول أوقات عمل الأطباء
CREATE TABLE doctor_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_id INT NOT NULL,
    clinic_id INT NOT NULL,
    day_of_week ENUM('saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    slot_duration INT DEFAULT 30, -- مدة الموعد بالدقائق
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (clinic_id) REFERENCES clinics(id)
);

-- جدول المواعيد
CREATE TABLE appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    clinic_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed', 'no_show') DEFAULT 'pending',
    consultation_type ENUM('in_person', 'video_call', 'phone_call') DEFAULT 'in_person',
    notes TEXT,
    patient_notes TEXT,
    doctor_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (clinic_id) REFERENCES clinics(id)
);

-- جدول الإشعارات
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('appointment_confirmed', 'appointment_cancelled', 'appointment_reminder', 'general', 'system') NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- جدول التقييمات والمراجعات
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_appointment_review (appointment_id)
);

-- جدول شركات التأمين
CREATE TABLE insurance_companies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    logo_url VARCHAR(500),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول تأمين المستخدمين
CREATE TABLE user_insurance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    insurance_company_id INT NOT NULL,
    policy_number VARCHAR(100) NOT NULL,
    is_primary BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (insurance_company_id) REFERENCES insurance_companies(id)
);

-- إدراج بيانات أولية
INSERT INTO specializations (name_ar, name_en, description, icon) VALUES
('طب الأسنان', 'Dentistry', 'تخصص طب وجراحة الأسنان', 'bi-tooth'),
('طب القلب', 'Cardiology', 'تخصص أمراض القلب والأوعية الدموية', 'bi-heart-pulse'),
('طب العيون', 'Ophthalmology', 'تخصص أمراض العيون والجراحات', 'bi-eye'),
('طب الأطفال', 'Pediatrics', 'تخصص رعاية الأطفال والرضع', 'bi-people'),
('طب النساء والولادة', 'Obstetrics & Gynecology', 'تخصص صحة المرأة والحمل', 'bi-gender-female'),
('طب الجلدية', 'Dermatology', 'تخصص أمراض الجلد والجلدية', 'bi-droplet'),
('طب الأعصاب', 'Neurology', 'تخصص أمراض الجهاز العصبي', 'bi-brain'),
('طب العظام', 'Orthopedics', 'تخصص جراحة العظام والمفاصل', 'bi-bone');

INSERT INTO insurance_companies (name, logo_url) VALUES
('بوبا العربية', 'https://example.com/bupa-logo.png'),
('التعاونية للتأمين', 'https://example.com/coop-logo.png'),
('ميدغلف', 'https://example.com/medgulf-logo.png'),
('أكسا للتأمين', 'https://example.com/axa-logo.png');

-- إنشاء فهرس لتحسين الأداء
CREATE INDEX idx_appointments_date ON appointments(appointment_date);
CREATE INDEX idx_appointments_doctor ON appointments(doctor_id);
CREATE INDEX idx_appointments_patient ON appointments(patient_id);
CREATE INDEX idx_appointments_status ON appointments(status);
CREATE INDEX idx_doctor_schedules_doctor ON doctor_schedules(doctor_id);
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_read ON notifications(is_read);