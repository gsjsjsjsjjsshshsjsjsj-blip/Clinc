/**
 * Patient Dashboard JavaScript
 * لوحة تحكم المريض
 */

let currentUser = null;
let selectedDoctor = null;
let selectedTimeSlot = null;
let specialties = [];
let insuranceCompanies = [];

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    checkAuthentication();
    initializeEventListeners();
    loadInitialData();
});

/**
 * Check if user is authenticated
 */
function checkAuthentication() {
    const userData = localStorage.getItem('user_data');
    if (!userData) {
        window.location.href = '../login.html';
        return;
    }
    
    currentUser = JSON.parse(userData);
    if (currentUser.role !== 'patient') {
        window.location.href = '../login.html';
        return;
    }
    
    // Update welcome message
    document.getElementById('userWelcome').textContent = `مرحباً ${currentUser.name}`;
}

/**
 * Initialize event listeners
 */
function initializeEventListeners() {
    // Sidebar navigation
    document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const section = this.getAttribute('data-section');
            if (section) {
                showSection(section);
                updateActiveNavLink(this);
            }
        });
    });
    
    // Logout button
    document.getElementById('logoutBtn').addEventListener('click', logout);
    
    // Appointment date change
    document.getElementById('appointmentDate').addEventListener('change', loadAvailableTimeSlots);
    
    // Book appointment button
    document.getElementById('bookAppointmentBtn').addEventListener('click', bookAppointment);
    
    // Appointment status filter
    document.getElementById('appointmentStatusFilter').addEventListener('change', loadAppointments);
    
    // Profile form
    document.getElementById('profileForm').addEventListener('submit', updateProfile);
    
    // Set minimum date for appointment booking
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('appointmentDate').setAttribute('min', today);
}

/**
 * Load initial data
 */
async function loadInitialData() {
    await Promise.all([
        loadDashboardStats(),
        loadUpcomingAppointments(),
        loadSpecialties(),
        loadInsuranceCompanies(),
        loadCities(),
        loadUserProfile()
    ]);
}

/**
 * Show specific section
 */
function showSection(sectionName) {
    // Hide all sections
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.add('d-none');
    });
    
    // Show selected section
    const targetSection = document.getElementById(`${sectionName}-section`);
    if (targetSection) {
        targetSection.classList.remove('d-none');
        
        // Load section-specific data
        switch(sectionName) {
            case 'appointments':
                loadAppointments();
                break;
            case 'notifications':
                loadNotifications();
                break;
            case 'book-appointment':
                loadFeaturedDoctors();
                break;
        }
    }
}

/**
 * Update active navigation link
 */
function updateActiveNavLink(activeLink) {
    document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
        link.classList.remove('active');
    });
    activeLink.classList.add('active');
}

/**
 * Load dashboard statistics
 */
async function loadDashboardStats() {
    try {
        const response = await fetch('../api/appointments.php?action=get_stats');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('totalAppointments').textContent = data.data.total || 0;
            document.getElementById('pendingAppointments').textContent = data.data.pending || 0;
            document.getElementById('completedAppointments').textContent = data.data.completed || 0;
            document.getElementById('cancelledAppointments').textContent = data.data.cancelled || 0;
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

/**
 * Load upcoming appointments
 */
async function loadUpcomingAppointments() {
    try {
        const response = await fetch('../api/appointments.php?action=get_user_appointments&status=pending,confirmed&limit=5');
        const data = await response.json();
        
        const container = document.getElementById('upcomingAppointments');
        
        if (data.success && data.data.length > 0) {
            container.innerHTML = data.data.map(appointment => `
                <div class="appointment-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">د. ${appointment.doctor_name}</h6>
                            <p class="text-muted mb-1">${appointment.specialty_name}</p>
                            <p class="mb-1">
                                <i class="bi bi-calendar me-1"></i>
                                ${formatDate(appointment.appointment_date)}
                                <i class="bi bi-clock me-1 ms-3"></i>
                                ${formatTime(appointment.appointment_time)}
                            </p>
                            <p class="mb-0">
                                <i class="bi bi-geo-alt me-1"></i>
                                ${appointment.clinic_name}
                            </p>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-${getStatusColor(appointment.status)} mb-2">
                                ${getStatusText(appointment.status)}
                            </span>
                            <br>
                            <small class="text-muted">
                                رمز التأكيد: ${appointment.confirmation_code}
                            </small>
                        </div>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = `
                <div class="text-center py-4">
                    <i class="bi bi-calendar-x text-muted fs-1"></i>
                    <p class="text-muted mt-2">لا توجد مواعيد قادمة</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading upcoming appointments:', error);
        document.getElementById('upcomingAppointments').innerHTML = `
            <div class="alert alert-danger">
                حدث خطأ في تحميل المواعيد
            </div>
        `;
    }
}

/**
 * Load all appointments
 */
async function loadAppointments() {
    const status = document.getElementById('appointmentStatusFilter').value;
    const statusParam = status ? `&status=${status}` : '';
    
    try {
        const response = await fetch(`../api/appointments.php?action=get_user_appointments${statusParam}`);
        const data = await response.json();
        
        const container = document.getElementById('appointmentsList');
        
        if (data.success && data.data.length > 0) {
            container.innerHTML = data.data.map(appointment => `
                <div class="appointment-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="row">
                                <div class="col-md-8">
                                    <h6 class="mb-1">د. ${appointment.doctor_name}</h6>
                                    <p class="text-muted mb-1">${appointment.specialty_name}</p>
                                    <p class="mb-1">
                                        <i class="bi bi-calendar me-1"></i>
                                        ${formatDate(appointment.appointment_date)}
                                        <i class="bi bi-clock me-1 ms-3"></i>
                                        ${formatTime(appointment.appointment_time)}
                                    </p>
                                    <p class="mb-1">
                                        <i class="bi bi-geo-alt me-1"></i>
                                        ${appointment.clinic_name}
                                    </p>
                                    ${appointment.symptoms ? `<p class="mb-1"><i class="bi bi-file-medical me-1"></i>${appointment.symptoms}</p>` : ''}
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="badge bg-${getStatusColor(appointment.status)} mb-2">
                                        ${getStatusText(appointment.status)}
                                    </span>
                                    <br>
                                    <small class="text-muted d-block mb-2">
                                        رمز التأكيد: ${appointment.confirmation_code}
                                    </small>
                                    <small class="text-muted d-block mb-2">
                                        الرسوم: ${appointment.total_fee} ريال
                                    </small>
                                    ${appointment.insurance_name ? `<small class="text-muted d-block">التأمين: ${appointment.insurance_name}</small>` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    ${appointment.status === 'pending' || appointment.status === 'confirmed' ? `
                        <div class="mt-3 pt-3 border-top">
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary" onclick="rescheduleAppointment(${appointment.id})">
                                    <i class="bi bi-calendar-event me-1"></i>إعادة جدولة
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="cancelAppointment(${appointment.id})">
                                    <i class="bi bi-x-circle me-1"></i>إلغاء
                                </button>
                            </div>
                        </div>
                    ` : ''}
                </div>
            `).join('');
        } else {
            container.innerHTML = `
                <div class="text-center py-4">
                    <i class="bi bi-calendar-x text-muted fs-1"></i>
                    <p class="text-muted mt-2">لا توجد مواعيد</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading appointments:', error);
        document.getElementById('appointmentsList').innerHTML = `
            <div class="alert alert-danger">
                حدث خطأ في تحميل المواعيد
            </div>
        `;
    }
}

/**
 * Load specialties
 */
async function loadSpecialties() {
    try {
        const response = await fetch('../api/doctors.php?action=get_specialties');
        const data = await response.json();
        
        if (data.success) {
            specialties = data.data;
            const select = document.getElementById('specialtyFilter');
            select.innerHTML = '<option value="">جميع التخصصات</option>' +
                data.data.map(specialty => 
                    `<option value="${specialty.id}">${specialty.name_ar}</option>`
                ).join('');
        }
    } catch (error) {
        console.error('Error loading specialties:', error);
    }
}

/**
 * Load insurance companies
 */
async function loadInsuranceCompanies() {
    try {
        const response = await fetch('../api/doctors.php?action=get_insurance_companies');
        const data = await response.json();
        
        if (data.success) {
            insuranceCompanies = data.data;
            const select = document.getElementById('insuranceFilter');
            select.innerHTML = '<option value="">بدون تأمين</option>' +
                data.data.map(company => 
                    `<option value="${company.id}">${company.name_ar}</option>`
                ).join('');
        }
    } catch (error) {
        console.error('Error loading insurance companies:', error);
    }
}

/**
 * Load cities
 */
async function loadCities() {
    try {
        const response = await fetch('../api/doctors.php?action=get_cities');
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('cityFilter');
            select.innerHTML = '<option value="">جميع المدن</option>' +
                data.data.map(city => 
                    `<option value="${city}">${city}</option>`
                ).join('');
        }
    } catch (error) {
        console.error('Error loading cities:', error);
    }
}

/**
 * Search doctors
 */
async function searchDoctors() {
    const specialty = document.getElementById('specialtyFilter').value;
    const city = document.getElementById('cityFilter').value;
    const insurance = document.getElementById('insuranceFilter').value;
    const search = document.getElementById('doctorSearch').value;
    
    const params = new URLSearchParams();
    if (specialty) params.append('specialty_id', specialty);
    if (city) params.append('city', city);
    if (insurance) params.append('insurance_id', insurance);
    if (search) params.append('search', search);
    
    try {
        const response = await fetch(`../api/doctors.php?action=search&${params.toString()}`);
        const data = await response.json();
        
        const container = document.getElementById('doctorsList');
        
        if (data.success && data.data.length > 0) {
            container.innerHTML = data.data.map(doctor => `
                <div class="stats-card mb-3">
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center">
                            <img src="${doctor.profile_image || 'https://via.placeholder.com/100'}" 
                                 alt="د. ${doctor.first_name} ${doctor.last_name}" 
                                 class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
                        </div>
                        <div class="col-md-6">
                            <h5 class="mb-1 text-primary">د. ${doctor.first_name} ${doctor.last_name}</h5>
                            <p class="mb-1">${doctor.specialty_name}</p>
                            <p class="mb-1">
                                <i class="bi bi-geo-alt me-1"></i>${doctor.city || 'غير محدد'}
                            </p>
                            <p class="mb-1">
                                <i class="bi bi-star-fill text-warning me-1"></i>
                                ${doctor.avg_rating || 0} (${doctor.review_count || 0} تقييم)
                            </p>
                            <p class="mb-0">
                                <i class="bi bi-clock me-1"></i>
                                ${doctor.experience_years} سنوات خبرة
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <p class="mb-2">
                                <strong>${doctor.consultation_fee} ريال</strong>
                                <br>
                                <small class="text-muted">رسوم الاستشارة</small>
                            </p>
                            <button class="btn btn-primary" onclick="selectDoctor(${doctor.id}, '${doctor.first_name} ${doctor.last_name}', ${doctor.consultation_fee})">
                                <i class="bi bi-calendar-plus me-1"></i>حجز موعد
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = `
                <div class="text-center py-4">
                    <i class="bi bi-search text-muted fs-1"></i>
                    <p class="text-muted mt-2">لم يتم العثور على أطباء</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error searching doctors:', error);
        document.getElementById('doctorsList').innerHTML = `
            <div class="alert alert-danger">
                حدث خطأ في البحث
            </div>
        `;
    }
}

/**
 * Load featured doctors
 */
async function loadFeaturedDoctors() {
    try {
        const response = await fetch('../api/doctors.php?action=get_featured');
        const data = await response.json();
        
        if (data.success && data.data.length > 0) {
            const container = document.getElementById('doctorsList');
            container.innerHTML = `
                <div class="mb-4">
                    <h5 class="mb-3">الأطباء المميزون</h5>
                    <div class="row">
                        ${data.data.map(doctor => `
                            <div class="col-md-6 mb-3">
                                <div class="stats-card h-100">
                                    <div class="text-center mb-3">
                                        <img src="${doctor.profile_image || 'https://via.placeholder.com/80'}" 
                                             alt="د. ${doctor.first_name} ${doctor.last_name}" 
                                             class="rounded-circle mb-2" style="width: 60px; height: 60px; object-fit: cover;">
                                        <h6 class="mb-1 text-primary">د. ${doctor.first_name} ${doctor.last_name}</h6>
                                        <p class="text-muted mb-2">${doctor.specialty_name}</p>
                                        <p class="mb-2">
                                            <i class="bi bi-star-fill text-warning me-1"></i>
                                            ${doctor.rating || 0}
                                        </p>
                                        <p class="mb-2"><strong>${doctor.consultation_fee} ريال</strong></p>
                                        <button class="btn btn-primary btn-sm" onclick="selectDoctor(${doctor.id}, '${doctor.first_name} ${doctor.last_name}', ${doctor.consultation_fee})">
                                            حجز موعد
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading featured doctors:', error);
    }
}

/**
 * Select doctor for booking
 */
function selectDoctor(doctorId, doctorName, fee) {
    selectedDoctor = { id: doctorId, name: doctorName, fee: fee };
    
    // Show booking modal
    const modal = new bootstrap.Modal(document.getElementById('bookingModal'));
    modal.show();
    
    // Update modal title
    document.querySelector('#bookingModal .modal-title').textContent = `حجز موعد مع د. ${doctorName}`;
}

/**
 * Load available time slots
 */
async function loadAvailableTimeSlots() {
    const date = document.getElementById('appointmentDate').value;
    const container = document.getElementById('availableTimeSlots');
    
    if (!date || !selectedDoctor) {
        container.innerHTML = '<p class="text-muted">اختر التاريخ أولاً</p>';
        return;
    }
    
    container.innerHTML = '<div class="spinner-border spinner-border-sm"></div> جاري التحميل...';
    
    try {
        const response = await fetch(`../api/doctors.php?action=get_availability&doctor_id=${selectedDoctor.id}&date=${date}`);
        const data = await response.json();
        
        if (data.success && data.data.length > 0) {
            container.innerHTML = data.data.map(slot => `
                <button class="btn btn-outline-primary btn-sm me-2 mb-2 time-slot-btn" 
                        onclick="selectTimeSlot('${slot.time}', this)">
                    ${slot.display_time}
                </button>
            `).join('');
        } else {
            container.innerHTML = '<p class="text-muted">لا توجد مواعيد متاحة في هذا التاريخ</p>';
        }
    } catch (error) {
        console.error('Error loading time slots:', error);
        container.innerHTML = '<p class="text-danger">حدث خطأ في تحميل المواعيد المتاحة</p>';
    }
}

/**
 * Select time slot
 */
function selectTimeSlot(time, button) {
    // Remove active class from all buttons
    document.querySelectorAll('.time-slot-btn').forEach(btn => {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-outline-primary');
    });
    
    // Add active class to selected button
    button.classList.remove('btn-outline-primary');
    button.classList.add('btn-primary');
    
    selectedTimeSlot = time;
    
    // Show step 2
    document.getElementById('bookingStep2').classList.remove('d-none');
}

/**
 * Book appointment
 */
async function bookAppointment() {
    if (!selectedDoctor || !selectedTimeSlot) {
        alert('يرجى اختيار الطبيب والوقت المناسب');
        return;
    }
    
    const date = document.getElementById('appointmentDate').value;
    const type = document.getElementById('appointmentType').value;
    const symptoms = document.getElementById('symptoms').value;
    const notes = document.getElementById('appointmentNotes').value;
    const insurance = document.getElementById('insuranceFilter').value;
    
    const bookingData = {
        doctor_id: selectedDoctor.id,
        appointment_date: date,
        appointment_time: selectedTimeSlot,
        appointment_type: type,
        symptoms: symptoms,
        notes: notes,
        insurance_id: insurance || null,
        total_fee: selectedDoctor.fee
    };
    
    const button = document.getElementById('bookAppointmentBtn');
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري الحجز...';
    
    try {
        const response = await fetch('../api/appointments.php?action=book', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(bookingData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('bookingModal')).hide();
            
            // Show success message
            showAlert('success', `تم حجز الموعد بنجاح! رمز التأكيد: ${data.confirmation_code}`);
            
            // Refresh data
            loadDashboardStats();
            loadUpcomingAppointments();
            
            // Reset form
            resetBookingForm();
        } else {
            showAlert('danger', data.message);
        }
    } catch (error) {
        console.error('Error booking appointment:', error);
        showAlert('danger', 'حدث خطأ أثناء حجز الموعد');
    } finally {
        button.disabled = false;
        button.innerHTML = 'تأكيد الحجز';
    }
}

/**
 * Cancel appointment
 */
async function cancelAppointment(appointmentId) {
    if (!confirm('هل أنت متأكد من إلغاء هذا الموعد؟')) {
        return;
    }
    
    const reason = prompt('سبب الإلغاء (اختياري):');
    
    try {
        const response = await fetch('../api/appointments.php?action=cancel', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                appointment_id: appointmentId,
                reason: reason
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', data.message);
            loadAppointments();
            loadDashboardStats();
            loadUpcomingAppointments();
        } else {
            showAlert('danger', data.message);
        }
    } catch (error) {
        console.error('Error cancelling appointment:', error);
        showAlert('danger', 'حدث خطأ أثناء إلغاء الموعد');
    }
}

/**
 * Load notifications
 */
async function loadNotifications() {
    try {
        const response = await fetch('../api/notifications.php?action=get_notifications');
        const data = await response.json();
        
        const container = document.getElementById('notificationsList');
        
        if (data.success && data.data.length > 0) {
            container.innerHTML = data.data.map(notification => `
                <div class="stats-card ${notification.is_read ? '' : 'border-primary'} mb-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${notification.title}</h6>
                            <p class="mb-2">${notification.message}</p>
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i>
                                ${formatDateTime(notification.created_at)}
                            </small>
                        </div>
                        <div>
                            ${!notification.is_read ? `
                                <button class="btn btn-sm btn-outline-primary" onclick="markNotificationRead(${notification.id})">
                                    <i class="bi bi-check"></i>
                                </button>
                            ` : ''}
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteNotification(${notification.id})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = `
                <div class="text-center py-4">
                    <i class="bi bi-bell-slash text-muted fs-1"></i>
                    <p class="text-muted mt-2">لا توجد إشعارات</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
    }
}

/**
 * Load user profile
 */
async function loadUserProfile() {
    try {
        const response = await fetch(`../api/users.php?action=get_profile&user_id=${currentUser.user_id}`);
        const data = await response.json();
        
        if (data.success) {
            const profile = data.data;
            document.getElementById('firstName').value = profile.first_name || '';
            document.getElementById('lastName').value = profile.last_name || '';
            document.getElementById('profileEmail').value = profile.email || '';
            document.getElementById('phone').value = profile.phone || '';
            document.getElementById('dateOfBirth').value = profile.date_of_birth || '';
            document.getElementById('profileCity').value = profile.city || '';
            document.getElementById('address').value = profile.address || '';
        }
    } catch (error) {
        console.error('Error loading profile:', error);
    }
}

/**
 * Update profile
 */
async function updateProfile(e) {
    e.preventDefault();
    
    const formData = {
        first_name: document.getElementById('firstName').value,
        last_name: document.getElementById('lastName').value,
        phone: document.getElementById('phone').value,
        date_of_birth: document.getElementById('dateOfBirth').value,
        city: document.getElementById('profileCity').value,
        address: document.getElementById('address').value
    };
    
    try {
        const response = await fetch('../api/auth.php?action=update_profile', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', data.message);
            
            // Update current user data
            currentUser.name = `${formData.first_name} ${formData.last_name}`;
            localStorage.setItem('user_data', JSON.stringify(currentUser));
            document.getElementById('userWelcome').textContent = `مرحباً ${currentUser.name}`;
        } else {
            showAlert('danger', data.message);
        }
    } catch (error) {
        console.error('Error updating profile:', error);
        showAlert('danger', 'حدث خطأ أثناء تحديث الملف الشخصي');
    }
}

/**
 * Logout
 */
async function logout() {
    if (!confirm('هل أنت متأكد من تسجيل الخروج؟')) {
        return;
    }
    
    try {
        await fetch('../api/auth.php?action=logout', { method: 'POST' });
    } catch (error) {
        console.error('Error during logout:', error);
    } finally {
        localStorage.removeItem('user_data');
        window.location.href = '../login.html';
    }
}

/**
 * Utility functions
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ar-SA');
}

function formatTime(timeString) {
    const time = new Date(`2000-01-01T${timeString}`);
    return time.toLocaleTimeString('ar-SA', { 
        hour: '2-digit', 
        minute: '2-digit',
        hour12: true 
    });
}

function formatDateTime(dateTimeString) {
    const date = new Date(dateTimeString);
    return date.toLocaleString('ar-SA');
}

function getStatusColor(status) {
    const colors = {
        'pending': 'warning',
        'confirmed': 'info',
        'completed': 'success',
        'cancelled': 'danger',
        'no_show': 'secondary'
    };
    return colors[status] || 'secondary';
}

function getStatusText(status) {
    const texts = {
        'pending': 'في الانتظار',
        'confirmed': 'مؤكد',
        'completed': 'مكتمل',
        'cancelled': 'ملغي',
        'no_show': 'لم يحضر'
    };
    return texts[status] || status;
}

function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Insert at the top of the main content
    const mainContent = document.querySelector('.main-content .p-4');
    mainContent.insertAdjacentHTML('afterbegin', alertHtml);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        const alert = mainContent.querySelector('.alert');
        if (alert) {
            alert.remove();
        }
    }, 5000);
}

function resetBookingForm() {
    document.getElementById('appointmentDate').value = '';
    document.getElementById('appointmentType').value = 'consultation';
    document.getElementById('symptoms').value = '';
    document.getElementById('appointmentNotes').value = '';
    document.getElementById('availableTimeSlots').innerHTML = '<p class="text-muted">اختر التاريخ أولاً</p>';
    document.getElementById('bookingStep2').classList.add('d-none');
    selectedDoctor = null;
    selectedTimeSlot = null;
}