// Enhanced main page script
document.addEventListener('DOMContentLoaded', function() {
    const titleElement = document.getElementById('typewriter-text');
    const descriptionElement = document.querySelector('.hero-description');
    const textToType = "صحتك تبدأ من هنا";
    let index = 0;

    function type() {
        // إضافة كلاس المؤشر الوامض عند بدء الكتابة
        if (index === 0) {
            titleElement.classList.add('typing');
        }

        if (index < textToType.length) {
            titleElement.textContent += textToType.charAt(index);
            index++;
            setTimeout(type, 120); // سرعة الكتابة
        } else {
            // عند انتهاء الكتابة:
            // 1. إيقاف وميض المؤشر
            titleElement.classList.remove('typing');
            // 2. جعل المؤشر ثابتًا
            titleElement.style.borderLeftColor = 'rgba(255, 255, 255, 0.7)';
            // 3. إظهار الوصف بسلاسة
            descriptionElement.classList.add('visible');
        }
    }

    // تأخير بدء الكتابة
    setTimeout(type, 500);
    
    // Check if user is already logged in
    checkUserSession();
    
    // Load specialties for search modal
    loadSpecialtiesForSearch();
});

/**
 * Check if user is logged in and update UI accordingly
 */
function checkUserSession() {
    const userData = localStorage.getItem('user_data');
    if (userData) {
        const user = JSON.parse(userData);
        
        // Update auth icons to show user menu
        const authIcons = document.querySelector('.top-auth-icons');
        authIcons.innerHTML = `
            <div class="dropdown">
                <button class="btn btn-link auth-icon dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><h6 class="dropdown-header">مرحباً ${user.name}</h6></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="dashboard/${user.role}.html">
                        <i class="bi bi-grid-1x2-fill me-2"></i>لوحة التحكم
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="logout()">
                        <i class="bi bi-box-arrow-right me-2"></i>تسجيل الخروج
                    </a></li>
                </ul>
            </div>
        `;
        
        // Update main button text
        const mainBtn = document.querySelector('.btn-booking');
        if (mainBtn) {
            mainBtn.textContent = 'احجز موعدك الآن';
            mainBtn.onclick = function() {
                window.location.href = `dashboard/${user.role}.html`;
            };
        }
    }
}

/**
 * Load specialties for search modal
 */
async function loadSpecialtiesForSearch() {
    try {
        const response = await fetch('api/doctors.php?action=get_specialties');
        const data = await response.json();
        
        if (data.success) {
            // This would be used if we had a search dropdown in the modal
            console.log('Specialties loaded:', data.data.length);
        }
    } catch (error) {
        console.error('Error loading specialties:', error);
    }
}

/**
 * Logout function
 */
async function logout() {
    if (!confirm('هل أنت متأكد من تسجيل الخروج؟')) {
        return;
    }
    
    try {
        await fetch('api/auth.php?action=logout', { method: 'POST' });
    } catch (error) {
        console.error('Error during logout:', error);
    } finally {
        localStorage.removeItem('user_data');
        location.reload();
    }
}
