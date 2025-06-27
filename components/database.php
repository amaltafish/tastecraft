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
?>