<?php
// components/database.php - חיבור למסד נתונים
$con = new mysqli("localhost", "root", "", "tastecraft");

// הגדרת charset לפתרון בעיות collation
$con->set_charset("utf8");

if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

// בדיקה אם המשתמש מחובר
function checkLogin() {
    if (!isset($_SESSION['id'])) {
        header("Location: login.php");
        exit();
    }
}

// בדיקה אם המשתמש הוא מנהל
function isAdmin() {
    return isset($_SESSION['flag']) && $_SESSION['flag'] == 1;
}

// Simple email function that works in development
function sendEmailDev($to, $subject, $message) {
    $headers = "From: TasteCraft <noreply@tastecraft.com>\r\n";
    $headers .= "Reply-To: noreply@tastecraft.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    // For development, just log the email
    $logFile = __DIR__ . '/../email_simulation.log';
    $logEntry = "\n=== " . date('Y-m-d H:i:s') . " ===\n";
    $logEntry .= "To: $to\n";
    $logEntry .= "Subject: $subject\n";
    $logEntry .= "Message:\n$message\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    return true;
}
?>