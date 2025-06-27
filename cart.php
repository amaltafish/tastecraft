<?php
session_start();
require_once 'email_config.php';
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
$userName = $_SESSION['Fname'];

// בדיקה אם המשתמש הוא מנהל
$isAdmin = isset($_SESSION['flag']) && $_SESSION['flag'] == 1;

// יצירת מערך סל קניות אם לא קיים
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// טיפול בפעולות הקשורות לסל קניות
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // הוספת סדנה לסל
    if ($action === 'add' && isset($_GET['workshopId'])) {
        $workshopId = intval($_GET['workshopId']); // 🔒 תיקון: וידוא שזה מספר
        
        // בדיקה אם הסדנה כבר בסל
        if (!in_array($workshopId, $_SESSION['cart'])) {
            // 🔒 תיקון: prepared statement
            $checkSql = "SELECT w.*, COUNT(r.registrationId) AS registeredCount 
                        FROM workshops w
                        LEFT JOIN registration r ON w.workshopId = r.workshopId
                        WHERE w.workshopId = ?
                        GROUP BY w.workshopId";
            
            $checkStmt = $con->prepare($checkSql);
            $checkStmt->bind_param("i", $workshopId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows > 0) {
                $workshop = $result->fetch_assoc();
                $availableSeats = $workshop['maxParticipants'] - $workshop['registeredCount'];
                
                if ($availableSeats > 0) {
                    // הוספת הסדנה לסל
                    $_SESSION['cart'][] = $workshopId;
                    
                    // שליחת אימייל ללקוח
                    // 🔒 תיקון: prepared statement לשליפת אימייל
                    $emailSql = "SELECT Email FROM users WHERE id = ?";
                    $emailStmt = $con->prepare($emailSql);
                    $emailStmt->bind_param("i", $userId);
                    $emailStmt->execute();
                    $emailResult = $emailStmt->get_result();
                    
                    if ($emailResult->num_rows > 0) {
                        $userEmail = $emailResult->fetch_assoc()['Email'];
                        
                        // הכנת תוכן האימייל
                        $subject = "Workshop Added to Your Cart - TasteCraft";
                        $message = "
                        <html>
                        <head>
                            <title>Workshop Added to Your Cart</title>
                        </head>
                        <body>
                            <h2>Hello $userName,</h2>
                            <p>The workshop <strong>{$workshop['workshopName']}</strong> has been added to your cart.</p>
                            <p>Workshop details:</p>
                            <ul>
                                <li>Date: " . date('F j, Y', strtotime($workshop['date'])) . "</li>
                                <li>Time: " . date('g:i A', strtotime($workshop['date'])) . "</li>
                                <li>Location: {$workshop['location']}</li>
                                <li>Price: ₪{$workshop['price']}</li>
                            </ul>
                            <p>To complete your registration, please proceed to checkout in your cart.</p>
                            <p>Thank you for choosing TasteCraft!</p>
                        </body>
                        </html>
                        ";
                        
                        // Headers for sending HTML email
                        $headers = "MIME-Version: 1.0" . "\r\n";
                        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                        $headers .= "From: noreply@tastecraft.com" . "\r\n";
                        
                        // Actually send the email
                        mail($userEmail, $subject, $message, $headers);
                    }
                    
                    // חזרה לדף פרטי הסדנה עם הודעת הצלחה
                    header("Location: workshop-details.php?workshopId=$workshopId&added=true");
                    exit();
                } else {
                    $errorMessage = "Sorry, this workshop is already full.";
                }
            } else {
                $errorMessage = "Workshop not found.";
            }
        } else {
            // אם הסדנה כבר בסל, חזרה לדף פרטי הסדנה
            header("Location: workshop-details.php?workshopId=$workshopId");
            exit();
        }
    }
    
    // הסרת סדנה מהסל
    elseif ($action === 'remove' && isset($_GET['workshopId'])) {
        $workshopId = intval($_GET['workshopId']); // 🔒 תיקון: וידוא שזה מספר
        $index = array_search($workshopId, $_SESSION['cart']);
        
        if ($index !== false) {
            unset($_SESSION['cart'][$index]);
            $_SESSION['cart'] = array_values($_SESSION['cart']); // מחדש את מפתחות המערך
        }
    }
    
    // ריקון הסל
    elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
    }
}

if (isset($_POST['checkout'])) {
    $success = true;
    $registeredWorkshops = [];
    $errorMessage = '';
    
    // קבלת פרטי המשתמש למיילים
    $userSql = "SELECT Fname, Lname, Email FROM users WHERE id = ?";
    $userStmt = $con->prepare($userSql);
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $userData = $userResult->fetch_assoc();
    
    foreach ($_SESSION['cart'] as $workshopId) {
        $checkSql = "SELECT w.*, COUNT(r.registrationId) AS registeredCount 
                    FROM workshops w
                    LEFT JOIN registration r ON w.workshopId = r.workshopId
                    WHERE w.workshopId = ?
                    GROUP BY w.workshopId";
        
        $checkStmt = $con->prepare($checkSql);
        $checkStmt->bind_param("i", $workshopId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            $workshop = $result->fetch_assoc();
            $availableSeats = $workshop['maxParticipants'] - $workshop['registeredCount'];
            
            if ($availableSeats > 0) {
                $insertSql = "INSERT INTO registration (id, workshopId, amountPaid, registrationDate) 
                             VALUES (?, ?, ?, NOW())";
                
                $insertStmt = $con->prepare($insertSql);
                $insertStmt->bind_param("iid", $userId, $workshopId, $workshop['price']);
                
                if ($insertStmt->execute()) {
                    $registeredWorkshops[] = $workshop;
                    
                    // ✅ יצירת התראה על הרשמה מוצלחת
                    $registrationMessage = "נרשמת בהצלחה לסדנה: " . $workshop['workshopName'] . " בתאריך " . date('d/m/Y H:i', strtotime($workshop['date']));
                    $registrationNotifSql = "INSERT INTO notifications (id, workshopId, message, type, status, createdAt) 
                                            VALUES (?, ?, ?, 'registration_success', 'unread', NOW())";
                    $registrationNotifStmt = $con->prepare($registrationNotifSql);
                    $registrationNotifStmt->bind_param("iis", $userId, $workshopId, $registrationMessage);
                    $registrationNotifStmt->execute();
                    
                    // ✅ שליחת מייל אישור
                    if ($userData) {
                        $emailData = [
                            'userName' => $userData['Fname'] . ' ' . $userData['Lname'],
                            'workshopName' => $workshop['workshopName'],
                            'date' => date('d/m/Y H:i', strtotime($workshop['date'])),
                            'location' => $workshop['location'],
                            'price' => $workshop['price']
                        ];
                        
                        $template = getEmailTemplate('registration_confirmation', $emailData);
                        $emailSent = sendEmail($userData['Email'], $template['subject'], $template['body'], $template['isHtml']);
                        
                        if (!$emailSent) {
                            error_log("WARNING: Failed to send confirmation email to " . $userData['Email']);
                        }
                    }
                    
                } else {
                    $success = false;
                    $errorMessage = "Error registering for workshop: " . $workshop['workshopName'];
                    break;
                }
            } else {
                $success = false;
                $errorMessage = "Workshop '" . $workshop['workshopName'] . "' is now full. Please remove it from your cart.";
                break;
            }
        } else {
            $success = false;
            $errorMessage = "Workshop not found. Please remove it from your cart.";
            break;
        }
    }
    
    if ($success) {
        $_SESSION['cart'] = [];
        $successMessage = "Registration successful! " . count($registeredWorkshops) . " workshop(s) registered. Confirmation emails have been sent.";
    }
}

// שליפת פרטי הסדנאות בסל
$cartItems = [];
$totalAmount = 0;

if (!empty($_SESSION['cart'])) {
    // 🔒 תיקון: prepared statement דינמי
    $placeholders = str_repeat('?,', count($_SESSION['cart']) - 1) . '?';
    
    $sql = "SELECT w.*, COUNT(r.registrationId) AS registeredCount 
            FROM workshops w
            LEFT JOIN registration r ON w.workshopId = r.workshopId
            WHERE w.workshopId IN ($placeholders)
            GROUP BY w.workshopId";
    
    $stmt = $con->prepare($sql);
    
    // בניית המערך לפרמטרים
    $types = str_repeat('i', count($_SESSION['cart']));
    $stmt->bind_param($types, ...$_SESSION['cart']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $cartItems[] = $row;
        $totalAmount += $row['price'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shopping Cart - TasteCraft</title>
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

        .page-title {
            font-size: 32px;
            margin-bottom: 20px;
            text-align: center;
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

        .cart-empty {
            text-align: center;
            margin: 50px 0;
            font-size: 18px;
            color: #666;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .cart-table th,
        .cart-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .cart-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .cart-table img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }

        .workshop-name {
            font-weight: 600;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .workshop-details {
            color: #666;
            font-size: 14px;
        }

        .price {
            font-weight: 600;
            font-size: 18px;
        }

        .remove-btn {
            color: #dc3545;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
        }

        .remove-btn:hover {
            text-decoration: underline;
        }

        .cart-summary {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .summary-title {
            font-weight: 600;
        }

        .summary-total {
            font-size: 24px;
            font-weight: 700;
            color: #222;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
            text-align: center;
            text-decoration: none;
            margin-right: 10px;
        }

        .btn-primary {
            background-color: #f5eada;
            color: #333;
        }

        .btn-primary:hover {
            background-color: orange;
        }

        .btn-secondary {
            background-color: #e0e0e0;
            color: #333;
        }

        .btn-secondary:hover {
            background-color: #d0d0d0;
        }

        .btn-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .btn-danger:hover {
            background-color: #f5c6cb;
        }

        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
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
        <h1 class="page-title">Your Shopping Cart</h1>
        
        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success">
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger">
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($cartItems)): ?>
            <div class="cart-empty">
                <p>Your cart is empty.</p>
                <a href="workshop.php" class="btn btn-primary">Browse Workshops</a>
            </div>
        <?php else: ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Workshop</th>
                        <th>Details</th>
                        <th>Price</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItems as $item): ?>
                        <tr>
                            <td>
                                <img src="<?php echo $item['img']; ?>" alt="<?php echo $item['workshopName']; ?>">
                            </td>
                            <td>
                                <div class="workshop-name"><?php echo $item['workshopName']; ?></div>
                                <div class="workshop-details">
                                    <div>Date: <?php echo date('F j, Y', strtotime($item['date'])); ?></div>
                                    <div>Time: <?php echo date('g:i A', strtotime($item['date'])); ?></div>
                                    <div>Location: <?php echo $item['location']; ?></div>
                                </div>
                            </td>
                            <td class="price">₪<?php echo $item['price']; ?></td>
                            <td>
                                <a href="cart.php?action=remove&workshopId=<?php echo $item['workshopId']; ?>" class="remove-btn">Remove</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="cart-summary">
                <div class="summary-row">
                    <span class="summary-title">Subtotal</span>
                    <span>₪<?php echo $totalAmount; ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-title">Total</span>
                    <span class="summary-total">₪<?php echo $totalAmount; ?></span>
                </div>
            </div>
            
            <div class="action-buttons">
                <div>
                    <a href="workshop.php" class="btn btn-secondary">Continue Shopping</a>
                    <a href="cart.php?action=clear" class="btn btn-danger">Clear Cart</a>
                </div>
                <form method="post" action="">
                    <button type="submit" name="checkout" class="btn btn-primary">Complete Registration</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="footer">
        <p class="pfooter">RAFEEK KABLAN ©</p>
        <p class="pfooter">AMAL TAFISH ©</p>
    </div>
</body>
</html>