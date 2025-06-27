<?php
// functions/email_functions.php - פונקציות שליחת מיילים

/**
 * שליחת מייל עם הגדרות מוכנות
 */
function sendEmail($to, $subject, $message, $isHTML = true) {
    if (!function_exists('mail')) {
        error_log("ERROR: mail() function not available");
        return false;
    }
    
    $headers = "From: noreply@tastecraft.com\r\n";
    
    if ($isHTML) {
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    }
    
    error_log("Attempting to send email to: " . $to);
    $result = @mail($to, $subject, $message, $headers);
    
    if ($result) {
        error_log("Email sent successfully to: " . $to);
    } else {
        error_log("Failed to send email to: " . $to);
    }
    
    return $result;
}

/**
 * שליחת מייל אישור הרשמה
 */
function sendRegistrationConfirmation($userEmail, $userName, $workshops, $totalAmount) {
    $subject = "Registration Confirmation - TasteCraft";
    $message = "
    <html>
    <head>
        <title>Registration Confirmation</title>
    </head>
    <body>
        <h2>Thank you for your registration, $userName!</h2>
        <p>Your registration for the following workshops has been confirmed:</p>
        <table border='1' cellpadding='10' style='border-collapse: collapse;'>
            <tr>
                <th>Workshop</th>
                <th>Date</th>
                <th>Time</th>
                <th>Location</th>
                <th>Price</th>
            </tr>";
    
    foreach ($workshops as $workshop) {
        $message .= "
            <tr>
                <td>{$workshop['workshopName']}</td>
                <td>" . date('F j, Y', strtotime($workshop['date'])) . "</td>
                <td>" . date('g:i A', strtotime($workshop['date'])) . "</td>
                <td>{$workshop['location']}</td>
                <td>₪{$workshop['price']}</td>
            </tr>";
    }
    
    $message .= "
            <tr>
                <td colspan='4' style='text-align: right;'><strong>Total:</strong></td>
                <td><strong>₪$totalAmount</strong></td>
            </tr>
        </table>
        <p>We look forward to seeing you at the workshops!</p>
        <p>If you have any questions, please contact us.</p>
        <p>Thank you for choosing TasteCraft!</p>
    </body>
    </html>
    ";
    
    return sendEmail($userEmail, $subject, $message);
}

/**
 * שליחת מייל על הוספה לסל
 */
function sendCartAddedNotification($userEmail, $userName, $workshop) {
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
    
    return sendEmail($userEmail, $subject, $message);
}

/**
 * שליחת מייל על ביטול הרשמה
 */
function sendCancellationConfirmation($userEmail, $userName, $workshopName, $amountPaid, $refundAmount) {
    $subject = "אישור ביטול סדנה - TasteCraft";
    $message = "
    <html>
    <head>
        <title>אישור ביטול סדנה</title>
    </head>
    <body>
        <h2>שלום $userName,</h2>
        <p>הרשמתך לסדנה \"$workshopName\" בוטלה בהצלחה.</p>
        <p>פרטי ההחזר:</p>
        <ul>
            <li>סכום ששולם: ₪$amountPaid</li>
            <li>החזר (80%): ₪$refundAmount</li>
        </ul>
        <p>ההחזר הכספי יועבר לאמצעי התשלום המקורי תוך 5-7 ימי עסקים.</p>
        <p>תודה שבחרת ב-TasteCraft!</p>
    </body>
    </html>
    ";
    
    return sendEmail($userEmail, $subject, $message);
}

/**
 * שליחת מייל על הזדמנות ברשימת המתנה
 */
function sendWaitlistOpportunity($userEmail, $userName, $workshopName) {
    $subject = "🎉 התפנה מקום בסדנה - יש לך 24 שעות! - TasteCraft";
    $message = "שלום $userName, התפנה מקום בסדנה $workshopName. יש לך 24 שעות לאשר דרך הפרופיל שלך.";
    
    return sendEmail($userEmail, $subject, $message, false);
}

/**
 * שליחת מייל על פקיעת תוקף
 */
function sendWaitlistExpired($userEmail, $userName, $workshopName) {
    $subject = "פג תוקף ההזדמנות - TasteCraft";
    $message = "שלום $userName, למרבה הצער פג תוקף הזמן לאישור השתתפותך בסדנה '$workshopName'. חזרת לרשימת ההמתנה ונעדכן אותך על הזדמנויות חדשות.";
    
    return sendEmail($userEmail, $subject, $message, false);
}

/**
 * בדיקה אם פונקציית המייל זמינה
 */
function isEmailAvailable() {
    return function_exists('mail');
}

/**
 * קבלת הגדרות מייל נוכחיות
 */
function getEmailSettings() {
    return [
        'mail_function' => function_exists('mail') ? 'Available' : 'Not Available',
        'smtp' => ini_get('SMTP') ?: 'Not configured',
        'smtp_port' => ini_get('smtp_port'),
        'sendmail_path' => ini_get('sendmail_path') ?: 'Not configured',
        'php_version' => phpversion()
    ];
}
?>