<?php
session_start();
$con = new mysqli("localhost", "root", "", "tastecraft");

if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

// בדיקה אם המשתמש מחובר
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['id'];

// בדיקה אם המשתמש הוא מנהל
$isAdmin = isset($_SESSION['flag']) && $_SESSION['flag'] == 1;

// שליפת כל הסדנאות עם מספר הנרשמים ומקומות נעולים - תיקון
$sql = "SELECT w.*, 
        COUNT(DISTINCT r.registrationId) AS registeredCount,
        COUNT(DISTINCT CASE WHEN n.status = 'notified' AND n.type = 'waitlist' 
                            AND n.createdAt > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN n.id END) AS lockedSeats
        FROM workshops w
        LEFT JOIN registration r ON w.workshopId = r.workshopId
        LEFT JOIN notifications n ON w.workshopId = n.workshopId
        GROUP BY w.workshopId
        ORDER BY w.date ASC";

$stmt = $con->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

// שליפת האפשרויות (סינון לפי אלרגיות)
$optionsSql = "SELECT * FROM options";
$optionsResult = $con->query($optionsSql);

// אם קיימים פרמטרים של סינון
$filteredOptions = [];
$filteredQuery = "";

if (isset($_GET['filter']) && !empty($_GET['filter'])) {
    $filteredOptions = $_GET['filter'];
    
    // בניית שאילתת סינון עם מקומות נעולים - תיקון
    $placeholders = str_repeat('?,', count($filteredOptions) - 1) . '?';
    $filteredQuery = "SELECT DISTINCT w.*, 
        COUNT(DISTINCT r.registrationId) AS registeredCount,
        COUNT(DISTINCT CASE WHEN n.status = 'notified' AND n.type = 'waitlist' 
                            AND n.createdAt > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN n.id END) AS lockedSeats
        FROM workshops w
        LEFT JOIN registration r ON w.workshopId = r.workshopId
        LEFT JOIN notifications n ON w.workshopId = n.workshopId
        LEFT JOIN workshopOptions wo ON w.workshopId = wo.workshopId
        LEFT JOIN options o ON wo.optionId = o.optionId
        WHERE o.optionId IN ($placeholders)
        GROUP BY w.workshopId
        ORDER BY w.date ASC";
    
    $filteredStmt = $con->prepare($filteredQuery);
    $types = str_repeat('i', count($filteredOptions));
    $filteredStmt->bind_param($types, ...$filteredOptions);
    $filteredStmt->execute();
    $result = $filteredStmt->get_result();
}

// בדיקה אם יש סדנאות בסל הקניות
$cartItems = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

// הוספת סדנה לסל הקניות
if (isset($_GET['action']) && $_GET['action'] == 'add' && isset($_GET['workshopId'])) {
    $workshopId = $_GET['workshopId'];
    
    // בדיקה אם הסדנה כבר בסל
    if (!in_array($workshopId, $cartItems)) {
        // בדיקה אם הסדנה כבר התקיימה
        $checkDateSql = "SELECT date FROM workshops WHERE workshopId = ?";
        $checkDateStmt = $con->prepare($checkDateSql);
        $checkDateStmt->bind_param("i", $workshopId);
        $checkDateStmt->execute();
        $checkDateResult = $checkDateStmt->get_result();
        $workshopDate = $checkDateResult->fetch_assoc()['date'];
        
        $currentDate = new DateTime();
        $workshopDateTime = new DateTime($workshopDate);
        
        // רק אם הסדנה עדיין לא התקיימה, הוסף אותה לסל
        if ($workshopDateTime > $currentDate) {
            $cartItems[] = $workshopId;
            $_SESSION['cart'] = $cartItems;
            header("Location: workshop.php?added=1");
            exit();
        } else {
            header("Location: workshop.php?error=past");
            exit();
        }
    } else {
        header("Location: workshop.php?error=already");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>הסדנאות שלנו - TasteCraft</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #FEFAF7;
            margin: 0;
            padding: 0;
            color: #333;
        }

        /* סרגל עליון */
        .navbar {
            background-color: white;
            color: black;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .navbar a {
            color: black;
            text-decoration: none;
            margin: 0 15px;
            font-weight: bold;
            font-size: 16px;
        }

        .navbar a:hover {
            color: #f4b400;
            font-size: 18px;
        }

        .navbar .icons img {
            width: 30px;
            margin-left: 15px;
            cursor: pointer;
            filter: grayscale(100%);
            border-radius: 50%;
        }

        .navbar .icons img:hover {
            background-color: #BAB3AE;
        }

        /* סגנון כפתור אדמין מושבת */
        .admin-disabled {
            color: #999 !important;
            pointer-events: none;
            cursor: default;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-title {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .page-subtitle {
            font-size: 18px;
            color: #666;
        }

        /* פילטרים */
        .filter-section {
            margin-bottom: 30px;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .filter-title {
            font-size: 20px;
            margin-bottom: 15px;
        }

        .filter-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .filter-option {
            cursor: pointer;
        }

        .filter-checkbox {
            margin-right: 5px;
        }

        .filter-buttons {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        .btn-filter {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-apply {
            background-color: #f5eada;
            color: #333;
        }

        .btn-apply:hover {
            background-color: orange;
        }

        .btn-reset {
            background-color: #e0e0e0;
            color: #333;
        }

        .btn-reset:hover {
            background-color: #d0d0d0;
        }

        /* רשימת הסדנאות */
        .workshops-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }

        .workshop-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .workshop-card:hover {
            transform: translateY(-5px);
        }

        .workshop-image {
            height: 200px;
            width: 100%;
            object-fit: cover;
        }

        .workshop-details {
            padding: 20px;
        }

        .workshop-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }

        .workshop-info {
            color: #666;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .workshop-price {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin: 15px 0;
        }

        .workshop-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }

        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background-color: #f5eada;
            color: #333;
            flex-grow: 1;
            margin-right: 10px;
        }

        .btn-primary:hover {
            background-color: orange;
        }

        .btn-secondary {
            background-color: #e0e0e0;
            color: #333;
            flex-grow: 1;
        }

        .btn-secondary:hover {
            background-color: #d0d0d0;
        }
        
        .btn-disabled {
            background-color: #e0e0e0;
            color: #999;
            cursor: not-allowed;
            flex-grow: 1;
        }

        .availability {
            margin-top: 10px;
            font-size: 14px;
            font-weight: bold;
        }

        .available {
            color: #28a745;
        }

        .limited {
            color: #f4b400;
        }

        .sold-out {
            color: #dc3545;
        }

        .locked-seat {
            color: #dc3545;
            background-color: #fff3cd;
            padding: 5px 8px;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
            margin-top: 5px;
        }

        .empty-result {
            text-align: center;
            padding: 50px;
            color: #666;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .workshop-completed {
            display: inline-block;
            background-color: #f0f0f0;
            color: #888;
            padding: 8px 15px;
            border-radius: 4px;
            font-weight: bold;
            margin-right: 10px;
            flex-grow: 1;
            text-align: center;
        }

        .footer {
            text-align: center;
            padding: 20px;
            background-color: #f1f1f1;
            margin-top: 50px;
        }

        .footer .pfooter {
            margin: 5px 0;
        }

        /* מותאם למסכים קטנים */
        @media (max-width: 768px) {
            .workshops-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- סרגל עליון -->
    <div class="navbar">
        <div class="left-links">
            <a href="home2.php">Home</a>
            <a href="about.php">About</a>
            <a href="workshop.php">Book Workshop</a>
            <a href="profile.php">Profile</a>
            <!-- כפתור Admin - מוצג באופן שונה בהתאם להרשאות -->
            <?php if($isAdmin): ?>
                <a href="admin.php">Admin</a>
            <?php else: ?>
                <a href="#" class="admin-disabled">Admin</a>
            <?php endif; ?>
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

    <div class="container">
        <div class="header">
            <h1 class="page-title">סדנאות בישול</h1>
            <p class="page-subtitle">גלו סדנאות בישול מרתקות והרשמו עוד היום</p>
        </div>
        
        <!-- הודעות הצלחה ושגיאה -->
        <?php if(isset($_GET['added'])): ?>
        <div class="alert alert-success" id="successMessage">
            הסדנה נוספה בהצלחה לסל הקניות שלך!
        </div>
        <?php endif; ?>
        
        <?php if(isset($_GET['error']) && $_GET['error'] == 'already'): ?>
        <div class="alert alert-danger" id="errorMessage">
            הסדנה כבר נמצאת בסל הקניות שלך.
        </div>
        <?php endif; ?>
        
        <?php if(isset($_GET['error']) && $_GET['error'] == 'past'): ?>
        <div class="alert alert-danger" id="errorMessage">
            לא ניתן להירשם לסדנה שכבר התקיימה.
        </div>
        <?php endif; ?>
        
        <!-- פילטרים - אלרגיות -->
        <div class="filter-section">
            <h2 class="filter-title">סנן לפי אלרגיות ומגבלות תזונה</h2>
            <form method="get" action="">
                <div class="filter-options">
                    <?php while ($option = $optionsResult->fetch_assoc()): ?>
                        <div class="filter-option">
                        <input type="checkbox" id="option-<?php echo $option['optionId']; ?>" name="filter[]" value="<?php echo $option['optionId']; ?>" class="filter-checkbox" <?php echo in_array($option['optionId'], $filteredOptions) ? 'checked' : ''; ?>>
                            <label for="option-<?php echo $option['optionId']; ?>"><?php echo $option['optionName']; ?></label>
                        </div>
                    <?php endwhile; ?>
                </div>
                <div class="filter-buttons">
                    <button type="submit" class="btn-filter btn-apply">החל סינון</button>
                    <a href="workshop.php" class="btn-filter btn-reset">אפס סינון</a>
                </div>
            </form>
        </div>
        
        <!-- רשימת הסדנאות -->
        <?php if ($result->num_rows > 0): ?>
            <div class="workshops-grid">
                <?php while ($workshop = $result->fetch_assoc()): 
                    // חישוב זמינות עם מקומות נעולים - תיקון
                    $availableSeats = $workshop['maxParticipants'] - $workshop['registeredCount'] - $workshop['lockedSeats'];
                    
                    // בדיקה אם המשתמש הנוכחי מקבל התראה של 24 שעות לסדנה זו
                    $userHas24hNotificationSql = "SELECT * FROM notifications 
                                                 WHERE id = ? AND workshopId = ? AND type = 'spot_available_24h' 
                                                 AND status = 'unread' 
                                                 AND createdAt > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
                    $userHas24hStmt = $con->prepare($userHas24hNotificationSql);
                    $userHas24hStmt->bind_param("ii", $userId, $workshop['workshopId']);
                    $userHas24hStmt->execute();
                    $userHas24hResult = $userHas24hStmt->get_result();
                    $userHas24hNotification = $userHas24hResult->num_rows > 0;
                    
                    // בדיקה אם הסדנה כבר התקיימה
                    $workshopDate = new DateTime($workshop['date']);
                    $currentDate = new DateTime();
                    $isPastWorkshop = $currentDate > $workshopDate;
                    
                    // בדיקה אם הסדנה כבר בסל
                    $isInCart = in_array($workshop['workshopId'], $cartItems);
                    
                    // בדיקה אם המשתמש כבר רשום לסדנה זו
                    $isRegisteredSql = "SELECT * FROM registration WHERE id = ? AND workshopId = ?";
                    $isRegisteredStmt = $con->prepare($isRegisteredSql);
                    $isRegisteredStmt->bind_param("ii", $userId, $workshop['workshopId']);
                    $isRegisteredStmt->execute();
                    $isRegisteredResult = $isRegisteredStmt->get_result();
                    $isRegistered = $isRegisteredResult->num_rows > 0;
                    
                    // בדיקה אם המשתמש כבר ברשימת המתנה
                    $isInWaitlistSql = "SELECT * FROM notifications WHERE id = ? AND workshopId = ? AND type = 'waitlist' AND status IN ('waiting', 'notified')";
                    $isInWaitlistStmt = $con->prepare($isInWaitlistSql);
                    $isInWaitlistStmt->bind_param("ii", $userId, $workshop['workshopId']);
                    $isInWaitlistStmt->execute();
                    $isInWaitlistResult = $isInWaitlistStmt->get_result();
                    $isInWaitlist = $isInWaitlistResult->num_rows > 0;
                ?>
                    <div class="workshop-card">
                        <img src="<?php echo $workshop['img']; ?>" alt="<?php echo $workshop['workshopName']; ?>" class="workshop-image">
                        <div class="workshop-details">
                            <h3 class="workshop-name"><?php echo $workshop['workshopName']; ?></h3>
                            <p class="workshop-info">
                                <strong>תאריך:</strong> <?php echo date('d/m/Y', strtotime($workshop['date'])); ?>
                            </p>
                            <p class="workshop-info">
                                <strong>שעה:</strong> <?php echo date('H:i', strtotime($workshop['date'])); ?>
                            </p>
                            <p class="workshop-info">
                                <strong>מיקום:</strong> <?php echo $workshop['location']; ?>
                            </p>
                            <p class="workshop-price">₪<?php echo $workshop['price']; ?></p>
                            
                            <div class="availability">
                                <?php if ($isPastWorkshop): ?>
                                    <span class="sold-out">הסדנה כבר התקיימה</span>
                                <?php elseif ($userHas24hNotification): ?>
                                    <span class="available">⭐ זמין עבורך! (יש לך 24 שעות לאישור)</span>
                                <?php elseif ($availableSeats > 5): ?>
                                    <span class="available">זמין (<?php echo $availableSeats; ?> מקומות נותרו)</span>
                                <?php elseif ($availableSeats > 0): ?>
                                    <span class="limited">זמינות מוגבלת (רק <?php echo $availableSeats; ?> מקומות נותרו)</span>
                                <?php else: ?>
                                    <span class="sold-out">הסדנה מלאה</span>
                                    <?php if ($workshop['lockedSeats'] > 0): ?>
                                        <div class="locked-seat">🔒 <?php echo $workshop['lockedSeats']; ?> מקומות בהליך אישור</div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="workshop-actions">
                                <?php if ($isPastWorkshop): ?>
                                    <span class="workshop-completed">הסדנה הסתיימה</span>
                                <?php elseif ($isRegistered): ?>
                                    <span class="btn-disabled">כבר רשום</span>
                                <?php elseif ($isInCart): ?>
                                    <span class="btn-disabled">בסל הקניות</span>
                                <?php elseif ($userHas24hNotification): ?>
                                    <a href="profile.php" class="btn btn-primary">⏰ אשר עכשיו!</a>
                                <?php elseif ($availableSeats > 0 && !$workshop['lockedSeats']): ?>
                                    <a href="?action=add&workshopId=<?php echo $workshop['workshopId']; ?>" class="btn btn-primary">הוסף לסל</a>
                                <?php elseif ($availableSeats > 0 && $workshop['lockedSeats'] > 0): ?>
                                    <span class="btn-disabled">🔒 מקום נעול לאישור</span>
                                <?php elseif ($isInWaitlist): ?>
                                    <span class="btn-disabled">ברשימת המתנה</span>
                                <?php else: ?>
                                    <a href="workshop-details.php?workshopId=<?php echo $workshop['workshopId']; ?>" class="btn btn-primary">הצטרף לרשימת המתנה</a>
                                <?php endif; ?>
                                
                                <a href="workshop-details.php?workshopId=<?php echo $workshop['workshopId']; ?>" class="btn btn-secondary">פרטים נוספים</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-result">
                <h3>לא נמצאו סדנאות התואמות את הסינון שבחרת</h3>
                <p>נסה להסיר חלק מהפילטרים או <a href="workshop.php">הצג את כל הסדנאות</a></p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="footer">
        <p class="pfooter">RAFEEK KABLAN ©</p>
        <p class="pfooter">AMAL TAFISH ©</p>
    </div>

    <script>
        // הסתרת הודעות הצלחה ושגיאה אחרי 5 שניות
        document.addEventListener('DOMContentLoaded', function() {
            const successMessage = document.getElementById('successMessage');
            const errorMessage = document.getElementById('errorMessage');
            
            if (successMessage) {
                setTimeout(function() {
                    successMessage.style.display = 'none';
                }, 5000);
            }
            
            if (errorMessage) {
                setTimeout(function() {
                    errorMessage.style.display = 'none';
                }, 5000);
            }
        });
    </script>
</body>
</html>