# دليل سريع - قاعدة البيانات الموسعة ⚡
## Quick Guide - Extended Database

---

## 🎉 ما الجديد؟

تم توسيع قاعدة البيانات بشكل كبير لتشمل:

✅ **40 تخصصاً طبياً** (بدلاً من 10)
✅ **90+ مدينة سعودية** في 13 منطقة (جديد!)
✅ **48 طبيباً** موزعين على مختلف المدن (بدلاً من 1)
✅ **جداول عمل كاملة** لجميع الأطباء

---

## 🚀 التطبيق السريع (دقيقتان)

### الخطوة 1: تطبيق الملف الموسع

```bash
# افتح MySQL
mysql -u root -p

# طبق الملف الموسع
USE medical_appointments;
SOURCE database/extended_data.sql;
```

أو باستخدام **phpMyAdmin**:
1. افتح phpMyAdmin
2. اختر قاعدة البيانات `medical_appointments`
3. Import → اختر `extended_data.sql`
4. اضغط Go

### الخطوة 2: تحقق من النجاح

```sql
-- يجب أن تحصل على هذه الأرقام
SELECT 'التخصصات' as النوع, COUNT(*) as العدد FROM specialties
UNION ALL
SELECT 'المدن', COUNT(*) FROM cities
UNION ALL
SELECT 'الأطباء', COUNT(*) FROM doctors;
```

**النتيجة المتوقعة:**
```
التخصصات: 40
المدن: 90+
الأطباء: 48
```

---

## 📊 ما تم إضافته؟

### 1️⃣ التخصصات (40 تخصص)

**الأساسية**: أسنان، عيون، أطفال، قلب، جلدية، عظام، نساء، باطنية، أنف وأذن، نفسية

**المتقدمة**: جراحة أعصاب، أعصاب، مسالك، تجميل، جراحة عامة، أشعة، تخدير، طوارئ، أورام، دم

**الدقيقة**: روماتيزم، غدد صماء، جهاز هضمي، كلى، صدر، أمراض معدية، حساسية، طب رياضي، طب أسرة، طب مسنين

**الأطفال**: جراحة أطفال، قلب أطفال، أعصاب أطفال

**الأسنان**: تقويم، جراحة فم، جذور، أسنان أطفال، تجميل أسنان

**أخرى**: طب بديل، تغذية علاجية

### 2️⃣ المدن (13 منطقة، 90+ مدينة)

| المنطقة | المدن الرئيسية | عدد المدن |
|---------|----------------|----------|
| الرياض | الرياض ⭐ | 8 |
| الشرقية | الدمام، الخبر، الجبيل، الأحساء ⭐ | 9 |
| مكة | مكة، جدة، الطائف ⭐ | 8 |
| المدينة | المدينة المنورة، ينبع ⭐ | 6 |
| القصيم | بريدة، عنيزة ⭐ | 6 |
| عسير | أبها، خميس مشيط ⭐ | 7 |
| تبوك | تبوك ⭐ | 5 |
| حائل | حائل ⭐ | 4 |
| الحدود الشمالية | عرعر ⭐ | 3 |
| جازان | جازان ⭐ | 6 |
| نجران | نجران ⭐ | 3 |
| الباحة | الباحة ⭐ | 4 |
| الجوف | سكاكا ⭐ | 4 |

### 3️⃣ الأطباء (48 طبيب)

موزعون على:
- **18 مدينة** رئيسية
- **معظم التخصصات** الطبية
- **جداول عمل** كاملة (الأحد-الخميس)
- **أسعار** متنوعة (250-600 ريال)
- **تقييمات** عالية (4.6-4.9)

---

## 💻 APIs الجديدة

### API المدن

```javascript
// الحصول على جميع المدن
GET /api/cities.php?action=all

// المدن الرئيسية فقط
GET /api/cities.php?action=all&major_only=1

// المدن حسب المنطقة
GET /api/cities.php?action=by_region&region=منطقة الرياض

// جميع المناطق
GET /api/cities.php?action=regions

// البحث عن مدينة
GET /api/cities.php?action=search&q=الرياض

// تفاصيل مدينة
GET /api/cities.php?action=details&city_id=1

// المدن الرئيسية مع عدد الأطباء
GET /api/cities.php?action=major_with_doctors
```

### API البحث المحدث

```javascript
// البحث عن أطباء في مدينة محددة
GET /api/search-doctors.php?city=1  // بمعرف المدينة
GET /api/search-doctors.php?city=الرياض  // باسم المدينة

// البحث حسب المنطقة
GET /api/search-doctors.php?region=منطقة مكة المكرمة

// البحث المتقدم
GET /api/search-doctors.php?specialty=1&city=19&min_rating=4.5&max_fee=400
```

---

## 🔍 استعلامات مفيدة

### 1. أطباء في مدينة معينة

```sql
SELECT 
    u.full_name as الطبيب,
    s.name_ar as التخصص,
    d.consultation_fee as السعر,
    d.rating as التقييم,
    d.clinic_name as العيادة
FROM doctors d
JOIN users u ON d.user_id = u.id
JOIN specialties s ON d.specialty_id = s.id
JOIN cities c ON d.city_id = c.id
WHERE c.name_ar = 'الرياض'
ORDER BY d.rating DESC;
```

### 2. المدن حسب عدد الأطباء

```sql
SELECT 
    c.name_ar as المدينة,
    c.region_ar as المنطقة,
    COUNT(d.id) as عدد_الأطباء
FROM cities c
LEFT JOIN doctors d ON c.id = d.city_id
GROUP BY c.id
HAVING عدد_الأطباء > 0
ORDER BY عدد_الأطباء DESC;
```

### 3. التخصصات الأكثر طلباً

```sql
SELECT 
    s.name_ar as التخصص,
    COUNT(d.id) as عدد_الأطباء,
    AVG(d.consultation_fee) as متوسط_السعر
FROM specialties s
LEFT JOIN doctors d ON s.id = d.specialty_id
GROUP BY s.id
HAVING عدد_الأطباء > 0
ORDER BY عدد_الأطباء DESC
LIMIT 10;
```

### 4. المناطق وإحصائياتها

```sql
SELECT 
    region_ar as المنطقة,
    COUNT(DISTINCT c.id) as عدد_المدن,
    COUNT(d.id) as عدد_الأطباء
FROM cities c
LEFT JOIN doctors d ON c.id = d.city_id
GROUP BY region_ar
ORDER BY عدد_الأطباء DESC;
```

---

## 🎯 حالات استخدام عملية

### 1. بحث المريض عن طبيب

```sql
-- مريض في جدة يبحث عن طبيب قلب
SELECT 
    u.full_name,
    d.clinic_name,
    d.clinic_address,
    d.consultation_fee,
    d.rating
FROM doctors d
JOIN users u ON d.user_id = u.id
JOIN specialties s ON d.specialty_id = s.id
JOIN cities c ON d.city_id = c.id
WHERE c.name_ar = 'جدة' 
  AND s.name_ar LIKE '%قلب%'
  AND d.rating >= 4.5
ORDER BY d.rating DESC, d.consultation_fee ASC;
```

### 2. عرض الأطباء في منطقة

```sql
-- جميع الأطباء في المنطقة الشرقية
SELECT 
    c.name_ar as المدينة,
    u.full_name as الطبيب,
    s.name_ar as التخصص,
    d.rating as التقييم
FROM doctors d
JOIN users u ON d.user_id = u.id
JOIN specialties s ON d.specialty_id = s.id
JOIN cities c ON d.city_id = c.id
WHERE c.region_ar = 'المنطقة الشرقية'
ORDER BY c.name_ar, d.rating DESC;
```

### 3. إحصائيات المدينة

```sql
-- إحصائيات شاملة لمدينة الرياض
SELECT 
    c.name_ar as المدينة,
    c.population as السكان,
    COUNT(DISTINCT d.id) as عدد_الأطباء,
    COUNT(DISTINCT d.specialty_id) as عدد_التخصصات,
    AVG(d.consultation_fee) as متوسط_السعر,
    AVG(d.rating) as متوسط_التقييم
FROM cities c
LEFT JOIN doctors d ON c.id = d.city_id
WHERE c.name_ar = 'الرياض'
GROUP BY c.id;
```

---

## 📝 ملاحظات مهمة

### حسابات الأطباء
- **البريد**: `dr.firstname.lastname@shifa.sa`
- **كلمة المرور**: `Doctor@123` (لجميع الأطباء)

### جداول العمل
- **أيام العمل**: الأحد - الخميس
- **الفترة الصباحية**: 9:00 - 13:00
- **الفترة المسائية**: 16:00 - 20:00 (الأحد - الأربعاء)
- **مدة الموعد**: 30 دقيقة

### البيانات الجغرافية
- كل مدينة لديها إحداثيات GPS
- يمكن استخدامها في الخرائط
- مناسبة لتطبيقات الموبايل

---

## ✅ التحقق من النجاح

بعد التطبيق، تأكد من:

- [ ] 40 تخصصاً في قاعدة البيانات
- [ ] 90+ مدينة سعودية
- [ ] 48 طبيباً مسجلين
- [ ] يمكن البحث عن الأطباء حسب المدينة
- [ ] API المدن يعمل بشكل صحيح
- [ ] جداول العمل موجودة لجميع الأطباء

---

## 🆘 حل المشاكل

### المشكلة: "Table 'cities' doesn't exist"
**الحل**: طبق `schema.sql` أولاً، ثم `extended_data.sql`

### المشكلة: بيانات مكررة
**الحل**: الملف `extended_data.sql` يحذف البيانات القديمة تلقائياً

### المشكلة: بعض المدن بدون أطباء
**الحل**: هذا طبيعي، الأطباء موزعون على المدن الرئيسية فقط

---

## 📚 التوثيق الكامل

للمزيد من التفاصيل:
- **قاعدة البيانات**: `database/README_DATABASE.md`
- **التثبيت**: `INSTALLATION.md`
- **الاستخدام**: `README.md`

---

## 🎊 مبروك!

الآن لديك نظام طبي متكامل يغطي:
- ✅ جميع التخصصات الطبية الرئيسية
- ✅ كل مناطق ومدن المملكة
- ✅ عشرات الأطباء في مختلف المدن
- ✅ نظام بحث متقدم

**جاهز للاستخدام! 🚀**
