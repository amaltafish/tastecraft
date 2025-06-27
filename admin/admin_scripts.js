// admin/admin_scripts.js - JavaScript לאדמין - מעודכן עם פתיחה מחדש

// Tab handling
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Remove 'active' class from all tabs
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // Add 'active' class to clicked tab
            tab.classList.add('active');
            const tabId = `${tab.dataset.tab}-tab`;
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    // Handle edit and delete buttons in table
    initWorkshopHandlers();
    
    // ייצוא טבלת החזרים לאקסל
    initExportHandlers();
    
    // Form validation
    initFormValidation();
    
    // רענון אוטומטי כל דקה לעדכון זמנים נותרים
    setInterval(function() {
        location.reload();
    }, 60000); // רענון כל דקה
});

function initWorkshopHandlers() {
    const editButtons = document.querySelectorAll('.edit-btn');
    const deleteButtons = document.querySelectorAll('.delete-btn');
    const updateWorkshopCard = document.getElementById('updateWorkshopCard');
    const deleteWorkshopCard = document.getElementById('deleteWorkshopCard');
    const cancelUpdateBtn = document.getElementById('cancelUpdateBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    
    // *** הוספה חדשה: טיפול בכפתור פתיחה מחדש ***
    const reopenButtons = document.querySelectorAll('.reopen-btn');
    const reopenWorkshopCard = document.getElementById('reopenWorkshopCard');
    const cancelReopenBtn = document.getElementById('cancelReopenBtn');
    const reopenForm = document.getElementById('reopenWorkshopForm');
    
    // Function to show edit form and fill with data
    editButtons.forEach(button => {
        button.addEventListener('click', () => {
            const workshopId = button.dataset.id;
            const tr = button.closest('tr');
            const workshopData = JSON.parse(tr.dataset.workshop);
            const workshopOptions = JSON.parse(tr.dataset.options);
            
            // Fill form with data
            document.getElementById('workshopIdUpdate').value = workshopData.workshopId;
            document.getElementById('workshopNameUpdate').value = workshopData.workshopName;
            document.getElementById('descriptionUpdate').value = workshopData.description;
            document.getElementById('dateUpdate').value = formatDateForInput(workshopData.date);
            document.getElementById('locationUpdate').value = workshopData.location;
            document.getElementById('priceUpdate').value = workshopData.price;
            document.getElementById('maxParticipantsUpdate').value = workshopData.maxParticipants;
            document.getElementById('imgUpdate').value = workshopData.img;
            
            // Check appropriate allergy checkboxes
            const checkboxes = document.querySelectorAll('#optionsUpdate input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = workshopOptions.includes(parseInt(checkbox.value));
            });
            
            // Show edit form
            updateWorkshopCard.classList.remove('hidden');
            updateWorkshopCard.classList.add('visible');
            
            // Scroll to edit form
            updateWorkshopCard.scrollIntoView({ behavior: 'smooth' });
        });
    });
    
    // Function to show delete confirmation
    deleteButtons.forEach(button => {
        button.addEventListener('click', () => {
            const workshopId = button.dataset.id;
            const workshopName = button.dataset.name;
            
            document.getElementById('workshopIdDelete').value = workshopId;
            document.getElementById('deleteWorkshopName').textContent = workshopName;
            
            // Show delete confirmation
            deleteWorkshopCard.classList.remove('hidden');
            deleteWorkshopCard.classList.add('visible');
            
            // Scroll to delete confirmation
            deleteWorkshopCard.scrollIntoView({ behavior: 'smooth' });
        });
    });
    
    // *** טיפול בלחיצה על כפתור "פתח מחדש" ***
    reopenButtons.forEach(button => {
        button.addEventListener('click', () => {
            const workshopId = button.dataset.id;
            const workshopName = button.dataset.name;
            const currentDate = button.dataset.currentDate;
            
            // מילוי פרטי הסדנה
            document.getElementById('workshopIdReopen').value = workshopId;
            document.getElementById('reopenWorkshopName').textContent = workshopName;
            document.getElementById('currentWorkshopDate').textContent = formatDate(currentDate);
            
            // הגדרת תאריך מינימלי - מחר
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const minDateTime = tomorrow.toISOString().slice(0, 16);
            document.getElementById('newDate').min = minDateTime;
            
            // הגדרת תאריך ברירת מחדל - שבוע קדימה
            const nextWeek = new Date();
            nextWeek.setDate(nextWeek.getDate() + 7);
            const defaultDateTime = nextWeek.toISOString().slice(0, 16);
            document.getElementById('newDate').value = defaultDateTime;
            
            // הצגת החלון
            reopenWorkshopCard.classList.remove('hidden');
            reopenWorkshopCard.classList.add('visible');
            reopenWorkshopCard.scrollIntoView({ behavior: 'smooth' });
        });
    });
    
    // Cancel buttons
    if (cancelUpdateBtn) {
        cancelUpdateBtn.addEventListener('click', () => {
            updateWorkshopCard.classList.add('hidden');
            updateWorkshopCard.classList.remove('visible');
        });
    }
    
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', () => {
            deleteWorkshopCard.classList.add('hidden');
            deleteWorkshopCard.classList.remove('visible');
        });
    }
    
    // *** טיפול בכפתור ביטול פתיחה מחדש ***
    if (cancelReopenBtn) {
        cancelReopenBtn.addEventListener('click', () => {
            reopenWorkshopCard.classList.add('hidden');
            reopenWorkshopCard.classList.remove('visible');
            
            // איפוס הטופס
            if (reopenForm) {
                reopenForm.reset();
            }
        });
    }
    
    // *** ולידציה לפני שליחת טופס פתיחה מחדש ***
    if (reopenForm) {
        reopenForm.addEventListener('submit', function(e) {
            const newDate = document.getElementById('newDate').value;
            const workshopName = document.getElementById('reopenWorkshopName').textContent;
            
            if (!newDate) {
                e.preventDefault();
                alert('חובה לבחור תאריך חדש לסדנה!');
                return;
            }
            
            // בדיקה שהתאריך בעתיד
            const selectedDate = new Date(newDate);
            const now = new Date();
            
            if (selectedDate <= now) {
                e.preventDefault();
                alert('התאריך החדש חייב להיות בעתיד!');
                return;
            }
            
            // אישור מהמשתמש
            const confirmMessage = `האם אתה בטוח שברצונך לפתוח מחדש את הסדנה "${workshopName}"?\n\n` +
                                  `תאריך חדש: ${formatDate(newDate)}\n\n` +
                                  `שים לב:\n` +
                                  `• ההרשמות הישנות יועברו לארכיון\n` +
                                  `• רשימות ההמתנה ינוקו\n` +
                                  `• הסדנה תחזור לסטטוס "עתידית"`;
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
            }
        });
    }
}

function initExportHandlers() {
    const exportRefundsBtn = document.getElementById('exportRefundsBtn');
    if (exportRefundsBtn) {
        exportRefundsBtn.addEventListener('click', function() {
            const table = document.getElementById('refunds-table');
            const html = table.outerHTML;
            const url = 'data:application/vnd.ms-excel;base64,' + btoa(html);
            const downloadLink = document.createElement("a");
            document.body.appendChild(downloadLink);
            downloadLink.href = url;
            downloadLink.download = 'refunds_report.xls';
            downloadLink.click();
            document.body.removeChild(downloadLink);
        });
    }
}

function initFormValidation() {
    const addForm = document.getElementById('addWorkshopForm');
    const updateForm = document.getElementById('updateWorkshopForm');
    const deleteForm = document.getElementById('deleteWorkshopForm');
    
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            if (!validateWorkshopForm('addWorkshopForm')) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    }
    
    if (updateForm) {
        updateForm.addEventListener('submit', function(e) {
            const workshopId = document.getElementById('workshopIdUpdate').value;
            if (!workshopId) {
                e.preventDefault();
                alert('No workshop selected for update.');
            }
        });
    }
    
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            const workshopName = document.getElementById('deleteWorkshopName').textContent;
            if (!confirm(`Are you sure you want to delete the workshop "${workshopName}"?\n\nThis action cannot be undone and will also delete all related registrations and notifications.`)) {
                e.preventDefault();
            }
        });
    }
}

// Form validation helper
function validateWorkshopForm(formId) {
    const form = document.getElementById(formId);
    const requiredFields = form.querySelectorAll('[required]');
    
    let isValid = true;
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.style.borderColor = '#dc3545';
            field.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.style.borderColor = '#ced4da';
                }
            });
        } else {
            field.style.borderColor = '#ced4da';
        }
    });
    
    return isValid;
}

// *** פונקציות עזר לעיצוב תאריכים ***
function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { 
        year: 'numeric', 
        month: '2-digit', 
        day: '2-digit', 
        hour: '2-digit', 
        minute: '2-digit' 
    };
    return date.toLocaleDateString('he-IL', options);
}

function formatDateForInput(dateString) {
    // המרת תאריך לפורמט datetime-local input
    const date = new Date(dateString);
    return date.toISOString().slice(0, 16);
}

// Notification form enhancements
document.addEventListener('DOMContentLoaded', function() {
    const notificationForm = document.getElementById('notificationForm');
    const notificationMessage = document.getElementById('notificationMessage');
    const workshopSelect = document.getElementById('workshopIdNotification');
    
    if (notificationForm) {
        notificationForm.addEventListener('submit', function(e) {
            if (notificationMessage.value.trim().length < 10) {
                e.preventDefault();
                alert('Notification message must be at least 10 characters long.');
                notificationMessage.focus();
            }
        });
    }
    
    // Auto-update message based on workshop selection
    if (workshopSelect && notificationMessage) {
        workshopSelect.addEventListener('change', function() {
            if (this.value && this.options[this.selectedIndex]) {
                const workshopName = this.options[this.selectedIndex].text;
                if (notificationMessage.value.trim() === '') {
                    notificationMessage.placeholder = `Enter notification message for ${workshopName}...`;
                }
            } else {
                notificationMessage.placeholder = 'Enter notification message for all users...';
            }
        });
    }
});

// Waitlist management
function confirmWaitlistAction(action, userName, workshopName) {
    let message = '';
    
    if (action === 'notify') {
        message = `Send notification to ${userName} for workshop "${workshopName}"?\n\nThis will lock a seat for 24 hours.`;
    } else if (action === 'remove') {
        message = `Remove ${userName} from the waitlist for workshop "${workshopName}"?\n\nThis action cannot be undone.`;
    }
    
    return confirm(message);
}

// Auto-refresh for time-sensitive data
function initAutoRefresh() {
    // Check if there are any time-remaining elements
    const timeElements = document.querySelectorAll('.time-remaining');
    
    if (timeElements.length > 0) {
        // Refresh every minute to update remaining times
        setInterval(() => {
            location.reload();
        }, 60000);
    }
}

// Initialize auto-refresh
document.addEventListener('DOMContentLoaded', initAutoRefresh);