-- ===================================
-- بيانات موسعة - تخصصات ومدن المملكة
-- Extended Data - Specialties and Saudi Cities
-- ===================================

USE medical_appointments;

-- ===================================
-- حذف البيانات القديمة (اختياري)
-- ===================================
-- DELETE FROM reviews;
-- DELETE FROM appointments;
-- DELETE FROM doctor_schedules;
-- DELETE FROM doctors;
-- DELETE FROM patients;
-- DELETE FROM specialties;
-- DELETE FROM users WHERE role != 'admin';

-- ===================================
-- التخصصات الطبية الشاملة (40 تخصص)
-- Comprehensive Medical Specialties
-- ===================================

TRUNCATE TABLE specialties;

INSERT INTO specialties (name_ar, name_en, description, icon, is_active) VALUES
-- التخصصات الأساسية
('طب الأسنان العام', 'General Dentistry', 'تشخيص وعلاج أمراض الفم والأسنان واللثة', 'bi-tooth', 1),
('طب وجراحة العيون', 'Ophthalmology', 'تشخيص وعلاج أمراض العين والجراحات البصرية', 'bi-eye', 1),
('طب الأطفال', 'Pediatrics', 'الرعاية الصحية الشاملة للأطفال والرضع', 'bi-hospital', 1),
('طب وجراحة القلب', 'Cardiology', 'تشخيص وعلاج أمراض القلب والأوعية الدموية', 'bi-heart-pulse', 1),
('الأمراض الجلدية', 'Dermatology', 'تشخيص وعلاج أمراض الجلد والشعر والأظافر', 'bi-bandaid', 1),
('جراحة العظام', 'Orthopedics', 'علاج أمراض العظام والمفاصل والعمود الفقري', 'bi-activity', 1),
('النساء والولادة', 'Obstetrics and Gynecology', 'الرعاية الصحية للنساء والحوامل', 'bi-person', 1),
('الطب الباطني', 'Internal Medicine', 'تشخيص وعلاج الأمراض الباطنية للبالغين', 'bi-clipboard-pulse', 1),
('الأنف والأذن والحنجرة', 'ENT', 'علاج أمراض الأنف والأذن والحنجرة', 'bi-ear', 1),
('الطب النفسي', 'Psychiatry', 'تشخيص وعلاج الأمراض والاضطرابات النفسية', 'bi-brain', 1),

-- التخصصات الفرعية المتقدمة
('جراحة الأعصاب', 'Neurosurgery', 'جراحة الدماغ والنخاع الشوكي والأعصاب الطرفية', 'bi-cpu', 1),
('أمراض الأعصاب', 'Neurology', 'تشخيص وعلاج أمراض الجهاز العصبي', 'bi-diagram-3', 1),
('جراحة المسالك البولية', 'Urology', 'علاج أمراض الجهاز البولي والتناسلي', 'bi-droplet', 1),
('جراحة التجميل', 'Plastic Surgery', 'عمليات التجميل والترميم', 'bi-stars', 1),
('الجراحة العامة', 'General Surgery', 'العمليات الجراحية العامة', 'bi-scissors', 1),
('الأشعة التشخيصية', 'Radiology', 'التصوير الطبي والأشعة التشخيصية', 'bi-camera', 1),
('التخدير والعناية المركزة', 'Anesthesiology', 'التخدير وإدارة الألم والعناية المركزة', 'bi-capsule', 1),
('طب الطوارئ', 'Emergency Medicine', 'الحالات الطارئة والرعاية العاجلة', 'bi-ambulance', 1),
('الأورام', 'Oncology', 'تشخيص وعلاج الأمراض السرطانية', 'bi-virus', 1),
('أمراض الدم', 'Hematology', 'تشخيص وعلاج أمراض الدم', 'bi-droplet-half', 1),

-- التخصصات الدقيقة
('أمراض الروماتيزم', 'Rheumatology', 'علاج أمراض المفاصل والروماتيزم', 'bi-hand-index', 1),
('الغدد الصماء والسكري', 'Endocrinology', 'علاج اضطرابات الهرمونات والسكري', 'bi-thermometer', 1),
('أمراض الجهاز الهضمي', 'Gastroenterology', 'تشخيص وعلاج أمراض الجهاز الهضمي', 'bi-emoji-smile', 1),
('أمراض الكلى', 'Nephrology', 'تشخيص وعلاج أمراض الكلى', 'bi-moisture', 1),
('أمراض الصدر والرئة', 'Pulmonology', 'علاج أمراض الجهاز التنفسي والرئة', 'bi-lungs', 1),
('أمراض معدية', 'Infectious Diseases', 'تشخيص وعلاج الأمراض المعدية', 'bi-virus2', 1),
('الحساسية والمناعة', 'Allergy and Immunology', 'علاج الحساسية واضطرابات المناعة', 'bi-shield-check', 1),
('الطب الرياضي', 'Sports Medicine', 'علاج إصابات الرياضيين وإعادة التأهيل', 'bi-trophy', 1),
('طب الأسرة', 'Family Medicine', 'الرعاية الصحية الشاملة للعائلة', 'bi-people', 1),
('طب المسنين', 'Geriatrics', 'الرعاية الصحية لكبار السن', 'bi-person-cane', 1),

-- تخصصات الأطفال المتخصصة
('جراحة الأطفال', 'Pediatric Surgery', 'العمليات الجراحية للأطفال', 'bi-hospital', 1),
('أمراض قلب الأطفال', 'Pediatric Cardiology', 'أمراض القلب عند الأطفال', 'bi-heart', 1),
('أعصاب الأطفال', 'Pediatric Neurology', 'أمراض الجهاز العصبي عند الأطفال', 'bi-lightning', 1),

-- تخصصات الأسنان المتخصصة
('تقويم الأسنان', 'Orthodontics', 'تقويم الأسنان والفكين', 'bi-align-center', 1),
('جراحة الفم والوجه والفكين', 'Oral and Maxillofacial Surgery', 'جراحة الفم والوجه والفكين', 'bi-mask', 1),
('علاج جذور الأسنان', 'Endodontics', 'علاج جذور وعصب الأسنان', 'bi-three-dots-vertical', 1),
('طب أسنان الأطفال', 'Pediatric Dentistry', 'علاج أسنان الأطفال', 'bi-emoji-smile', 1),
('تجميل الأسنان', 'Cosmetic Dentistry', 'تجميل وتبييض الأسنان', 'bi-gem', 1),

-- تخصصات أخرى
('الطب البديل والتكميلي', 'Alternative Medicine', 'العلاج بالطب البديل والأعشاب', 'bi-flower1', 1),
('التغذية العلاجية', 'Clinical Nutrition', 'العلاج والاستشارات الغذائية', 'bi-apple', 1);

-- ===================================
-- جدول المدن السعودية
-- Saudi Cities Table
-- ===================================

CREATE TABLE IF NOT EXISTS cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_ar VARCHAR(100) NOT NULL,
    name_en VARCHAR(100) NOT NULL,
    region_ar VARCHAR(100) NOT NULL COMMENT 'المنطقة',
    region_en VARCHAR(100) NOT NULL,
    population INT DEFAULT NULL,
    is_major_city TINYINT(1) DEFAULT 0 COMMENT 'مدينة رئيسية',
    latitude DECIMAL(10, 8) DEFAULT NULL,
    longitude DECIMAL(11, 8) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_region (region_ar),
    INDEX idx_major (is_major_city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- مدن المملكة العربية السعودية (13 منطقة)
-- Saudi Arabia Cities (13 Regions)
-- ===================================

INSERT INTO cities (name_ar, name_en, region_ar, region_en, population, is_major_city, latitude, longitude) VALUES
-- منطقة الرياض
('الرياض', 'Riyadh', 'منطقة الرياض', 'Riyadh Region', 7000000, 1, 24.7136, 46.6753),
('الخرج', 'Al-Kharj', 'منطقة الرياض', 'Riyadh Region', 430000, 0, 24.1553, 47.3048),
('الدوادمي', 'Ad-Dawadmi', 'منطقة الرياض', 'Riyadh Region', 90000, 0, 24.5085, 44.3911),
('المجمعة', 'Al-Majma\'ah', 'منطقة الرياض', 'Riyadh Region', 100000, 0, 25.8928, 45.3670),
('الزلفي', 'Az-Zulfi', 'منطقة الرياض', 'Riyadh Region', 70000, 0, 26.2990, 44.8057),
('شقراء', 'Shaqra', 'منطقة الرياض', 'Riyadh Region', 50000, 0, 25.2526, 45.2602),
('عفيف', 'Afif', 'منطقة الرياض', 'Riyadh Region', 60000, 0, 23.9065, 42.9173),
('وادي الدواسر', 'Wadi Ad-Dawasir', 'منطقة الرياض', 'Riyadh Region', 110000, 0, 20.5042, 44.7292),

-- المنطقة الشرقية
('الدمام', 'Dammam', 'المنطقة الشرقية', 'Eastern Region', 1500000, 1, 26.4207, 50.0888),
('الخبر', 'Khobar', 'المنطقة الشرقية', 'Eastern Region', 700000, 1, 26.2172, 50.1971),
('الظهران', 'Dhahran', 'المنطقة الشرقية', 'Eastern Region', 150000, 0, 26.2758, 50.1503),
('الجبيل', 'Jubail', 'المنطقة الشرقية', 'Eastern Region', 400000, 1, 27.0174, 49.6583),
('القطيف', 'Qatif', 'المنطقة الشرقية', 'Eastern Region', 500000, 0, 26.5210, 50.0089),
('الأحساء', 'Al-Ahsa', 'المنطقة الشرقية', 'Eastern Region', 1100000, 1, 25.4295, 49.5951),
('الهفوف', 'Hofuf', 'المنطقة الشرقية', 'Eastern Region', 600000, 0, 25.3647, 49.5856),
('حفر الباطن', 'Hafar Al-Batin', 'المنطقة الشرقية', 'Eastern Region', 350000, 0, 28.4327, 45.9608),
('رأس تنورة', 'Ras Tanura', 'المنطقة الشرقية', 'Eastern Region', 80000, 0, 26.6500, 50.1667),

-- منطقة مكة المكرمة
('مكة المكرمة', 'Makkah', 'منطقة مكة المكرمة', 'Makkah Region', 2000000, 1, 21.4225, 39.8262),
('جدة', 'Jeddah', 'منطقة مكة المكرمة', 'Makkah Region', 4000000, 1, 21.5433, 39.1728),
('الطائف', 'Taif', 'منطقة مكة المكرمة', 'Makkah Region', 1000000, 1, 21.2703, 40.4150),
('القنفذة', 'Al-Qunfudhah', 'منطقة مكة المكرمة', 'Makkah Region', 150000, 0, 19.1296, 41.0783),
('الليث', 'Al-Lith', 'منطقة مكة المكرمة', 'Makkah Region', 100000, 0, 20.1583, 40.2667),
('رابغ', 'Rabigh', 'منطقة مكة المكرمة', 'Makkah Region', 180000, 0, 22.7967, 39.0343),
('خليص', 'Khulais', 'منطقة مكة المكرمة', 'Makkah Region', 70000, 0, 22.2350, 39.3050),
('الجموم', 'Al-Jumum', 'منطقة مكة المكرمة', 'Makkah Region', 60000, 0, 21.6250, 39.7000),

-- المنطقة المدينة المنورة
('المدينة المنورة', 'Madinah', 'منطقة المدينة المنورة', 'Madinah Region', 1500000, 1, 24.5247, 39.5692),
('ينبع', 'Yanbu', 'منطقة المدينة المنورة', 'Madinah Region', 350000, 1, 24.0890, 38.0618),
('العلا', 'Al-Ula', 'منطقة المدينة المنورة', 'Madinah Region', 100000, 0, 26.6083, 37.9267),
('بدر', 'Badr', 'منطقة المدينة المنورة', 'Madinah Region', 50000, 0, 23.7833, 38.7917),
('خيبر', 'Khaybar', 'منطقة المدينة المنورة', 'Madinah Region', 60000, 0, 25.7000, 39.3000),
('المهد', 'Al-Mahd', 'منطقة المدينة المنورة', 'Madinah Region', 40000, 0, 23.8833, 40.7667),

-- منطقة القصيم
('بريدة', 'Buraidah', 'منطقة القصيم', 'Qassim Region', 650000, 1, 26.3260, 43.9750),
('عنيزة', 'Unaizah', 'منطقة القصيم', 'Qassim Region', 400000, 1, 26.0875, 43.9936),
('الرس', 'Ar-Rass', 'منطقة القصيم', 'Qassim Region', 150000, 0, 25.8697, 43.4975),
('المذنب', 'Al-Mithnab', 'منطقة القصيم', 'Qassim Region', 70000, 0, 25.8611, 44.2250),
('البكيرية', 'Al-Bukayriyah', 'منطقة القصيم', 'Qassim Region', 80000, 0, 26.1394, 43.6578),
('البدائع', 'Al-Badai', 'منطقة القصيم', 'Qassim Region', 60000, 0, 25.5136, 43.7158),

-- منطقة عسير
('أبها', 'Abha', 'منطقة عسير', 'Asir Region', 500000, 1, 18.2164, 42.5053),
('خميس مشيط', 'Khamis Mushait', 'منطقة عسير', 'Asir Region', 600000, 1, 18.3067, 42.7289),
('بيشة', 'Bisha', 'منطقة عسير', 'Asir Region', 200000, 0, 19.9942, 42.6050),
('النماص', 'An-Namas', 'منطقة عسير', 'Asir Region', 80000, 0, 19.1453, 42.1347),
('محايل', 'Muhayil', 'منطقة عسير', 'Asir Region', 90000, 0, 18.5117, 42.0453),
('سراة عبيدة', 'Sarat Abidah', 'منطقة عسير', 'Asir Region', 70000, 0, 18.2333, 42.3333),
('ظهران الجنوب', 'Dhahran Al-Janoub', 'منطقة عسير', 'Asir Region', 60000, 0, 17.4833, 43.7167),

-- منطقة تبوك
('تبوك', 'Tabuk', 'منطقة تبوك', 'Tabuk Region', 600000, 1, 28.3838, 36.5550),
('الوجه', 'Al-Wajh', 'منطقة تبوك', 'Tabuk Region', 60000, 0, 26.2456, 36.4556),
('ضباء', 'Duba', 'منطقة تبوك', 'Tabuk Region', 70000, 0, 27.3500, 35.6833),
('تيماء', 'Tayma', 'منطقة تبوك', 'Tabuk Region', 90000, 0, 27.6317, 38.4867),
('أملج', 'Umluj', 'منطقة تبوك', 'Tabuk Region', 50000, 0, 25.0217, 37.2683),

-- منطقة حائل
('حائل', 'Hail', 'منطقة حائل', 'Hail Region', 450000, 1, 27.5219, 41.6900),
('بقعاء', 'Baqaa', 'منطقة حائل', 'Hail Region', 50000, 0, 25.9831, 41.6217),
('الشنان', 'Ash-Shinan', 'منطقة حائل', 'Hail Region', 40000, 0, 26.5667, 42.0333),
('موقق', 'Muqaq', 'منطقة حائل', 'Hail Region', 35000, 0, 26.2833, 41.4833),

-- منطقة الحدود الشمالية
('عرعر', 'Arar', 'منطقة الحدود الشمالية', 'Northern Borders Region', 250000, 1, 30.9753, 41.0381),
('رفحاء', 'Rafha', 'منطقة الحدود الشمالية', 'Northern Borders Region', 100000, 0, 29.6264, 43.4942),
('طريف', 'Turaif', 'منطقة الحدود الشمالية', 'Northern Borders Region', 60000, 0, 31.6725, 38.6633),

-- منطقة جازان
('جازان', 'Jazan', 'منطقة جازان', 'Jazan Region', 500000, 1, 16.8892, 42.5511),
('صبيا', 'Sabya', 'منطقة جازان', 'Jazan Region', 150000, 0, 17.1497, 42.6253),
('أبو عريش', 'Abu Arish', 'منطقة جازان', 'Jazan Region', 130000, 0, 16.9686, 42.8319),
('صامطة', 'Samtah', 'منطقة جازان', 'Jazan Region', 110000, 0, 16.5983, 42.9442),
('بيش', 'Baysh', 'منطقة جازان', 'Jazan Region', 90000, 0, 17.4667, 42.5833),
('الدرب', 'Ad-Darb', 'منطقة جازان', 'Jazan Region', 70000, 0, 17.7333, 42.2333),

-- منطقة نجران
('نجران', 'Najran', 'منطقة نجران', 'Najran Region', 400000, 1, 17.4924, 44.1277),
('شرورة', 'Sharurah', 'منطقة نجران', 'Najran Region', 100000, 0, 17.4833, 47.1167),
('حبونا', 'Habuna', 'منطقة نجران', 'Najran Region', 60000, 0, 17.4333, 44.0333),

-- منطقة الباحة
('الباحة', 'Al-Bahah', 'منطقة الباحة', 'Al-Bahah Region', 200000, 1, 20.0129, 41.4677),
('بلجرشي', 'Baljurashi', 'منطقة الباحة', 'Al-Bahah Region', 80000, 0, 19.9167, 41.6333),
('المخواة', 'Al-Mandaq', 'منطقة الباحة', 'Al-Bahah Region', 70000, 0, 19.9333, 41.2667),
('المندق', 'Al-Mukhwah', 'منطقة الباحة', 'Al-Bahah Region', 60000, 0, 20.2167, 41.3500),

-- منطقة الجوف
('سكاكا', 'Sakaka', 'منطقة الجوف', 'Al-Jouf Region', 200000, 1, 29.9697, 40.2064),
('دومة الجندل', 'Dumat Al-Jandal', 'منطقة الجوف', 'Al-Jouf Region', 70000, 0, 29.8067, 39.8700),
('القريات', 'Qurayyat', 'منطقة الجوف', 'Al-Jouf Region', 150000, 0, 31.3311, 37.3436),
('طبرجل', 'Tabarjal', 'منطقة الجوف', 'Al-Jouf Region', 60000, 0, 30.5000, 38.2167);

-- ===================================
-- تحديث جدول الأطباء لربطه بالمدن
-- ===================================

-- إضافة عمود city_id إذا لم يكن موجوداً
ALTER TABLE doctors ADD COLUMN IF NOT EXISTS city_id INT DEFAULT NULL AFTER clinic_city;
ALTER TABLE doctors ADD FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL;
ALTER TABLE doctors ADD INDEX idx_city (city_id);

-- ===================================
-- بيانات أطباء موسعة (50 طبيب)
-- Extended Doctors Data
-- ===================================

-- حذف الأطباء التجريبيين القدامى (عدا المدير)
DELETE FROM doctor_schedules WHERE doctor_id > 0;
DELETE FROM doctors WHERE id > 0;
DELETE FROM users WHERE role = 'doctor' AND id > 1;

-- إضافة أطباء في مختلف المدن والتخصصات
INSERT INTO users (email, password, full_name, phone, role, is_active, email_verified) VALUES
-- الرياض
('dr.ahmed.alsalem@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. أحمد محمد السالم', '0501234567', 'doctor', 1, 1),
('dr.fatima.alharbi@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. فاطمة عبدالله الحربي', '0502345678', 'doctor', 1, 1),
('dr.mohammed.alghamdi@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. محمد خالد الغامدي', '0503456789', 'doctor', 1, 1),
('dr.noura.almutairi@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. نورة سعد المطيري', '0504567890', 'doctor', 1, 1),
('dr.abdullah.alotaibi@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. عبدالله فهد العتيبي', '0505678901', 'doctor', 1, 1),

-- جدة
('dr.sara.aljuhani@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. سارة أحمد الجهني', '0506789012', 'doctor', 1, 1),
('dr.omar.alzahrani@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. عمر عبدالرحمن الزهراني', '0507890123', 'doctor', 1, 1),
('dr.huda.baabbad@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. هدى محمد بعباد', '0508901234', 'doctor', 1, 1),
('dr.khalid.almalki@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. خالد سلمان المالكي', '0509012345', 'doctor', 1, 1),
('dr.maha.alsharif@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. مها عبدالله الشريف', '0501123456', 'doctor', 1, 1),

-- الدمام
('dr.saleh.aldosari@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. صالح علي الدوسري', '0502234567', 'doctor', 1, 1),
('dr.lina.alnasser@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. لينا فهد الناصر', '0503345678', 'doctor', 1, 1),
('dr.faisal.alqahtani@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. فيصل سعيد القحطاني', '0504456789', 'doctor', 1, 1),
('dr.aisha.alshammari@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. عائشة حمد الشمري', '0505567890', 'doctor', 1, 1),
('dr.turki.algosaibi@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. تركي ناصر القصيبي', '0506678901', 'doctor', 1, 1),

-- مكة المكرمة
('dr.amira.alkhatib@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. أميرة حسن الخطيب', '0507789012', 'doctor', 1, 1),
('dr.waleed.alharthy@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. وليد إبراهيم الحارثي', '0508890123', 'doctor', 1, 1),
('dr.layla.baghdadi@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. ليلى محمود بغدادي', '0509901234', 'doctor', 1, 1),

-- المدينة المنورة
('dr.yasser.alarifi@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. ياسر صالح العريفي', '0501012345', 'doctor', 1, 1),
('dr.najla.alansari@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. نجلاء عبدالعزيز الأنصاري', '0502123456', 'doctor', 1, 1),
('dr.majed.aljabri@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. ماجد فواز الجابري', '0503234567', 'doctor', 1, 1),

-- الطائف
('dr.reem.althagafi@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. ريم عبدالله الثقفي', '0504345678', 'doctor', 1, 1),
('dr.bandar.albalawi@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. بندر سعود البلوي', '0505456789', 'doctor', 1, 1),

-- أبها
('dr.hanan.asiri@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. حنان علي عسيري', '0506567890', 'doctor', 1, 1),
('dr.saad.alghamdi@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. سعد محمد الغامدي', '0507678901', 'doctor', 1, 1),
('dr.wafa.alshahrani@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. وفاء حسن الشهراني', '0508789012', 'doctor', 1, 1),

-- بريدة
('dr.adel.almutlaq@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. عادل صالح المطلق', '0509890123', 'doctor', 1, 1),
('dr.jamilah.alrasheed@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. جميلة عبدالله الرشيد', '0501901234', 'doctor', 1, 1),

-- تبوك
('dr.basem.alblowi@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. باسم فهد البلوي', '0502012345', 'doctor', 1, 1),
('dr.mona.alenzi@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. منى سعيد العنزي', '0503123456', 'doctor', 1, 1),

-- حائل
('dr.nasser.alshamri@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. ناصر محمد الشمري', '0504234567', 'doctor', 1, 1),
('dr.hessa.alharbi@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. حصة خالد الحربي', '0505345678', 'doctor', 1, 1),

-- جازان
('dr.ali.hakami@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. علي أحمد حكمي', '0506456789', 'doctor', 1, 1),
('dr.nada.madkhali@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. ندى عبدالله مدخلي', '0507567890', 'doctor', 1, 1),

-- نجران
('dr.ibrahim.almarwani@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. إبراهيم سعيد المروان', '0508678901', 'doctor', 1, 1),
('dr.abeer.almakrami@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. عبير محمد المكرمي', '0509789012', 'doctor', 1, 1),

-- الباحة
('dr.abdullah.alzahrani@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. عبدالله أحمد الزهراني', '0501890123', 'doctor', 1, 1),
('dr.dalal.alghamdi@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. دلال علي الغامدي', '0502901234', 'doctor', 1, 1),

-- الخبر
('dr.fahad.alsubaie@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. فهد خالد السبيعي', '0503012345', 'doctor', 1, 1),
('dr.rawan.alhaddad@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. روان محمد الحداد', '0504123456', 'doctor', 1, 1),

-- الجبيل
('dr.sultan.alkhaldi@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. سلطان عبدالله الخالدي', '0505234567', 'doctor', 1, 1),
('dr.hind.almanea@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. هند سليمان المنيع', '0506345678', 'doctor', 1, 1),

-- الأحساء
('dr.rashid.almufarrij@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. راشد حمد المفرج', '0507456789', 'doctor', 1, 1),
('dr.amal.aljishi@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. أمل عبدالله الجشي', '0508567890', 'doctor', 1, 1),

-- ينبع
('dr.masoud.alharbi@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. مسعود صالح الحربي', '0509678901', 'doctor', 1, 1),
('dr.thanaa.alotaibi@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. ثناء فهد العتيبي', '0501789012', 'doctor', 1, 1),

-- خميس مشيط
('dr.meshal.alqarni@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. مشعل سعيد القرني', '0502890123', 'doctor', 1, 1),
('dr.ghada.alshahrani@shifa.sa', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyxqrPYsY6WO', 'د. غادة عبدالله الشهراني', '0503901234', 'doctor', 1, 1);

-- إدراج بيانات الأطباء مع ربطها بالمدن والتخصصات
INSERT INTO doctors (user_id, specialty_id, city_id, license_number, bio, years_of_experience, consultation_fee, rating, total_reviews, clinic_name, clinic_address, clinic_city, clinic_phone) VALUES
-- الرياض
(2, 1, 1, 'LIC-2024-001', 'أخصائي طب الأسنان مع خبرة 12 عاماً في جراحة الفم والأسنان', 12, 300, 4.8, 245, 'عيادة الابتسامة الساطعة', 'شارع التحلية، حي النزهة', 'الرياض', '0112345001'),
(3, 7, 1, 'LIC-2024-002', 'استشارية نساء وولادة، متخصصة في الحمل عالي الخطورة', 15, 400, 4.9, 312, 'مركز الأمل الطبي النسائي', 'طريق الملك عبدالله، حي العليا', 'الرياض', '0112345002'),
(4, 4, 1, 'LIC-2024-003', 'استشاري أمراض القلب والقسطرة العلاجية', 18, 500, 4.7, 189, 'مركز القلب المتقدم', 'شارع العروبة، حي المالقة', 'الرياض', '0112345003'),
(5, 3, 1, 'LIC-2024-004', 'أخصائية طب الأطفال وحديثي الولادة', 10, 250, 4.9, 421, 'عيادة براعم الصحة', 'طريق الأمير سلمان، حي السلامة', 'الرياض', '0112345004'),
(6, 8, 1, 'LIC-2024-005', 'استشاري باطنية عامة والجهاز الهضمي', 14, 350, 4.6, 178, 'العيادات المتخصصة', 'شارع الملك فهد، حي المربع', 'الرياض', '0112345005'),

-- جدة
(7, 2, 19, 'LIC-2024-006', 'استشارية طب وجراحة العيون، متخصصة في الليزك', 11, 450, 4.8, 298, 'مركز النور للعيون', 'شارع التحلية، حي الزهراء', 'جدة', '0122345001'),
(8, 6, 19, 'LIC-2024-007', 'استشاري جراحة عظام ومفاصل', 16, 500, 4.7, 234, 'عيادة العظام المتطورة', 'طريق الكورنيش، حي الشاطئ', 'جدة', '0122345002'),
(9, 5, 19, 'LIC-2024-008', 'أخصائية أمراض جلدية وتجميل', 9, 300, 4.9, 456, 'كلينيك الجمال الطبي', 'شارع فلسطين، حي الروضة', 'جدة', '0122345003'),
(10, 10, 19, 'LIC-2024-009', 'استشاري الطب النفسي والعلاج السلوكي', 13, 400, 4.8, 167, 'مركز السلام النفسي', 'طريق المدينة، حي النعيم', 'جدة', '0122345004'),
(11, 9, 19, 'LIC-2024-010', 'استشارية أنف وأذن وحنجرة', 12, 350, 4.7, 201, 'عيادة السمع والتوازن', 'شارع حراء، حي الفيصلية', 'جدة', '0122345005'),

-- الدمام
(12, 8, 9, 'LIC-2024-011', 'استشاري طب باطني وأمراض معدية', 14, 380, 4.8, 189, 'المركز الطبي الشامل', 'شارع الملك سعود، حي الشاطئ', 'الدمام', '0132345001'),
(13, 7, 9, 'LIC-2024-012', 'أخصائية نساء وولادة وعقم', 10, 400, 4.9, 278, 'مركز الخصوبة والنسائية', 'طريق الظهران، حي الفردوس', 'الدمام', '0132345002'),
(14, 4, 9, 'LIC-2024-013', 'استشاري أمراض قلب وشرايين', 17, 520, 4.7, 156, 'مستوصف القلب الرائد', 'شارع الخليج، حي المزروعية', 'الدمام', '0132345003'),
(15, 3, 9, 'LIC-2024-014', 'أخصائية طب أطفال وتطعيمات', 11, 280, 4.9, 389, 'عيادة الأطفال المرحة', 'طريق الملك فهد، حي الفيصلية', 'الدمام', '0132345004'),
(16, 11, 9, 'LIC-2024-015', 'استشاري جراحة أعصاب ودماغ', 19, 600, 4.8, 142, 'مركز جراحة الأعصاب', 'شارع الأمير محمد، حي الأثير', 'الدمام', '0132345005'),

-- مكة المكرمة
(17, 1, 18, 'LIC-2024-016', 'أخصائية طب أسنان وتجميل', 8, 280, 4.9, 334, 'عيادة الأسنان الماسية', 'شارع العزيزية، حي العزيزية', 'مكة المكرمة', '0252345001'),
(18, 6, 18, 'LIC-2024-017', 'استشاري جراحة عظام ورياضية', 15, 480, 4.7, 198, 'مركز العظام المتكامل', 'طريق الهجرة، حي النسيم', 'مكة المكرمة', '0252345002'),
(19, 2, 18, 'LIC-2024-018', 'استشارية طب عيون وليزر', 12, 420, 4.8, 267, 'عيادات الرؤية الواضحة', 'شارع جبل عمر، حي العوالي', 'مكة المكرمة', '0252345003'),

-- المدينة المنورة
(20, 8, 28, 'LIC-2024-019', 'استشاري باطنية وجهاز هضمي', 13, 360, 4.8, 176, 'المستوصف الطبي الحديث', 'طريق سلطانة، حي السلام', 'المدينة المنورة', '0142345001'),
(21, 7, 28, 'LIC-2024-020', 'أخصائية نساء وولادة', 9, 380, 4.9, 245, 'عيادة الأمومة والطفولة', 'شارع العيون، حي العيون', 'المدينة المنورة', '0142345002'),
(22, 5, 28, 'LIC-2024-021', 'استشاري أمراض جلدية وليزر', 14, 340, 4.7, 189, 'مركز البشرة النضرة', 'طريق الملك عبدالله، حي الحرم', 'المدينة المنورة', '0142345003'),

-- الطائف
(23, 3, 20, 'LIC-2024-022', 'أخصائية طب أطفال ورضاعة', 10, 260, 4.9, 312, 'عيادة أطفال الطائف', 'شارع الحويه، حي الحويه', 'الطائف', '0122245001'),
(24, 4, 20, 'LIC-2024-023', 'استشاري أمراض قلب تداخلية', 16, 480, 4.8, 167, 'مركز القلب النابض', 'طريق الرياض، حي السداد', 'الطائف', '0122245002'),

-- أبها
(25, 6, 39, 'LIC-2024-024', 'استشارية جراحة عظام أطفال', 11, 400, 4.9, 223, 'عيادة عظام الأطفال', 'شارع الملك فهد، حي الضباب', 'أبها', '0172345001'),
(26, 1, 39, 'LIC-2024-025', 'استشاري طب وجراحة الفم', 15, 320, 4.7, 198, 'مجمع الأسنان المتطور', 'طريق الملك عبدالله، حي الموظفين', 'أبها', '0172345002'),
(27, 10, 39, 'LIC-2024-026', 'أخصائية طب نفسي وإدمان', 8, 350, 4.8, 134, 'مركز الصحة النفسية', 'شارع الأمير سلطان، حي المنسك', 'أبها', '0172345003'),

-- بريدة
(28, 8, 33, 'LIC-2024-027', 'استشاري باطنية وسكري', 14, 340, 4.8, 189, 'عيادة السكري المتخصصة', 'طريق الملك عبدالعزيز، حي السالمية', 'بريدة', '0163345001'),
(29, 7, 33, 'LIC-2024-028', 'أخصائية نساء وولادة وأطفال أنابيب', 12, 420, 4.9, 256, 'مركز الخصوبة والإنجاب', 'شارع الستين، حي الصفراء', 'بريدة', '0163345002'),

-- تبوك
(30, 5, 47, 'LIC-2024-029', 'استشاري أمراض جلدية وتناسلية', 13, 320, 4.7, 167, 'عيادة الجلدية المتقدمة', 'طريق الأمير فهد، حي السليمانية', 'تبوك', '0143345001'),
(31, 3, 47, 'LIC-2024-030', 'أخصائية طب أطفال وحديثي الولادة', 9, 270, 4.9, 289, 'مركز رعاية الأطفال', 'شارع الجيش، حي المنطقة الصناعية', 'تبوك', '0143345002'),

-- حائل
(32, 6, 51, 'LIC-2024-031', 'استشاري جراحة عظام ومناظير', 14, 420, 4.8, 178, 'مجمع حائل للعظام', 'طريق الملك عبدالعزيز، حي السمراء', 'حائل', '0165345001'),
(33, 2, 51, 'LIC-2024-032', 'أخصائية طب وجراحة عيون', 10, 380, 4.9, 234, 'مركز العيون والليزك', 'شارع الملك فهد، حي البادية', 'حائل', '0165345002'),

-- جازان
(34, 8, 55, 'LIC-2024-033', 'استشاري طب باطني وكبد', 15, 350, 4.7, 156, 'المستوصف الشامل', 'طريق الملك عبدالله، حي الروضة', 'جازان', '0173345001'),
(35, 3, 55, 'LIC-2024-034', 'أخصائية طب أطفال وتغذية', 8, 250, 4.9, 312, 'عيادة صحة الطفل', 'شارع الأمير سلطان، حي المطار', 'جازان', '0173345002'),

-- نجران
(36, 4, 61, 'LIC-2024-035', 'استشاري أمراض قلب بالغين', 16, 460, 4.8, 145, 'مركز القلب التخصصي', 'طريق الملك عبدالعزيز، حي الفيصلية', 'نجران', '0175345001'),
(37, 7, 61, 'LIC-2024-036', 'أخصائية نساء وولادة وجراحة', 11, 390, 4.9, 198, 'مستوصف المرأة والطفل', 'شارع الملك خالد، حي الضباط', 'نجران', '0175345002'),

-- الباحة
(38, 6, 64, 'LIC-2024-037', 'استشاري عظام وكسور', 13, 400, 4.7, 167, 'عيادة العظام التخصصية', 'طريق الملك فهد، حي الزهور', 'الباحة', '0177345001'),
(39, 1, 64, 'LIC-2024-038', 'أخصائية طب أسنان عامة', 7, 260, 4.9, 278, 'مجمع الباحة للأسنان', 'شارع الأمير محمد، حي الطلعة', 'الباحة', '0177345002'),

-- الخبر
(40, 2, 10, 'LIC-2024-039', 'استشاري طب عيون وشبكية', 17, 500, 4.8, 189, 'مركز الرؤية للعيون', 'طريق الأمير تركي، حي العقربية', 'الخبر', '0133445001'),
(41, 5, 10, 'LIC-2024-040', 'أخصائية جلدية وتجميل بالليزر', 10, 350, 4.9, 345, 'كلينيك الجمال المتقدم', 'شارع الظهران، حي الراكة', 'الخبر', '0133445002'),

-- الجبيل
(42, 8, 11, 'LIC-2024-041', 'استشاري باطنية وكلى', 14, 380, 4.7, 156, 'المركز الطبي المتخصص', 'طريق الملك فهد، حي الصناعية', 'الجبيل', '0133545001'),
(43, 3, 11, 'LIC-2024-042', 'أخصائية طب أطفال وحساسية', 9, 280, 4.9, 267, 'عيادة أطفال الجبيل', 'شارع الخليج، حي الدفي', 'الجبيل', '0133545002'),

-- الأحساء
(44, 4, 13, 'LIC-2024-043', 'استشاري قلب وقسطرة', 18, 540, 4.8, 178, 'مستشفى القلب المتقدم', 'طريق الملك عبدالله، حي المبرز', 'الأحساء', '0135645001'),
(45, 7, 13, 'LIC-2024-044', 'أخصائية نساء وأطفال أنابيب', 12, 410, 4.9, 234, 'مركز الأحساء للخصوبة', 'شارع الملك فيصل، حي الهفوف', 'الأحساء', '0135645002'),

-- ينبع
(46, 6, 29, 'LIC-2024-045', 'استشاري جراحة عظام', 15, 440, 4.7, 145, 'مجمع العظام الحديث', 'طريق الكورنيش، حي الهيئة', 'ينبع', '0144445001'),
(47, 1, 29, 'LIC-2024-046', 'أخصائية تقويم أسنان', 8, 320, 4.9, 289, 'عيادة التقويم المثالي', 'شارع الملك عبدالعزيز، حي الفيصلية', 'ينبع', '0144445002'),

-- خميس مشيط
(48, 10, 40, 'LIC-2024-047', 'استشاري طب نفسي أطفال', 11, 380, 4.8, 123, 'مركز الطب النفسي', 'طريق الملك فهد، حي المستشفى', 'خميس مشيط', '0172445001'),
(49, 5, 40, 'LIC-2024-048', 'أخصائية أمراض جلدية وشعر', 9, 310, 4.9, 312, 'عيادة الجلدية والتجميل', 'شارع الملك خالد، حي الموظفين', 'خميس مشيط', '0172445002');

-- إضافة جداول عمل للأطباء الجدد (الأحد - الخميس)
-- سيتم إضافة جداول عمل نموذجية لكل طبيب
INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, slot_duration, is_active)
SELECT 
    id,
    day_of_week,
    CASE 
        WHEN day_of_week IN (0,1,2,3,4) THEN '09:00:00'
    END as start_time,
    CASE 
        WHEN day_of_week IN (0,1,2,3,4) THEN '13:00:00'
    END as end_time,
    30 as slot_duration,
    1 as is_active
FROM doctors
CROSS JOIN (
    SELECT 0 as day_of_week UNION ALL
    SELECT 1 UNION ALL
    SELECT 2 UNION ALL
    SELECT 3 UNION ALL
    SELECT 4
) days
WHERE doctors.id > 0;

-- إضافة فترة مسائية
INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, slot_duration, is_active)
SELECT 
    id,
    day_of_week,
    '16:00:00' as start_time,
    '20:00:00' as end_time,
    30 as slot_duration,
    1 as is_active
FROM doctors
CROSS JOIN (
    SELECT 0 as day_of_week UNION ALL
    SELECT 1 UNION ALL
    SELECT 2 UNION ALL
    SELECT 3
) days
WHERE doctors.id > 0;

-- ===================================
-- إحصائيات البيانات المضافة
-- ===================================
SELECT 
    'التخصصات الطبية' as النوع,
    COUNT(*) as العدد
FROM specialties
UNION ALL
SELECT 
    'المدن السعودية' as النوع,
    COUNT(*) as العدد
FROM cities
UNION ALL
SELECT 
    'الأطباء المسجلين' as النوع,
    COUNT(*) as العدد
FROM doctors;

-- عرض توزيع الأطباء حسب المدن
SELECT 
    c.name_ar as المدينة,
    c.region_ar as المنطقة,
    COUNT(d.id) as عدد_الأطباء
FROM cities c
LEFT JOIN doctors d ON c.id = d.city_id
GROUP BY c.id, c.name_ar, c.region_ar
HAVING عدد_الأطباء > 0
ORDER BY عدد_الأطباء DESC;

-- عرض توزيع الأطباء حسب التخصصات
SELECT 
    s.name_ar as التخصص,
    COUNT(d.id) as عدد_الأطباء
FROM specialties s
LEFT JOIN doctors d ON s.id = d.specialty_id
GROUP BY s.id, s.name_ar
HAVING عدد_الأطباء > 0
ORDER BY عدد_الأطباء DESC;

-- ===================================
-- تم الانتهاء بنجاح!
-- Database Extended Successfully!
-- ===================================
