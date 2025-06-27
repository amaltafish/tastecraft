<?php
// email_test.php - בדיקה מלאה של מערכת מיילים TasteCraft
require_once 'email_config.php';

// אם יש בסיס נתונים - ננסה להתחבר
$dbConnected = false;
if (file_exists('components/database.php')) {
    try {
        require_once 'components/database.php';
        $dbConnected = true;
    } catch (Exception $e) {
        $dbError = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>בדיקת מיילים - TasteCraft</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 40px; 
            direction: rtl; 
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success { 
            background: #d4edda; 
            color: #155724; 
            padding: 15px; 
            margin: 15px 0; 
            border-radius: 5px; 
            border-left: 5px solid #28a745;
        }
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            padding: 15px; 
            margin: 15px 0; 
            border-radius: 5px; 
            border-left: 5px solid #dc3545;
        }
        .warning { 
            background: #fff3cd; 
            color: #856404; 
            padding: 15px; 
            margin: 15px 0; 
            border-radius: 5px; 
            border-left: 5px solid #ffc107;
        }
        .info { 
            background: #e7f3ff; 
            padding: 15px; 
            margin: 15px 0; 
            border-radius: 5px; 
            border-left: 5px solid #007cba;
        }
        .test-section {
            background: #f8f9fa;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        input[type="email"] { 
            padding: 10px; 
            width: 350px; 
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }
        button { 
            padding: 12px 25px; 
            background: #007cba; 
            color: white; 
            border: none; 
            cursor: pointer; 
            border-radius: 5px;
            font-size: 14px;
            margin: 5px;
        }
        button:hover { background: #005a82; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #545b62; }
        h1, h2, h3 { color: #333; }
        .status-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        .status-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
            border: 1px solid #dee2e6;
        }
        .email-preview {
            background: #f8f9fa;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            white-space: pre-wrap;
            font-family: monospace;
            font-size: 13px;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>🧪 מערכת בדיקת מיילים - TasteCraft</h1>
    <p><small>בדיקה מקיפה של יכולות שליחת מיילים במערכת</small></p>

    <?php
    // 1. בדיקת חיבור לבסיס נתונים
    echo "<div class='test-section'>";
    echo "<h2>🔗 1. בדיקת חיבור לבסיס נתונים</h2>";
    if ($dbConnected) {
        echo "<div class='success'>✅ חיבור לבסיס נתונים תקין</div>";
    } else {
        echo "<div class='warning'>⚠️ לא ניתן להתחבר לבסיס נתונים";
        if (isset($dbError)) {
            echo "<br><small>שגיאה: " . htmlspecialchars($dbError) . "</small>";
        }
        echo "</div>";
    }
    echo "</div>";

    // 2. בדיקה בסיסית של PHP mail
    echo "<div class='test-section'>";
    echo "<h2>📧 2. בדיקת זמינות PHP Mail</h2>";
    if (function_exists('mail')) {
        echo "<div class='success'>✅ פונקציית mail() זמינה ופעילה</div>";
    } else {
        echo "<div class='error'>❌ פונקציית mail() לא זמינה במערכת</div>";
    }
    echo "</div>";

    // 3. בדיקת הגדרות PHP מייל
    echo "<div class='test-section'>";
    echo "<h2>⚙️ 3. הגדרות מייל במערכת PHP</h2>";
    
    $mailSettings = [
        'sendmail_path' => ini_get('sendmail_path'),
        'SMTP' => ini_get('SMTP'),
        'smtp_port' => ini_get('smtp_port'),
        'sendmail_from' => ini_get('sendmail_from'),
        'mail.add_x_header' => ini_get('mail.add_x_header') ? 'On' : 'Off',
        'mail.log' => ini_get('mail.log')
    ];

    echo "<div class='status-grid'>";
    foreach ($mailSettings as $setting => $value) {
        $status = !empty($value) ? "success" : "warning";
        $icon = !empty($value) ? "✅" : "⚠️";
        echo "<div class='status-card'>";
        echo "<strong>$setting:</strong><br>";
        echo "<span class='$status'>$icon " . ($value ?: 'לא מוגדר') . "</span>";
        echo "</div>";
    }
    echo "</div>";
    echo "</div>";

    // 4. בדיקת תצורת המייל הפנימית שלנו
    echo "<div class='test-section'>";
    echo "<h2>🔧 4. בדיקת תצורת המערכת הפנימית</h2>";
    $emailConfig = testEmailConfiguration();
    if ($emailConfig['status']) {
        echo "<div class='success'>✅ " . htmlspecialchars($emailConfig['message']) . "</div>";
    } else {
        echo "<div class='error'>❌ " . htmlspecialchars($emailConfig['message']) . "</div>";
    }
    echo "</div>";

    // 5. תצוגה מקדימה של טמפלטי מייל
    echo "<div class='test-section'>";
    echo "<h2>📄 5. תצוגה מקדימה של טמפלטי מייל</h2>";
    
    $sampleData = [
        'userName' => 'דוגמה משתמש',
        'workshopName' => 'סדנת בישול איטלקי',
        'date' => date('d/m/Y H:i'),
        'location' => 'מטבח TasteCraft, תל אביב',
        'price' => '150',
        'amountPaid' => '150',
        'refundAmount' => '120'
    ];

    $templates = ['registration_confirmation', 'cancellation_confirmation', 'waitlist_spot_available'];
    
    foreach ($templates as $templateType) {
        $template = getEmailTemplate($templateType, $sampleData);
        echo "<h4>" . $template['subject'] . "</h4>";
        echo "<div class='email-preview'>" . htmlspecialchars($template['body']) . "</div>";
    }
    echo "</div>";

    // 6. בדיקת שליחת מייל אמיתי
    if (isset($_POST['test_email']) && !empty($_POST['email'])) {
        echo "<div class='test-section'>";
        echo "<h2>📤 6. תוצאת שליחת מייל ניסיון</h2>";
        
        $testEmail = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        if ($testEmail) {
            $testType = $_POST['test_type'] ?? 'registration_confirmation';
            $testData = [
                'userName' => 'בודק המערכת',
                'workshopName' => 'סדנת בדיקה - מערכת מיילים TasteCraft',
                'date' => date('d/m/Y H:i', strtotime('+1 week')),
                'location' => 'מטבח הבדיקות, תל אביב',
                'price' => '99',
                'amountPaid' => '99',
                'refundAmount' => '79'
            ];
            
            echo "<p><strong>שולח מייל מסוג:</strong> " . $testType . "</p>";
            echo "<p><strong>אל כתובת:</strong> " . htmlspecialchars($testEmail) . "</p>";
            
            $template = getEmailTemplate($testType, $testData);
            $startTime = microtime(true);
            $result = sendEmail($testEmail, $template['subject'], $template['body']);
            $endTime = microtime(true);
            $sendTime = round(($endTime - $startTime) * 1000, 2);
            
            if ($result) {
                echo "<div class='success'>";
                echo "✅ <strong>מייל נשלח בהצלחה!</strong><br>";
                echo "⏱️ זמן שליחה: {$sendTime} אלפיות שנייה<br>";
                echo "📥 בדוק את תיבת הדואר שלך (וגם תיקיית ספאם)<br>";
                echo "📧 נושא: " . htmlspecialchars($template['subject']);
                echo "</div>";
                
                // רישום הצלחה ללוג
                error_log("EMAIL SUCCESS: Test email sent to $testEmail (type: $testType, time: {$sendTime}ms)");
                
            } else {
                echo "<div class='error'>";
                echo "❌ <strong>שליחת המייל נכשלה</strong><br>";
                echo "⏱️ זמן ניסיון: {$sendTime} אלפיות שנייה<br>";
                echo "<br><strong>סיבות אפשריות:</strong><br>";
                echo "• השרת לא מוגדר לשליחת מיילים<br>";
                echo "• חסרות הרשאות למשלח מיילים<br>";
                echo "• בעיית רשת או DNS<br>";
                echo "• כתובת המקור נחסמה כספאם<br>";
                echo "</div>";
                
                // רישום כשל ללוג
                error_log("EMAIL FAILED: Test email failed to $testEmail (type: $testType, time: {$sendTime}ms)");
                
                // הצגת שגיאה מפורטת אם קיימת
                $lastError = error_get_last();
                if ($lastError && strpos($lastError['message'], 'mail') !== false) {
                    echo "<div class='warning'>";
                    echo "<strong>שגיאה טכנית:</strong><br>";
                    echo "<small>" . htmlspecialchars($lastError['message']) . "</small>";
                    echo "</div>";
                }
            }
        } else {
            echo "<div class='error'>❌ כתובת אימייל לא תקינה</div>";
        }
        echo "</div>";
    }

    // 7. בדיקת לוג מיילים
    echo "<div class='test-section'>";
    echo "<h2>📋 7. לוג מיילים אחרונים</h2>";
    
    // קריאת error log של PHP אם אפשר
    $logEntries = [];
    
    // ניסיון לקרוא את ה-error log
    $errorLogPath = ini_get('error_log');
    if ($errorLogPath && file_exists($errorLogPath) && is_readable($errorLogPath)) {
        $logContent = file_get_contents($errorLogPath);
        $logLines = explode("\n", $logContent);
        $emailLogLines = array_filter($logLines, function($line) {
            return strpos($line, 'EMAIL') !== false || strpos($line, 'mail') !== false;
        });
        $logEntries = array_slice(array_reverse($emailLogLines), 0, 10);
    }
    
    if (!empty($logEntries)) {
        echo "<pre>";
        foreach ($logEntries as $entry) {
            echo htmlspecialchars($entry) . "\n";
        }
        echo "</pre>";
    } else {
        echo "<div class='info'>ℹ️ אין רשומות לוג זמינות או לא נמצאו רשומות מיילים</div>";
    }
    echo "</div>";
    ?>

    <!-- טופס בדיקת מייל מתקדם -->
    <div class="test-section">
        <h2>🧪 בדיקת שליחת מייל</h2>
        <form method="post">
            <p>
                <label for="email"><strong>כתובת אימייל לבדיקה:</strong></label><br>
                <input type="email" id="email" name="email" required 
                       placeholder="your-email@example.com" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </p>
            
            <p>
                <label for="test_type"><strong>סוג מייל לבדיקה:</strong></label><br>
                <select name="test_type" id="test_type" style="padding: 10px; width: 300px;">
                    <option value="registration_confirmation">אישור הרשמה לסדנה</option>
                    <option value="cancellation_confirmation">אישור ביטול הרשמה</option>
                    <option value="waitlist_spot_available">התראה על מקום פנוי</option>
                </select>
            </p>
            
            <p>
                <button type="submit" name="test_email">📧 שלח מייל בדיקה</button>
                <button type="button" class="btn-secondary" onclick="location.reload()">🔄 רענן דף</button>
            </p>
        </form>
    </div>

    <!-- הוראות תיקון -->
    <div class="info">
        <h3>💡 הוראות לתיקון בעיות מיילים:</h3>
        
        <h4>🖥️ XAMPP (Windows/Mac):</h4>
        <ul>
            <li>הפעל את Mercury Mail Server</li>
            <li>או הגדר sendmail ב-php.ini</li>
            <li>הוסף: <code>sendmail_path = "C:\xampp\sendmail\sendmail.exe -t"</code></li>
        </ul>
        
        <h4>🖥️ WAMP:</h4>
        <ul>
            <li>עדכן php.ini עם נתיבי sendmail</li>
            <li>הגדר SMTP שרת מקומי או חיצוני</li>
        </ul>
        
        <h4>🐧 Linux Server:</h4>
        <ul>
            <li>התקן sendmail: <code>sudo apt-get install sendmail</code></li>
            <li>או postfix: <code>sudo apt-get install postfix</code></li>
            <li>הפעל את השירות: <code>sudo service sendmail start</code></li>
        </ul>
        
        <h4>☁️ שרת בענן:</h4>
        <ul>
            <li>השתמש ב-SMTP של הספק (Gmail, SendGrid, AWS SES)</li>
            <li>הגדר PHPMailer או SwiftMailer</li>
            <li>הגדר SPF ו-DKIM records</li>
        </ul>
    </div>

    <hr style="margin: 30px 0;">
    <p style="text-align: center; color: #666; font-size: 14px;">
        <strong>TasteCraft Email Testing System</strong><br>
        <small>קובץ: email_test.php | זמן: <?php echo date('Y-m-d H:i:s'); ?> | 
        PHP Version: <?php echo phpversion(); ?></small>
    </p>
</div>

</body>
</html>