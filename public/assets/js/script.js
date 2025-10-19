// script.js
document.addEventListener('DOMContentLoaded', function() {
    const titleElement = document.getElementById('typewriter-text');
    const descriptionElement = document.querySelector('.hero-description');
    const textToType = "صحتك تبدأ من هنا";
    let index = 0;

    function type() {
        // إضافة كلاس المؤشر الوامض عند بدء الكتابة
        if (index === 0) {
            titleElement.classList.add('blinking-cursor');
        }

        if (index < textToType.length) {
            titleElement.textContent += textToType.charAt(index);
            index++;
            setTimeout(type, 120); // سرعة الكتابة
        } else {
            // عند انتهاء الكتابة:
            // 1. إيقاف وميض المؤشر
            titleElement.classList.remove('blinking-cursor');
            // 2. جعل المؤشر ثابتًا
            titleElement.style.borderLeftColor = 'rgba(255, 255, 255, 0.7)';
            // 3. إظهار الوصف بسلاسة
            descriptionElement.classList.add('visible');
        }
    }

    // تأخير بدء الكتابة
    setTimeout(type, 500); 
});
