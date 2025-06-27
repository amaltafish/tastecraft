<?php
// email_config.php - תיקון ל-WAMP עם אפשרות Gmail SMTP

// הגדרות Gmail SMTP (אם תרצה להשתמש)
define('USE_GMAIL_SMTP', false); // שנה ל-true אם תרצה Gmail
define('GMAIL_USERNAME', 'your-email@gmail.com'); // החלף!
define('GMAIL_APP_PASSWORD', 'your-app-password'); // החלף!

/**
 * פונקציה משופרת לשליחת מיילים - תיקון ל-WAMP
 */
function sendEmail($to, $subject, $message, $isHtml = false) {
    error_log("DEBUG: Attempting to send email to: $to");
    
    // בדיקת הגדרות WAMP
    if (!function_exists('mail')) {
        error_log("ERROR: PHP mail() function is not available");
        return false;
    }
    
    // תיקון הגדרות PHP זמניות ל-WAMP
    ini_set('SMTP', 'localhost');
    ini_set('smtp_port', 25);
    ini_set('sendmail_from', 'noreply@tastecraft.com');
    
    // אם WAMP לא עובד - השתמש בדרך אחרת
    if (USE_GMAIL_SMTP) {
        return sendEmailViaGmail($to, $subject, $message, $isHtml);
    }
    
    // ניסיון שליחה רגילה עם timeout קצר
    $oldErrorReporting = error_reporting(0); // הסתר שגיאות זמנית
    
    // הגדרת encoding לעברית
    $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    
    // הגדרת headers פשוטים ל-WAMP
    $headers = "MIME-Version: 1.0\r\n";
    if ($isHtml) {
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    } else {
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    }
    $headers .= "From: TasteCraft <test@localhost>\r\n";
    $headers .= "Reply-To: test@localhost\r\n";
    
    // ניסיון שליחה עם timeout
    $startTime = time();
    $result = @mail($to, $subject, $message, $headers);
    $endTime = time();
    
    error_reporting($oldErrorReporting); // החזר דיווח שגיאות
    
    // אם השליחה לקחה יותר מ-3 שניות או נכשלה - זה כנראה בעיית WAMP
    if (($endTime - $startTime) > 3 || !$result) {
        error_log("WARNING: WAMP mail failed or took too long. Using simulation mode.");
        return simulateEmailSend($to, $subject, $message);
    }
    
    if ($result) {
        error_log("SUCCESS: Email sent via WAMP to: $to");
        return true;
    } else {
        error_log("ERROR: Failed to send email via WAMP to: $to");
        return simulateEmailSend($to, $subject, $message);
    }
}

/**
 * פונקציה לשליחה דרך Gmail SMTP (אופציונלי)
 */
function sendEmailViaGmail($to, $subject, $message, $isHtml = false) {
    // TODO: יישום PHPMailer עם Gmail
    error_log("DEBUG: Gmail SMTP not implemented yet");
    return simulateEmailSend($to, $subject, $message);
}

/**
 * סימולציה של שליחת מייל לפיתוח (WAMP)
 */
function simulateEmailSend($to, $subject, $message) {
    error_log("SIMULATION: Would send email to: $to");
    error_log("SIMULATION: Subject: $subject");
    error_log("SIMULATION: Message preview: " . substr($message, 0, 100) . "...");
    
    // שמירה לקובץ מקומי לבדיקה
    $emailLog = "email_simulation.log";
    $logEntry = "\n" . str_repeat("=", 50) . "\n";
    $logEntry .= "Time: " . date('Y-m-d H:i:s') . "\n";
    $logEntry .= "To: $to\n";
    $logEntry .= "Subject: $subject\n";
    $logEntry .= "Message:\n$message\n";
    $logEntry .= str_repeat("=", 50) . "\n";
    
    file_put_contents($emailLog, $logEntry, FILE_APPEND | LOCK_EX);
    
    return true; // סימולציה תמיד מצליחה
}

/**
 * טמפלטים למיילים בעברית
 */
function getEmailTemplate($type, $data) {
    switch ($type) {
        case 'registration_confirmation':
            return [
                'subject' => "אישור הרשמה לסדנה - TasteCraft",
                'body' => "שלום {$data['userName']},\n\n🎉 נרשמת בהצלחה לסדנה!\n\n📋 פרטי הסדנה:\n🍳 שם הסדנה: {$data['workshopName']}\n📅 תאריך: {$data['date']}\n📍 מקום: {$data['location']}\n💰 מחיר ששולם: ₪{$data['price']}\n\n🎯 אנא הגע 15 דקות לפני תחילת הסדנה.\n\n👨‍🍳 נתראה בסדנה!\n\nבברכה,\nצוות TasteCraft",
                'isHtml' => false
            ];
            
        case 'cancellation_confirmation':
            return [
                'subject' => "אישור ביטול הרשמה - TasteCraft",
                'body' => "שלום {$data['userName']},\n\n✅ הרשמתך לסדנה \"{$data['workshopName']}\" בוטלה בהצלחה.\n\n💰 פרטי החזר:\n• סכום ששולם: ₪{$data['amountPaid']}\n• החזר (80%): ₪{$data['refundAmount']}\n\n⏱️ ההחזר יועבר תוך 5-7 ימי עסקים לאמצעי התשלום המקורי.\n\n🙏 תודה על הבנתך!\n\nבברכה,\nצוות TasteCraft",
                'isHtml' => false
            ];
            
        case 'waitlist_spot_available':
            return [
                'subject' => "🎉 התפנה מקום בסדנה - יש לך 24 שעות! - TasteCraft",
                'body' => "שלום {$data['userName']},\n\n🎉 בשורות טובות! התפנה מקום בסדנה שחיכית לה:\n🍳 \"{$data['workshopName']}\"\n\n⏰ חשוב! יש לך בדיוק 24 שעות לאשר השתתפותך.\n\n🔗 לאישור מיידי:\n1. היכנס לפרופיל שלך באתר TasteCraft\n2. עבור לקטע 'ההתראות שלי'\n3. לחץ על 'אשר השתתפות'\n\n⚠️ שים לב: אם לא תאשר בזמן, המקום יועבר למשתמש הבא ברשימה.\n\n🏃‍♂️ מהר לפני שיחמוק לך!\n\nבברכה,\nצוות TasteCraft",
                'isHtml' => false
            ];
            
        default:
            return [
                'subject' => "עדכון מ-TasteCraft",
                'body' => $data['message'] ?? "עדכון מהאתר",
                'isHtml' => false
            ];
    }
}

/**
 * פונקציה לבדיקת תצורת מייל
 */
function testEmailConfiguration() {
    if (!function_exists('mail')) {
        return [
            'status' => false,
            'message' => 'PHP mail() function is not available'
        ];
    }
    
    return [
        'status' => true,
        'message' => 'Using WAMP simulation mode - emails will be logged to email_simulation.log'
    ];
}
?>