-- Medical Appointment System Database Schema
-- نظام المواعيد الطبية - هيكل قاعدة البيانات

CREATE DATABASE IF NOT EXISTS medical_appointments CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE medical_appointments;

-- Users table (المستخدمون)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('male', 'female') NOT NULL,
    role ENUM('patient', 'doctor', 'admin') NOT NULL DEFAULT 'patient',
    profile_image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_active (is_active)
);

-- Specializations table (التخصصات الطبية)
CREATE TABLE specializations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name_ar VARCHAR(255) NOT NULL,
    name_en VARCHAR(255) NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Doctors table (الأطباء)
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
    working_hours JSON,
    is_available BOOLEAN DEFAULT TRUE,
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (specialization_id) REFERENCES specializations(id) ON DELETE RESTRICT,
    INDEX idx_specialization (specialization_id),
    INDEX idx_available (is_available),
    INDEX idx_rating (rating)
);

-- Clinics table (العيادات)
CREATE TABLE clinics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(255),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    facilities JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Doctor clinics (علاقة الأطباء بالعيادات)
CREATE TABLE doctor_clinics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_id INT NOT NULL,
    clinic_id INT NOT NULL,
    consultation_fee DECIMAL(10,2) NOT NULL,
    working_days JSON,
    working_hours JSON,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE,
    UNIQUE KEY unique_doctor_clinic (doctor_id, clinic_id)
);

-- Appointments table (المواعيد)
CREATE TABLE appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    clinic_id INT,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    duration_minutes INT DEFAULT 30,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed', 'no_show') DEFAULT 'pending',
    consultation_type ENUM('in_person', 'video_call', 'phone_call') DEFAULT 'in_person',
    notes TEXT,
    symptoms TEXT,
    diagnosis TEXT,
    prescription TEXT,
    total_fee DECIMAL(10,2),
    payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE SET NULL,
    INDEX idx_patient (patient_id),
    INDEX idx_doctor (doctor_id),
    INDEX idx_date (appointment_date),
    INDEX idx_status (status)
);

-- Reviews table (التقييمات)
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_id INT,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    is_anonymous BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    UNIQUE KEY unique_patient_doctor_review (patient_id, doctor_id, appointment_id),
    INDEX idx_doctor_rating (doctor_id, rating)
);

-- Notifications table (الإشعارات)
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    action_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, is_read),
    INDEX idx_created_at (created_at)
);

-- Insurance companies table (شركات التأمين)
CREATE TABLE insurance_companies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    logo VARCHAR(255),
    contact_phone VARCHAR(20),
    contact_email VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Patient insurance (تأمين المرضى)
CREATE TABLE patient_insurance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    insurance_company_id INT NOT NULL,
    policy_number VARCHAR(100),
    coverage_percentage DECIMAL(5,2) DEFAULT 0.00,
    is_primary BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (insurance_company_id) REFERENCES insurance_companies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_patient_insurance (patient_id, insurance_company_id)
);

-- Insert sample data
INSERT INTO specializations (name_ar, name_en, description, icon) VALUES
('طب الأسنان', 'Dentistry', 'تخصص في علاج أمراض الأسنان واللثة', 'tooth'),
('طب القلب', 'Cardiology', 'تخصص في علاج أمراض القلب والأوعية الدموية', 'heart'),
('طب العيون', 'Ophthalmology', 'تخصص في علاج أمراض العيون', 'eye'),
('طب الأطفال', 'Pediatrics', 'تخصص في علاج الأطفال', 'baby'),
('طب النساء والولادة', 'Gynecology', 'تخصص في صحة المرأة والولادة', 'female'),
('الطب الباطني', 'Internal Medicine', 'تخصص في تشخيص وعلاج الأمراض الداخلية', 'stethoscope'),
('الجراحة العامة', 'General Surgery', 'تخصص في العمليات الجراحية', 'scalpel'),
('طب الأعصاب', 'Neurology', 'تخصص في علاج أمراض الجهاز العصبي', 'brain');

INSERT INTO insurance_companies (name, contact_phone, contact_email) VALUES
('بوبا العربية', '920000000', 'info@bupa.com.sa'),
('التعاونية للتأمين', '920000001', 'info@tawuniya.com.sa'),
('ميدغلف', '920000002', 'info@medgulf.com.sa'),
('أكسا للتأمين', '920000003', 'info@axa.com.sa');

INSERT INTO clinics (name, address, city, phone, email) VALUES
('مستشفى الملك فهد', 'شارع الملك فهد، الرياض', 'الرياض', '0112345678', 'info@kfh.com.sa'),
('مستشفى الملك عبدالعزيز', 'شارع الملك عبدالعزيز، جدة', 'جدة', '0123456789', 'info@kauh.com.sa'),
('مستشفى الملك خالد', 'شارع الملك خالد، الدمام', 'الدمام', '0134567890', 'info@kkh.com.sa');