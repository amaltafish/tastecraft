<?php
// email_config.php - Updated with proper email sending

require_once 'components/database.php';

function sendEmail($to, $subject, $message, $isHtml = false) {
    // Set proper headers for UTF-8 and emoji support
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "From: TasteCraft <noreply@tastecraft.com>\r\n";
    $headers .= "Reply-To: noreply@tastecraft.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    // Encode subject for proper Hebrew display
    $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    
    // Try to send email
    $success = @mail($to, $subject, $message, $headers);
    
    // Log all email attempts
    $logEntry = "\n=== " . date('Y-m-d H:i:s') . " ===\n";
    $logEntry .= "To: $to\n";
    $logEntry .= "Subject: $subject\n";
    $logEntry .= "Message:\n$message\n";
    $logEntry .= "Status: " . ($success ? "Sent" : "Failed") . "\n";
    
    file_put_contents(__DIR__ . '/email_simulation.log', $logEntry, FILE_APPEND);
    
    return $success;
}

/**
 * טמפלטים למיילים בעברית
 */
function getEmailTemplate($type, $data) {
    switch ($type) {
        case 'registration_confirmation':
            return [
                'subject' => "אישור הרשמה לסדנה - TasteCraft",
                'body' => "שלום {$data['userName']},\n\n🎉 נרשמת בהצלחה לסדנה!\n\n📋 פרטי הסדנה:\n🍳 שם הסדנה: {$data['workshopName']}\n📅 תאריך: {$data['date']}\n📍 מקום: {$data['location']}\n💰 מחיר ששולם: ₪{$data['price']}\n\n🎯 אנא הגע 15 דקות לפני תחילת הסדנה.\n\n👨‍🍳 נתראה בסדנה!\n\nבברכה,\nצוות TasteCraft"
            ];
            
        case 'cancellation_confirmation':
            return [
                'subject' => "אישור ביטול הרשמה - TasteCraft",
                'body' => "שלום {$data['userName']},\n\n✅ הרשמתך לסדנה \"{$data['workshopName']}\" בוטלה בהצלחה.\n\n💰 פרטי החזר:\n• סכום ששולם: ₪{$data['amountPaid']}\n• החזר (80%): ₪{$data['refundAmount']}\n\n⏱️ ההחזר יועבר תוך 5-7 ימי עסקים לאמצעי התשלום המקורי.\n\n🙏 תודה על הבנתך!\n\nבברכה,\nצוות TasteCraft"
            ];
            
        case 'waitlist_spot_available':
            return [
                'subject' => "🎉 התפנה מקום בסדנה - יש לך 24 שעות! - TasteCraft",
                'body' => "שלום {$data['userName']},\n\n🎉 בשורות טובות! התפנה מקום בסדנה שחיכית לה:\n🍳 \"{$data['workshopName']}\"\n\n⏰ חשוב! יש לך בדיוק 24 שעות לאשר השתתפותך.\n\n🔗 לאישור מיידי:\n1. היכנס לפרופיל שלך באתר TasteCraft\n2. עבור לקטע 'ההתראות שלי'\n3. לחץ על 'אשר השתתפות'\n\n⚠️ שים לב: אם לא תאשר בזמן, המקום יועבר למשתמש הבא ברשימה.\n\n🏃‍♂️ מהר לפני שיחמוק לך!\n\nבברכה,\nצוות TasteCraft"
            ];
            
        case 'waitlist_registration':
            return [
                'subject' => "אישור הרשמה לרשימת המתנה - TasteCraft",
                'body' => "שלום {$data['userName']},\n\n" .
                         "✅ נרשמת בהצלחה לרשימת ההמתנה עבור הסדנה:\n" .
                         "🍳 \"{$data['workshopName']}\"\n\n" .
                         "📍 מיקום: {$data['location']}\n" .
                         "📅 תאריך: {$data['date']}\n\n" .
                         "🔔 נעדכן אותך מיד כשיתפנה מקום!\n\n" .
                         "שים לב:\n" .
                         "• כשיתפנה מקום, תקבל התראה במייל ובפרופיל האישי\n" .
                         "• יהיו לך 24 שעות לאשר את השתתפותך\n" .
                         "• אם לא תאשר בזמן, המקום יעבור למשתמש הבא ברשימה\n\n" .
                         "בברכה,\nצוות TasteCraft"
            ];
            
        default:
            return [
                'subject' => "עדכון מ-TasteCraft",
                'body' => $data['message'] ?? "עדכון מהאתר"
            ];
    }
}
?>