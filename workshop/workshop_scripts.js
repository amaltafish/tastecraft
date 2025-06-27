// workshop/workshop_scripts.js - JavaScript לפרטי סדנה

document.addEventListener('DOMContentLoaded', function() {
    // הסתרת הודעות הצלחה אחרי 5 שניות
    hideMessagesAfterDelay();
    
    // ספירה לאחור עבור התראות דחופות
    initUrgentCountdown();
    
    // אישור לפני הצטרפות לרשימת המתנה
    initWaitlistConfirmation();
    
    // הצגה/הסתרה של טופס עריכת חוות דעת
    initReviewFormHandlers();
});

function hideMessagesAfterDelay() {
    const messages = [
        document.getElementById('successMessage'),
        document.getElementById('reviewAddedMessage'),
        document.getElementById('reviewUpdatedMessage'),
        document.getElementById('waitlistMessage')
    ];
    
    messages.forEach(message => {
        if (message && message.style.display !== 'none') {
            setTimeout(function() {
                message.style.display = 'none';
            }, 5000);
        }
    });
}

function initUrgentCountdown() {
    const urgentCountdown = document.getElementById('urgent-countdown');
    if (urgentCountdown) {
        const hours = parseFloat(urgentCountdown.dataset.hours);
        let totalMinutes = Math.floor(hours * 60);
        
        const countdownTimer = urgentCountdown.querySelector('.countdown-timer');
        
        function updateCountdown() {
            if (totalMinutes <= 0) {
                urgentCountdown.innerHTML = '⏰ פג תוקף ההתראה!';
                setTimeout(() => location.reload(), 3000);
                return;
            }
            
            const displayHours = Math.floor(totalMinutes / 60);
            const displayMinutes = totalMinutes % 60;
            
            let timeString = '';
            if (displayHours > 0) {
                timeString = displayHours + ' שעות ו-' + displayMinutes + ' דקות';
            } else {
                timeString = displayMinutes + ' דקות';
            }
            
            countdownTimer.textContent = timeString;
            totalMinutes--;
        }
        
        updateCountdown();
        setInterval(updateCountdown, 60000); // עדכון כל דקה
    }
}

function initWaitlistConfirmation() {
    const waitlistButton = document.querySelector('button[name="joinWaitlist"]');
    if (waitlistButton) {
        waitlistButton.addEventListener('click', function(e) {
            const confirmMessage = `האם אתה בטוח שברצונך להצטרף לרשימת ההמתנה?

🔔 איך זה עובד:
• תקבל אימייל והתראה כשיתפנה מקום
• יהיו לך 24 שעות בדיוק לאשר
• אם לא תאשר בזמן, המקום יועבר הלאה
• המקום יהיה נעול עבורך למשך 24 השעות

האם להמשיך?`;

            if (!confirm(confirmMessage)) {
                e.preventDefault();
            }
        });
    }
}

function initReviewFormHandlers() {
    // הצגת טופס עריכת חוות דעת
    window.showEditReviewForm = function() {
        const form = document.getElementById('edit-review-form');
        if (form) {
            form.style.display = 'block';
        }
    };
    
    // הסתרת טופס עריכת חוות דעת
    window.hideEditReviewForm = function() {
        const form = document.getElementById('edit-review-form');
        if (form) {
            form.style.display = 'none';
        }
    };
}

// התראות מיוחדות
function showSpecialAlerts() {
    // התראה למשתמשים שקיבלו הודעה
    const waitlistNotified = document.querySelector('.waitlist-info.urgent');
    if (waitlistNotified) {
        setTimeout(() => {
            if (confirm('🔔 יש לך התראה דחופה!\n\nיש לך זמן מוגבל לאשר השתתפות בסדנה.\nהאם לעבור לפרופיל כדי לאשר עכשיו?')) {
                window.location.href = 'profile.php';
            }
        }, 1000);
    }
}

// קריאה לפונקציה כשהדף נטען
document.addEventListener('DOMContentLoaded', showSpecialAlerts);