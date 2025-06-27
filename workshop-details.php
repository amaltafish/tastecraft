<?php
// workshop-details.php - קובץ ראשי מקוצר
session_start();

// קבצים משותפים
require_once 'components/database.php';
require_once 'functions/waitlist_functions.php';
require_once 'workshop/workshop_functions.php';
require_once 'workshop/workshop_handlers.php';

// בדיקות בסיסיות
checkLogin();
$userId = $_SESSION['id'];
$isAdmin = isAdmin();

// בדיקה אם יש מזהה סדנה
if (!isset($_GET['workshopId'])) {
    header("Location: workshop.php");
    exit();
}

$workshopId = $_GET['workshopId'];

// טיפול בפעולות POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = handleWorkshopActions($con, $userId, $workshopId);
    if ($result['redirect']) {
        header("Location: workshop-details.php?workshopId=$workshopId&" . http_build_query($result['params']));
        exit();
    }
}

// שליפת נתונים
$workshopData = getWorkshopData($con, $workshopId, $userId);

if (!$workshopData) {
    header("Location: workshop.php");
    exit();
}

$workshop = $workshopData['workshop'];
$availableSeats = $workshopData['availableSeats'];
$allergies = $workshopData['allergies'];
$reviewsResult = $workshopData['reviews'];
$avgRating = $workshopData['avgRating'];
$isRegistered = $workshopData['isRegistered'];
$isInCart = $workshopData['isInCart'];
$hasReview = $workshopData['hasReview'];
$userReview = $workshopData['userReview'];
$isPastWorkshop = $workshopData['isPastWorkshop'];
$isInWaitlist = $workshopData['isInWaitlist'];
$waitlistData = $workshopData['waitlistData'];
$waitlistStatus = $workshopData['waitlistStatus'];
$hoursRemaining = $workshopData['hoursRemaining'];
$minutesRemaining = $workshopData['minutesRemaining'];
$waitlistPosition = $workshopData['waitlistPosition'];
$userHas24hNotification = $workshopData['userHas24hNotification'];
$notification24hData = $workshopData['notification24hData'];
$hoursRemaining24h = $workshopData['hoursRemaining24h'] ?? null;

// הודעות
$successMessage = $_GET['success'] ?? null;
$errorMessage = $_GET['error'] ?? null;

// Get workshop details and waitlist status
$seats = getAvailableSeats($con, $workshopId);
$hasAvailableSeats = $seats['available'] > 0;
$isNotLocked = $seats['locked'] == 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $workshop['workshopName']; ?> - TasteCraft</title>
    <link rel="stylesheet" href="workshop/workshop_styles.css">
</head>
<body>
    <?php include 'components/navbar.php'; ?>

    <div class="container">
        <?php include 'components/alerts.php'; ?>
        <?php include 'workshop/workshop_content.php'; ?>
    </div>
    
    <?php include 'components/footer.php'; ?>
    
    <script src="workshop/workshop_scripts.js"></script>
    
    <?php if ($userHas24hNotification && isset($hoursRemaining24h) && $hoursRemaining24h > 0): ?>
        <script>
            // התראה למשתמשים עם התראה 24 שעות
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(() => {
                    if (confirm('🎉 יש לך הזדמנות מיוחדת!\n\nמקום התפנה בסדנה זו ואתה הבא ברשימה!\nיש לך <?php echo floor($hoursRemaining24h); ?> שעות לאשר.\n\nהאם לעבור לפרופיל כדי לאשר עכשיו?')) {
                        window.location.href = 'profile.php';
                    }
                }, 500);
            });
        </script>
    <?php endif; ?>
</body>
</html>

<?php if (!$isRegistered && !$isInCart && !$isPastWorkshop && $hasAvailableSeats && $isNotLocked): ?>
    <a href="cart.php?action=add&workshopId=<?php echo $workshopId; ?>" class="btn btn-primary">הוסף לסל</a>
<?php endif; ?>