<?php
// admin.php - קובץ ראשי מקוצר
session_start();

// קבצים משותפים
require_once 'components/database.php';
require_once 'functions/waitlist_functions.php';
require_once 'admin/admin_functions.php';
require_once 'admin/admin_handlers.php';

// בדיקות בסיסיות
checkLogin();

// בדיקה אם המשתמש הוא מנהל
if (!isAdmin()) {
    header("Location: login.php");
    exit();
}

$admin = $_SESSION['Fname'];

// טיפול בפעולות POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = handleAdminActions($con);
    if ($result['redirect']) {
        header("Location: admin.php?" . http_build_query($result['params']));
        exit();
    }
}

// שליפת נתונים
$adminData = getAdminData($con);
$options = $adminData['options'];
$workshopsResult = $adminData['workshops'];
$workshopOptionsMap = $adminData['workshopOptionsMap'];
$waitlistResult = $adminData['waitlists'];
$refundsResult = $adminData['refunds'];
$dashboardStats = $adminData['dashboardStats'];

// הודעות
$successMessage = $_GET['success'] ?? null;
$errorMessage = $_GET['error'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - TasteCraft</title>
    <link rel="stylesheet" href="admin/admin_styles.css">
</head>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Quick fix for reopen buttons');
    
    // מצא את כל כפתורי הפתיחה מחדש
    const reopenButtons = document.querySelectorAll('.reopen-btn');
    console.log('Found buttons:', reopenButtons.length);
    
    reopenButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const workshopId = this.dataset.id;
            const workshopName = this.dataset.name;
            const currentDate = this.dataset.currentDate;
            
            const newDate = prompt(`פתיחה מחדש של: ${workshopName}\n\nהכנס תאריך חדש (YYYY-MM-DD HH:MM):`);
            
            if (newDate && confirm(`לפתוח מחדש עם תאריך ${newDate}?`)) {
                // יצירת טופס ושליחה
                const form = document.createElement('form');
                form.method = 'post';
                form.innerHTML = `
                    <input type="hidden" name="workshopId" value="${workshopId}">
                    <input type="hidden" name="newDate" value="${newDate}">
                    <input type="hidden" name="reopenWorkshop" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
});
</script>
<body>
    <!-- Navbar similar to home page -->
    <div class="navbar">
        <div class="left-links">
            <a href="home2.php">Home</a>
            <a href="workshop.php">Book Workshop</a>
            <a href="about.php">About</a>
            <a href="profile.php">Profile</a>
        </div>
        <div class="icons">
            <a href="cart.php" title="Cart">
                <img src="cart.jpg" alt="Cart">
            </a>
            <a href="logout.php" title="Logout">
                <img src="logout.jpg" alt="Logout">
            </a>
        </div>
    </div>

    <div class="header">
        <div class="welcome">Welcome, <?php echo $_SESSION['Fname']; ?> (Admin)</div>
    </div>
    
    <?php include 'components/alerts.php'; ?>
    
    <div class="container">
        <div class="tabs">
            <div class="tab active" data-tab="workshops">Manage Workshops</div>
            <div class="tab" data-tab="notifications">Notifications</div>
            <div class="tab" data-tab="users">Users</div>
            <div class="tab" data-tab="waitlist">רשימות המתנה מתקדמות</div>
            <div class="tab" data-tab="stats">Statistics</div>
        </div>
        
        <?php include 'admin/admin_workshops.php'; ?>
        <?php include 'admin/admin_notifications.php'; ?>
        <?php include 'admin/admin_waitlists.php'; ?>
    </div>
    
    <script src="admin/admin_scripts.js"></script>
</body>
</html>