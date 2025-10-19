// script.js - Medical Appointment System
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the application
    initializeApp();
    
    // Typewriter effect for hero section
    initializeTypewriter();
    
    // Initialize authentication
    initializeAuth();
    
    // Initialize search functionality
    initializeSearch();
    
    // Initialize notifications
    initializeNotifications();
});

// Initialize the application
function initializeApp() {
    // Check if user is logged in
    checkAuthStatus();
    
    // Set up global error handling
    window.addEventListener('unhandledrejection', function(event) {
        console.error('Unhandled promise rejection:', event.reason);
        showNotification('حدث خطأ غير متوقع', 'error');
    });
}

// Typewriter effect
function initializeTypewriter() {
    const titleElement = document.getElementById('typewriter-text');
    if (!titleElement) return;
    
    const descriptionElement = document.querySelector('.hero-description');
    const textToType = "صحتك تبدأ من هنا";
    let index = 0;

    function type() {
        if (index === 0) {
            titleElement.classList.add('typing');
        }

        if (index < textToType.length) {
            titleElement.textContent += textToType.charAt(index);
            index++;
            setTimeout(type, 120);
        } else {
            titleElement.classList.remove('typing');
            titleElement.style.borderLeftColor = 'rgba(255, 255, 255, 0.7)';
            if (descriptionElement) {
                descriptionElement.classList.add('visible');
            }
        }
    }

    setTimeout(type, 500);
}

// Authentication functions
function initializeAuth() {
    // Handle login form
    const loginForm = document.querySelector('form');
    if (loginForm && window.location.pathname.includes('login.html')) {
        loginForm.addEventListener('submit', handleLogin);
    }
    
    // Handle registration form
    if (loginForm && window.location.pathname.includes('register.html')) {
        loginForm.addEventListener('submit', handleRegister);
    }
}

async function handleLogin(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const loginData = {
        email: formData.get('email') || document.getElementById('floatingInput')?.value,
        password: formData.get('password') || document.getElementById('floatingPassword')?.value
    };
    
    try {
        const response = await fetch('/api/auth.php?action=login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(loginData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('تم تسجيل الدخول بنجاح', 'success');
            setTimeout(() => {
                window.location.href = '/dashboard.html';
            }, 1500);
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        console.error('Login error:', error);
        showNotification('حدث خطأ في تسجيل الدخول', 'error');
    }
}

async function handleRegister(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const registerData = {
        full_name: formData.get('full_name') || document.getElementById('floatingFullName')?.value,
        email: formData.get('email') || document.getElementById('floatingInput')?.value,
        password: formData.get('password') || document.getElementById('floatingPassword')?.value,
        confirm_password: formData.get('confirm_password') || document.getElementById('floatingConfirmPassword')?.value,
        gender: formData.get('gender') || 'male'
    };
    
    // Validate password confirmation
    if (registerData.password !== registerData.confirm_password) {
        showNotification('كلمة المرور وتأكيدها غير متطابقين', 'error');
        return;
    }
    
    try {
        const response = await fetch('/api/auth.php?action=register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(registerData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('تم إنشاء الحساب بنجاح', 'success');
            setTimeout(() => {
                window.location.href = '/login.html';
            }, 1500);
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        console.error('Registration error:', error);
        showNotification('حدث خطأ في إنشاء الحساب', 'error');
    }
}

async function checkAuthStatus() {
    try {
        const response = await fetch('/api/auth.php?action=check_auth');
        const result = await response.json();
        
        if (result.success && result.authenticated) {
            updateUIForLoggedInUser(result.user);
        } else {
            updateUIForGuest();
        }
    } catch (error) {
        console.error('Auth check error:', error);
        updateUIForGuest();
    }
}

function updateUIForLoggedInUser(user) {
    // Update navigation to show user menu
    const authIcons = document.querySelector('.top-auth-icons');
    if (authIcons) {
        authIcons.innerHTML = `
            <div class="dropdown">
                <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i> ${user.name}
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="/dashboard.html"><i class="bi bi-house"></i> لوحة التحكم</a></li>
                    <li><a class="dropdown-item" href="/appointments.html"><i class="bi bi-calendar-check"></i> مواعيدي</a></li>
                    <li><a class="dropdown-item" href="/profile.html"><i class="bi bi-person"></i> الملف الشخصي</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" onclick="logout()"><i class="bi bi-box-arrow-right"></i> تسجيل الخروج</a></li>
                </ul>
            </div>
        `;
    }
}

function updateUIForGuest() {
    // Keep the original auth icons for guest users
    const authIcons = document.querySelector('.top-auth-icons');
    if (authIcons && !authIcons.querySelector('.dropdown')) {
        // Already set up for guests
    }
}

async function logout() {
    try {
        const response = await fetch('/api/auth.php?action=logout', {
            method: 'POST'
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('تم تسجيل الخروج بنجاح', 'success');
            setTimeout(() => {
                window.location.href = '/index.html';
            }, 1000);
        }
    } catch (error) {
        console.error('Logout error:', error);
        showNotification('حدث خطأ في تسجيل الخروج', 'error');
    }
}

// Search functionality
function initializeSearch() {
    const searchForm = document.querySelector('#searchModal form');
    if (searchForm) {
        searchForm.addEventListener('submit', handleSearch);
    }
}

async function handleSearch(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const searchData = {
        search: formData.get('doctorName') || document.getElementById('doctorName')?.value,
        location: formData.get('location') || document.getElementById('location')?.value,
        insurance: formData.get('insurance') || document.getElementById('insurance')?.value
    };
    
    // Build query string
    const params = new URLSearchParams();
    if (searchData.search) params.append('search', searchData.search);
    if (searchData.location) params.append('city', searchData.location);
    if (searchData.insurance && searchData.insurance !== 'جميع الشركات') {
        params.append('insurance', searchData.insurance);
    }
    
    // Redirect to search results
    window.location.href = `/search-results.html?${params.toString()}`;
}

// Notification system
function initializeNotifications() {
    // Check for unread notifications every 30 seconds
    setInterval(checkUnreadNotifications, 30000);
    
    // Check on page load
    checkUnreadNotifications();
}

async function checkUnreadNotifications() {
    try {
        const response = await fetch('/api/notifications.php?action=unread_count');
        const result = await response.json();
        
        if (result.success) {
            updateNotificationBadge(result.unread_count);
        }
    } catch (error) {
        console.error('Notification check error:', error);
    }
}

function updateNotificationBadge(count) {
    let badge = document.querySelector('.notification-badge');
    if (!badge) {
        // Create notification badge if it doesn't exist
        const authIcons = document.querySelector('.top-auth-icons');
        if (authIcons) {
            const notificationIcon = document.createElement('a');
            notificationIcon.href = '/notifications.html';
            notificationIcon.className = 'auth-icon position-relative';
            notificationIcon.title = 'الإشعارات';
            notificationIcon.innerHTML = '<i class="bi bi-bell"></i>';
            
            badge = document.createElement('span');
            badge.className = 'notification-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
            notificationIcon.appendChild(badge);
            
            authIcons.insertBefore(notificationIcon, authIcons.firstChild);
        }
    }
    
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    }
}

// Utility functions
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// API helper functions
async function apiCall(endpoint, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        }
    };
    
    if (data) {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(endpoint, options);
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.message || 'حدث خطأ في الطلب');
        }
        
        return result;
    } catch (error) {
        console.error('API call error:', error);
        throw error;
    }
}
