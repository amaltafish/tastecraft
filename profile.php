<?php
// profile.php - קובץ ראשי מקוצר
session_start();

// קבצים משותפים
require_once 'components/database.php';
require_once 'functions/waitlist_functions.php';
require_once 'profile/profile_functions.php';
require_once 'profile/profile_handlers.php';

// בדיקות בסיסיות
checkLogin();
$userId = $_SESSION['id'];
$isAdmin = isAdmin();

// טיפול בפעולות POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = handleProfileActions($con, $userId);
    if ($result['redirect']) {
        header("Location: profile.php?" . http_build_query($result['params']));
        exit();
    }
}

// שליפת נתונים
$profileData = getProfileData($con, $userId);
$userData = $profileData['user'];
$registrations = $profileData['registrations'];
$waitlistResult = $profileData['waitlists'];
$notificationsArray = $profileData['notifications'];
$urgentCount = $profileData['urgentCount'];
$reviews = $profileData['reviews'];

// הודעות
$successMessage = $_GET['success'] ?? null;
$errorMessage = $_GET['error'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>הפרופיל שלי - TasteCraft</title>
    <link rel="stylesheet" href="profile/profile_styles.css">
</head>
<body>
    <?php include 'components/navbar.php'; ?>

    <div class="container">
        <h1 class="page-title">הפרופיל שלי</h1>
        
        <?php include 'components/alerts.php'; ?>
        
        <?php include 'profile/profile_tabs.php'; ?>
    </div>
    
    <?php include 'components/footer.php'; ?>
    
    <script src="profile/profile_scripts.js"></script>
</body>
</html>