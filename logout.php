<?php
// התחלת סשן
session_start();

// ניקוי כל משתני הסשן
$_SESSION = array();

// אם יש עוגיית סשן, מחק אותה
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// סיום הסשן
session_destroy();

// הפניה לדף ההתחברות
header("Location: login.php");
exit();
?>