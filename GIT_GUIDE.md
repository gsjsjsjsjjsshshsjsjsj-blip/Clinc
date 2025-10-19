# دليل استخدام Git و GitHub 🚀
## Git & GitHub Usage Guide

---

## ✅ حالة المشروع الحالية

**جميع التغييرات تم رفعها بنجاح إلى GitHub!** ✨

### آخر Commit:
```
2f79af3 - feat: Expand database with cities and doctors
```

### الفرع الحالي:
```
cursor/build-medical-appointment-system-7d08
```

### الملفات المرفوعة في آخر تحديث:
- ✅ `database/extended_data.sql` (479 سطر)
- ✅ `database/README_DATABASE.md` (399 سطر)
- ✅ `includes/cities.php` (169 سطر)
- ✅ `api/cities.php` (70 سطر)
- ✅ `EXTENDED_DATABASE_GUIDE.md` (327 سطر)
- ✅ `UPDATE_SUMMARY.md` (347 سطر)
- ✅ تحديث `includes/doctors.php`

**المجموع**: +1807 سطر من الكود والتوثيق! 🎉

---

## 🔍 كيف أعرف أن التغييرات مرفوعة؟

### 1️⃣ التحقق من حالة Git

```bash
git status
```

**إذا ظهرت هذه الرسالة:**
```
On branch cursor/build-medical-appointment-system-7d08
Your branch is up to date with 'origin/...'
nothing to commit, working tree clean
```

**✅ معناها**: جميع التغييرات مرفوعة بنجاح!

---

### 2️⃣ مقارنة الفرع المحلي مع GitHub

```bash
# التحقق من الفروع
git branch -a

# مقارنة مع الفرع على GitHub
git diff origin/cursor/build-medical-appointment-system-7d08
```

**إذا لم يظهر شيء** = كل شيء محدث! ✅

---

### 3️⃣ عرض آخر Commits

```bash
# آخر 5 commits
git log --oneline -5

# التفاصيل الكاملة لآخر commit
git log -1 --stat
```

---

### 4️⃣ التحقق من GitHub مباشرة

افتح المتصفح واذهب إلى:
```
https://github.com/your-username/your-repo
```

تحقق من:
- ✅ تاريخ آخر commit
- ✅ عدد الملفات
- ✅ حجم المشروع

---

## 📤 كيف أرفع تغييرات جديدة؟

### السيناريو 1: ملفات جديدة أو معدلة

```bash
# 1. فحص الملفات المتغيرة
git status

# 2. إضافة جميع الملفات
git add .

# أو إضافة ملفات محددة
git add file1.php file2.sql

# 3. عمل commit مع رسالة واضحة
git commit -m "feat: Add new feature description"

# 4. رفع التغييرات إلى GitHub
git push origin cursor/build-medical-appointment-system-7d08
```

---

### السيناريو 2: ملفات تم حذفها

```bash
# حذف من Git
git rm file-to-delete.php

# commit
git commit -m "remove: Delete unnecessary file"

# رفع
git push
```

---

### السيناريو 3: تعديلات على ملفات موجودة

```bash
# Git يكتشفها تلقائياً
git status

# إضافة التعديلات
git add .

# commit ورفع
git commit -m "update: Improve database queries"
git push
```

---

## 📋 أوامر Git المفيدة

### التحقق من الحالة

```bash
# حالة المشروع
git status

# الملفات المتغيرة بالتفصيل
git status -v

# الملفات المتغيرة (مختصر)
git status -s
```

---

### عرض التغييرات

```bash
# التغييرات غير المحفوظة
git diff

# التغييرات المحفوظة (staged)
git diff --staged

# المقارنة مع commit معين
git diff HEAD~1

# إحصائيات التغييرات
git diff --stat
```

---

### سجل Commits

```bash
# آخر 10 commits (مختصر)
git log --oneline -10

# آخر commit بالتفصيل
git log -1

# سجل مع الملفات المتغيرة
git log --stat

# سجل مع التغييرات الكاملة
git log -p

# commits في فترة معينة
git log --since="2 days ago"

# commits لمستخدم معين
git log --author="YourName"
```

---

### إدارة الفروع

```bash
# عرض جميع الفروع
git branch -a

# إنشاء فرع جديد
git branch feature-name

# التبديل إلى فرع
git checkout feature-name

# إنشاء والتبديل مباشرة
git checkout -b new-feature

# دمج فرع
git merge feature-name

# حذف فرع
git branch -d feature-name
```

---

### التزامن مع GitHub

```bash
# تحديث من GitHub
git pull origin branch-name

# رفع فرع جديد
git push -u origin new-branch

# رفع جميع الفروع
git push --all

# رفع Tags
git push --tags
```

---

## 🎯 نصائح مهمة

### ✅ رسائل Commit الواضحة

استخدم بادئات واضحة:

```bash
# ميزة جديدة
git commit -m "feat: Add user authentication"

# إصلاح خطأ
git commit -m "fix: Resolve login issue"

# تحديث
git commit -m "update: Improve database performance"

# توثيق
git commit -m "docs: Add API documentation"

# تنظيف
git commit -m "refactor: Restructure user module"

# حذف
git commit -m "remove: Delete unused files"
```

---

### ✅ قبل كل Commit

```bash
# 1. تأكد من أن الكود يعمل
# 2. اختبر التغييرات
# 3. راجع الملفات المتغيرة

git status
git diff

# 4. ثم commit
```

---

### ✅ تجنب Commits الكبيرة

```bash
# بدلاً من commit واحد كبير:
git add .
git commit -m "Multiple changes"

# قسم إلى commits منطقية:
git add database/
git commit -m "feat: Add extended database"

git add includes/cities.php api/cities.php
git commit -m "feat: Add cities management"

git add *.md
git commit -m "docs: Add comprehensive documentation"
```

---

## 🔄 حالات شائعة

### 1. نسيت إضافة ملف في Commit

```bash
# أضف الملف
git add forgotten-file.php

# أضفه للـ commit الأخير
git commit --amend --no-edit

# أو مع رسالة جديدة
git commit --amend -m "New commit message"
```

---

### 2. تراجع عن تغييرات غير محفوظة

```bash
# تراجع عن ملف واحد
git checkout -- file.php

# تراجع عن جميع التغييرات
git checkout .

# أو
git reset --hard HEAD
```

---

### 3. تراجع عن Commit (لم يُرفع بعد)

```bash
# تراجع عن آخر commit (الملفات تبقى)
git reset --soft HEAD~1

# تراجع عن آخر commit (حذف التغييرات)
git reset --hard HEAD~1
```

---

### 4. التراجع عن Commit مرفوع

```bash
# إنشاء commit جديد يلغي الأخير
git revert HEAD

# رفع التراجع
git push
```

---

## 📊 فحص حالة المشروع الحالي

### الملفات الموجودة في Git

```bash
# عرض جميع الملفات المتتبعة
git ls-files

# عدد الملفات
git ls-files | wc -l

# الملفات حسب النوع
git ls-files | grep "\.php$"
git ls-files | grep "\.md$"
```

---

### إحصائيات المشروع

```bash
# عدد السطور في كل نوع ملف
git ls-files | grep "\.php$" | xargs wc -l

# عدد Commits
git rev-list --count HEAD

# المساهمون
git shortlog -sn

# أكثر الملفات تعديلاً
git log --format=format: --name-only | grep -v "^$" | sort | uniq -c | sort -rn | head -10
```

---

## 🌐 التحقق من GitHub عبر المتصفح

### 1. افتح Repository

```
https://github.com/username/repository-name
```

### 2. تحقق من:

- ✅ **آخر Commit**: يظهر في الأعلى
- ✅ **عدد Commits**: رقم بجانب أيقونة الساعة
- ✅ **الفروع**: Branches
- ✅ **الملفات**: تصفح المجلدات

### 3. عرض Commit معين

```
https://github.com/username/repo/commit/[commit-hash]
```

### 4. مقارنة Commits

```
https://github.com/username/repo/compare/main...feature-branch
```

---

## 🎓 أفضل الممارسات

### ✅ افعل:

1. **Commit بشكل متكرر** - commits صغيرة ومنطقية
2. **رسائل واضحة** - اشرح ماذا ولماذا
3. **اختبر قبل Commit** - تأكد أن الكود يعمل
4. **راجع التغييرات** - `git diff` قبل الـ commit
5. **اسحب قبل الدفع** - `git pull` قبل `git push`

### ❌ تجنب:

1. **Commits عشوائية** - "update" أو "changes"
2. **Commit كل شيء** - قسم التغييرات منطقياً
3. **تجاهل .gitignore** - لا ترفع ملفات غير ضرورية
4. **تعديل History** - إذا كان مشترك مع آخرين
5. **نسيان Push** - تأكد من رفع التغييرات

---

## 🛠️ أدوات مساعدة

### Git GUI Tools:

- **GitHub Desktop** - سهل للمبتدئين
- **GitKraken** - واجهة احترافية
- **SourceTree** - مجاني وقوي
- **VS Code Git** - مدمج في المحرر

### الأوامر السريعة:

```bash
# alias مفيدة (.bashrc أو .zshrc)
alias gs='git status'
alias ga='git add .'
alias gc='git commit -m'
alias gp='git push'
alias gl='git log --oneline'
alias gd='git diff'
```

---

## 📞 الدعم

إذا واجهت مشكلة:

1. اقرأ رسالة الخطأ بعناية
2. استخدم `git status` لفهم الحالة
3. راجع `git log` لمعرفة آخر التغييرات
4. ابحث عن الخطأ في Google
5. راجع [Git Documentation](https://git-scm.com/doc)

---

## ✅ الخلاصة

### حالة المشروع الآن:

✅ **جميع الملفات مرفوعة**
✅ **آخر commit: قاعدة البيانات الموسعة**
✅ **الفرع: cursor/build-medical-appointment-system-7d08**
✅ **متزامن مع GitHub**

### للتحقق المستقبلي:

```bash
# الأمر الأساسي
git status

# إذا ظهر "nothing to commit, working tree clean"
# ✅ يعني كل شيء مرفوع!
```

---

**تم بنجاح! جميع تغييراتك محفوظة على GitHub! 🎉**

---

*آخر تحديث: 2025-10-19*
