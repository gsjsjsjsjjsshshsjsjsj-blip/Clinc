/**
 * Medical Appointment System - Frontend JavaScript
 * نظام المواعيد الطبية - جافا سكريبت الواجهة الأمامية
 */

class MedicalAppointmentSystem {
    constructor() {
        this.apiBaseUrl = 'api/';
        this.user = this.getUserFromStorage();
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.checkAuthentication();
    }

    /**
     * Setup global event listeners
     * إعداد مستمعي الأحداث العامة
     */
    setupEventListeners() {
        // تسجيل الدخول
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }

        // التسجيل
        const registerForm = document.getElementById('registerForm');
        if (registerForm) {
            registerForm.addEventListener('submit', (e) => this.handleRegister(e));
        }

        // البحث عن الأطباء
        const searchForm = document.getElementById('searchForm');
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => this.handleSearch(e));
        }
    }

    /**
     * Check user authentication
     * التحقق من مصادقة المستخدم
     */
    checkAuthentication() {
        if (this.user && this.user.id) {
            this.updateUserInterface();
        } else {
            this.redirectToLogin();
        }
    }

    /**
     * Get user from localStorage
     * الحصول على المستخدم من التخزين المحلي
     */
    getUserFromStorage() {
        try {
            return JSON.parse(localStorage.getItem('user') || '{}');
        } catch (error) {
            console.error('Error parsing user data:', error);
            return {};
        }
    }

    /**
     * Save user to localStorage
     * حفظ المستخدم في التخزين المحلي
     */
    saveUserToStorage(user) {
        localStorage.setItem('user', JSON.stringify(user));
        this.user = user;
    }

    /**
     * Handle login
     * معالجة تسجيل الدخول
     */
    async handleLogin(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const email = formData.get('email') || document.getElementById('floatingInput')?.value;
        const password = formData.get('password') || document.getElementById('floatingPassword')?.value;
        
        if (!email || !password) {
            this.showAlert('danger', 'يرجى ملء جميع الحقول المطلوبة');
            return;
        }

        try {
            const response = await this.makeRequest('auth.php?action=login', 'POST', {
                email: email,
                password: password
            });

            if (response.success) {
                this.saveUserToStorage(response.user);
                this.showAlert('success', 'تم تسجيل الدخول بنجاح!');
                
                setTimeout(() => {
                    this.redirectToDashboard(response.user.role);
                }, 1500);
            } else {
                this.showAlert('danger', response.error || 'فشل في تسجيل الدخول');
            }
        } catch (error) {
            console.error('Login error:', error);
            this.showAlert('danger', 'حدث خطأ في الاتصال بالخادم');
        }
    }

    /**
     * Handle registration
     * معالجة التسجيل
     */
    async handleRegister(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const userData = {
            full_name: formData.get('full_name') || document.getElementById('floatingFullName')?.value,
            email: formData.get('email') || document.getElementById('floatingInput')?.value,
            password: formData.get('password') || document.getElementById('floatingPassword')?.value,
            phone: formData.get('phone') || document.getElementById('floatingPhone')?.value,
            gender: formData.get('gender') || document.getElementById('floatingGender')?.value,
            date_of_birth: formData.get('date_of_birth') || document.getElementById('floatingBirthDate')?.value
        };

        // التحقق من صحة البيانات
        if (!this.validateRegistrationData(userData)) {
            return;
        }

        try {
            const response = await this.makeRequest('auth.php?action=register', 'POST', userData);

            if (response.success) {
                this.showAlert('success', 'تم إنشاء الحساب بنجاح!');
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 2000);
            } else {
                this.showAlert('danger', response.error || 'فشل في إنشاء الحساب');
            }
        } catch (error) {
            console.error('Registration error:', error);
            this.showAlert('danger', 'حدث خطأ في الاتصال بالخادم');
        }
    }

    /**
     * Validate registration data
     * التحقق من صحة بيانات التسجيل
     */
    validateRegistrationData(data) {
        if (!data.full_name || !data.email || !data.password) {
            this.showAlert('danger', 'جميع الحقول المطلوبة يجب ملؤها');
            return false;
        }

        if (!this.isValidEmail(data.email)) {
            this.showAlert('danger', 'البريد الإلكتروني غير صحيح');
            return false;
        }

        if (data.password.length < 8) {
            this.showAlert('danger', 'كلمة المرور يجب أن تحتوي على 8 أحرف على الأقل');
            return false;
        }

        return true;
    }

    /**
     * Validate email format
     * التحقق من صيغة البريد الإلكتروني
     */
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Handle search
     * معالجة البحث
     */
    async handleSearch(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const searchData = {
            specialization: formData.get('specialization'),
            city: formData.get('city'),
            doctor_name: formData.get('doctor_name')
        };

        try {
            const response = await this.makeRequest('doctors.php?action=search', 'GET', searchData);
            
            if (response.success) {
                this.displaySearchResults(response.doctors);
            } else {
                this.showAlert('danger', response.error || 'فشل في البحث');
            }
        } catch (error) {
            console.error('Search error:', error);
            this.showAlert('danger', 'حدث خطأ في البحث');
        }
    }

    /**
     * Display search results
     * عرض نتائج البحث
     */
    displaySearchResults(doctors) {
        const container = document.getElementById('searchResults');
        if (!container) return;

        if (doctors.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="bi bi-search fs-1 text-muted"></i>
                    <p class="mt-3 text-muted">لم يتم العثور على أطباء</p>
                </div>
            `;
            return;
        }

        container.innerHTML = doctors.map(doctor => `
            <div class="doctor-card mb-3">
                <div class="row">
                    <div class="col-md-2">
                        <div class="doctor-avatar">
                            <i class="bi bi-person-circle fs-1 text-primary"></i>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5 class="doctor-name">د. ${doctor.full_name}</h5>
                        <p class="doctor-specialty">${doctor.specialization_name}</p>
                        <div class="doctor-rating">
                            <i class="bi bi-star-fill text-warning"></i>
                            ${doctor.rating} (${doctor.total_reviews} تقييم)
                        </div>
                        <p class="doctor-location">
                            <i class="bi bi-geo-alt"></i> ${doctor.clinic_city}
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <p class="price-info">سعر الكشف: ${doctor.consultation_fee} ريال</p>
                        <button class="btn btn-primary" onclick="appointmentSystem.bookAppointment(${doctor.id})">
                            حجز موعد
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    /**
     * Book appointment
     * حجز موعد
     */
    async bookAppointment(doctorId) {
        if (!this.user || !this.user.id) {
            this.redirectToLogin();
            return;
        }

        // توجيه إلى صفحة حجز الموعد
        window.location.href = `patient/book-appointment.html?doctor_id=${doctorId}`;
    }

    /**
     * Load user appointments
     * تحميل مواعيد المستخدم
     */
    async loadUserAppointments(status = null, page = 1, limit = 10) {
        try {
            const params = new URLSearchParams({
                action: 'my-appointments',
                page: page,
                limit: limit
            });
            
            if (status) {
                params.append('status', status);
            }

            const response = await this.makeRequest(`appointments.php?${params}`, 'GET');
            
            if (response.success) {
                return response.appointments;
            } else {
                throw new Error(response.error || 'فشل في تحميل المواعيد');
            }
        } catch (error) {
            console.error('Error loading appointments:', error);
            throw error;
        }
    }

    /**
     * Load notifications
     * تحميل الإشعارات
     */
    async loadNotifications(limit = 10) {
        try {
            const response = await this.makeRequest(`notifications.php?action=my-notifications&limit=${limit}`, 'GET');
            
            if (response.success) {
                return response.notifications;
            } else {
                throw new Error(response.error || 'فشل في تحميل الإشعارات');
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
            throw error;
        }
    }

    /**
     * Update appointment status
     * تحديث حالة الموعد
     */
    async updateAppointmentStatus(appointmentId, status, notes = null) {
        try {
            const response = await this.makeRequest(`appointments.php?action=update-status&id=${appointmentId}`, 'PUT', {
                status: status,
                notes: notes
            });

            if (response.success) {
                this.showAlert('success', 'تم تحديث حالة الموعد بنجاح');
                return true;
            } else {
                this.showAlert('danger', response.error || 'فشل في تحديث حالة الموعد');
                return false;
            }
        } catch (error) {
            console.error('Error updating appointment:', error);
            this.showAlert('danger', 'حدث خطأ في تحديث حالة الموعد');
            return false;
        }
    }

    /**
     * Cancel appointment
     * إلغاء الموعد
     */
    async cancelAppointment(appointmentId, reason = null) {
        try {
            const response = await this.makeRequest(`appointments.php?action=cancel&id=${appointmentId}`, 'PUT', {
                reason: reason
            });

            if (response.success) {
                this.showAlert('success', 'تم إلغاء الموعد بنجاح');
                return true;
            } else {
                this.showAlert('danger', response.error || 'فشل في إلغاء الموعد');
                return false;
            }
        } catch (error) {
            console.error('Error cancelling appointment:', error);
            this.showAlert('danger', 'حدث خطأ في إلغاء الموعد');
            return false;
        }
    }

    /**
     * Make API request
     * إجراء طلب API
     */
    async makeRequest(endpoint, method = 'GET', data = null) {
        const url = `${this.apiBaseUrl}${endpoint}`;
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            }
        };

        if (data && method !== 'GET') {
            options.body = JSON.stringify(data);
        } else if (data && method === 'GET') {
            const params = new URLSearchParams(data);
            const urlWithParams = `${url}${url.includes('?') ? '&' : '?'}${params}`;
            const response = await fetch(urlWithParams, options);
            return await response.json();
        }

        const response = await fetch(url, options);
        return await response.json();
    }

    /**
     * Show alert message
     * عرض رسالة تنبيه
     */
    showAlert(type, message, containerId = 'alertContainer') {
        const container = document.getElementById(containerId);
        if (!container) {
            console.error('Alert container not found:', containerId);
            return;
        }

        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        container.innerHTML = '';
        container.appendChild(alertDiv);

        // إزالة التنبيه تلقائياً بعد 5 ثوان
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    /**
     * Update user interface based on authentication
     * تحديث واجهة المستخدم بناءً على المصادقة
     */
    updateUserInterface() {
        // تحديث اسم المستخدم في الشريط العلوي
        const userNameElements = document.querySelectorAll('#userName');
        userNameElements.forEach(element => {
            element.textContent = this.user.full_name || 'المستخدم';
        });

        // إخفاء/إظهار عناصر واجهة المستخدم
        const authElements = document.querySelectorAll('.auth-required');
        authElements.forEach(element => {
            element.style.display = this.user.id ? 'block' : 'none';
        });

        const guestElements = document.querySelectorAll('.guest-only');
        guestElements.forEach(element => {
            element.style.display = this.user.id ? 'none' : 'block';
        });
    }

    /**
     * Redirect to login page
     * التوجيه إلى صفحة تسجيل الدخول
     */
    redirectToLogin() {
        if (!window.location.pathname.includes('login.html') && 
            !window.location.pathname.includes('register.html') &&
            !window.location.pathname.includes('index.html')) {
            window.location.href = 'login.html';
        }
    }

    /**
     * Redirect to appropriate dashboard
     * التوجيه إلى لوحة التحكم المناسبة
     */
    redirectToDashboard(userRole) {
        switch (userRole) {
            case 'admin':
                window.location.href = 'admin/dashboard.html';
                break;
            case 'doctor':
                window.location.href = 'doctor/dashboard.html';
                break;
            case 'patient':
            default:
                window.location.href = 'patient/dashboard.html';
                break;
        }
    }

    /**
     * Logout user
     * تسجيل خروج المستخدم
     */
    logout() {
        if (confirm('هل أنت متأكد من تسجيل الخروج؟')) {
            localStorage.removeItem('user');
            this.user = {};
            window.location.href = 'login.html';
        }
    }

    /**
     * Format date for display
     * تنسيق التاريخ للعرض
     */
    formatDate(dateString, format = 'ar-SA') {
        const date = new Date(dateString);
        return date.toLocaleDateString(format);
    }

    /**
     * Format time for display
     * تنسيق الوقت للعرض
     */
    formatTime(timeString) {
        const [hours, minutes] = timeString.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'م' : 'ص';
        const displayHour = hour % 12 || 12;
        return `${displayHour}:${minutes} ${ampm}`;
    }

    /**
     * Get status text in Arabic
     * الحصول على نص الحالة بالعربية
     */
    getStatusText(status) {
        const statusTexts = {
            'pending': 'في الانتظار',
            'confirmed': 'مؤكد',
            'completed': 'مكتمل',
            'cancelled': 'ملغي',
            'no_show': 'لم يحضر'
        };
        return statusTexts[status] || status;
    }

    /**
     * Get status badge class
     * الحصول على فئة شارة الحالة
     */
    getStatusBadgeClass(status) {
        const statusClasses = {
            'pending': 'status-pending',
            'confirmed': 'status-confirmed',
            'completed': 'status-completed',
            'cancelled': 'status-cancelled',
            'no_show': 'status-cancelled'
        };
        return statusClasses[status] || 'status-pending';
    }
}

// إنشاء مثيل عام من النظام
const appointmentSystem = new MedicalAppointmentSystem();

// تصدير النظام للاستخدام في ملفات أخرى
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MedicalAppointmentSystem;
}