// profile/profile_scripts.js - JavaScript לפרופיל

// Tab Handling
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            tab.classList.add('active');
            const tabId = tab.dataset.tab + '-tab';
            document.getElementById(tabId).classList.add('active');
        });
    });

    // ספירה לאחור מתקדמת עבור התראות 24 שעות
    initCountdowns();
    
    // רענון אוטומטי כל 5 דקות לעדכון סטטוסים
    setInterval(function() {
        const urgentTimers = document.querySelectorAll('.urgent-timer');
        if (urgentTimers.length > 0) {
            location.reload();
        }
    }, 300000); // כל 5 דקות
    
    // התראות חזותיות
    initVisualAlerts();
    
    // אישור פעולות
    initConfirmationHandlers();
});

function initCountdowns() {
    const countdowns = document.querySelectorAll('[id^="countdown-"]');
    
    countdowns.forEach(countdown => {
        const hours = parseInt(countdown.dataset.hours) || 0;
        const minutes = parseInt(countdown.dataset.minutes) || 0;
        const timeDisplay = countdown.querySelector('.time-display');
        
        if (!timeDisplay) return;
        
        let totalMinutes = (hours * 60) + minutes;
        
        function updateDisplay() {
            if (totalMinutes <= 0) {
                countdown.innerHTML = '<span style="color: #fff; font-weight: bold;">⏰ פג תוקף!</span>';
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
            
            timeDisplay.textContent = timeString;
        }
        
        // עדכון ראשוני
        updateDisplay();
        
        // עדכון כל דקה
        const timer = setInterval(() => {
            totalMinutes--;
            updateDisplay();
            
            if (totalMinutes <= 0) {
                clearInterval(timer);
            }
        }, 60000); // כל דקה
    });
}

function initVisualAlerts() {
    const urgentNotifications = document.querySelectorAll('.notification-urgent');
    
    if (urgentNotifications.length > 0) {
        // הצגת התראה כשנכנסים לדף
        setTimeout(() => {
            const urgentCount = urgentNotifications.length;
            const message = urgentCount === 1 ? 
                'יש לך התראה דחופה שדורשת תשומת לב!' : 
                `יש לך ${urgentCount} התראות דחופות שדורשות תשומת לב!`;
            
            if (confirm(message + '\n\nהאם לעבור לטאב ההתראות?')) {
                // מעבר לטאב התראות
                document.querySelector('.tab[data-tab="notifications"]').click();
            }
        }, 1000);
    }
}

function initConfirmationHandlers() {
    // אישור ביטול הרשמה
    const cancelButtons = document.querySelectorAll('button[name="cancelRegistration"]');
    cancelButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('האם אתה בטוח שברצונך לבטל את ההרשמה?\n\nתקבל החזר של 80% מהסכום ששילמת.\nהפעולה לא ניתנת לביטול.')) {
                e.preventDefault();
            }
        });
    });
    
    // אישור ביטול רשימת המתנה
    const waitlistCancelButtons = document.querySelectorAll('button[name="cancelWaitlist"]');
    waitlistCancelButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('האם אתה בטוח שברצונך להסיר את עצמך מרשימת ההמתנה?\n\nתאבד את מקומך ברשימה ולא תקבל עדכונים על סדנה זו.')) {
                e.preventDefault();
            }
        });
    });
    
    // אישור אישור השתתפות
    const confirmButtons = document.querySelectorAll('button[name="confirmWaitlistSpot"]');
    confirmButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('🎉 מעולה! האם אתה בטוח שברצונך לאשר את השתתפותך בסדנה?\n\nהסדנה תועבר לסל הקניות שלך והמקום יישמר עבורך.')) {
                e.preventDefault();
            }
        });
    });
    
    // אישור דחיית הזדמנות
    const declineButtons = document.querySelectorAll('button[name="declineWaitlistSpot"]');
    declineButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('האם אתה בטוח שברצונך לדחות את ההזדמנות?\n\nהמקום יועבר למשתמש הבא ברשימה.\nתישאר ברשימת ההמתנה לעדכונים עתידיים.')) {
                e.preventDefault();
            }
        });
    });
}

// פונקציות חוות דעת
function showReviewForm(workshopId) {
    document.getElementById('review-form-' + workshopId).style.display = 'block';
}

function hideReviewForm(workshopId) {
    document.getElementById('review-form-' + workshopId).style.display = 'none';
}