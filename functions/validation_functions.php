<?php
// functions/validation_functions.php - פונקציות בדיקה ותקינות

/**
 * בדיקה אם המייל תקין
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * בדיקה אם הסיסמה חזקה מספיק
 */
function validatePassword($password) {
    // לפחות 6 תווים
    if (strlen($password) < 6) {
        return false;
    }
    return true;
}

/**
 * בדיקה אם ת.ז ישראלית תקינה
 */
function validateIsraeliID($id) {
    // בדיקה בסיסית של אורך
    if (strlen($id) !== 9 || !is_numeric($id)) {
        return false;
    }
    
    // בדיקת ספרת ביקורת
    $sum = 0;
    for ($i = 0; $i < 8; $i++) {
        $digit = intval($id[$i]);
        if ($i % 2 === 0) {
            $sum += $digit;
        } else {
            $digit *= 2;
            if ($digit > 9) {
                $digit = intval($digit / 10) + ($digit % 10);
            }
            $sum += $digit;
        }
    }
    
    $checkDigit = (10 - ($sum % 10)) % 10;
    return $checkDigit === intval($id[8]);
}

/**
 * בדיקה אם השם תקין
 */
function validateName($name) {
    // לפחות 2 תווים, רק אותיות ורווחים
    return strlen(trim($name)) >= 2 && preg_match('/^[a-zA-Zא-ת\s]+$/', $name);
}

/**
 * בדיקה אם המחיר תקין
 */
function validatePrice($price) {
    return is_numeric($price) && $price >= 0;
}

/**
 * בדיקה אם התאריך תקין
 */
function validateDate($date) {
    $dateTime = DateTime::createFromFormat('Y-m-d', $date);
    return $dateTime && $dateTime->format('Y-m-d') === $date;
}

/**
 * בדיקה אם התאריך בעתיד
 */
function validateFutureDate($date) {
    if (!validateDate($date)) {
        return false;
    }
    
    $dateTime = new DateTime($date);
    $currentDate = new DateTime();
    
    return $dateTime > $currentDate;
}

/**
 * בדיקה אם מספר המשתתפים תקין
 */
function validateParticipants($count) {
    return is_numeric($count) && $count > 0 && $count <= 100;
}

/**
 * בדיקה אם URL התמונה תקין
 */
function validateImageURL($url) {
    // בדיקה בסיסית של URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    
    // בדיקה אם יש סיומת תמונה
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
    
    return in_array($extension, $imageExtensions);
}

/**
 * בדיקה אם הטקסט אינו ריק
 */
function validateNotEmpty($text) {
    return !empty(trim($text));
}

/**
 * בדיקה אם אורך הטקסט בטווח מתאים
 */
function validateLength($text, $min = 1, $max = 1000) {
    $length = strlen(trim($text));
    return $length >= $min && $length <= $max;
}

/**
 * ניקוי טקסט מתגי HTML
 */
function sanitizeText($text) {
    return htmlspecialchars(strip_tags(trim($text)), ENT_QUOTES, 'UTF-8');
}

/**
 * ניקוי מייל
 */
function sanitizeEmail($email) {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

/**
 * בדיקת קוד מנהל
 */
function validateAdminCode($code) {
    return $code === '12345'; // הקוד הקבוע שלך
}

/**
 * בדיקה משולבת של נתוני משתמש
 */
function validateUserData($data) {
    $errors = [];
    
    // בדיקת ת.ז
    if (!isset($data['id']) || !validateIsraeliID($data['id'])) {
        $errors[] = 'תעודת זהות לא תקינה';
    }
    
    // בדיקת שם פרטי
    if (!isset($data['Fname']) || !validateName($data['Fname'])) {
        $errors[] = 'שם פרטי לא תקין';
    }
    
    // בדיקת שם משפחה
    if (!isset($data['Lname']) || !validateName($data['Lname'])) {
        $errors[] = 'שם משפחה לא תקין';
    }
    
    // בדיקת מייל
    if (!isset($data['Email']) || !validateEmail($data['Email'])) {
        $errors[] = 'כתובת מייל לא תקינה';
    }
    
    // בדיקת סיסמה
    if (!isset($data['password']) || !validatePassword($data['password'])) {
        $errors[] = 'סיסמה חייבת להיות לפחות 6 תווים';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * בדיקה משולבת של נתוני סדנה
 */
function validateWorkshopData($data) {
    $errors = [];
    
    // בדיקת שם סדנה
    if (!isset($data['workshopName']) || !validateLength($data['workshopName'], 3, 100)) {
        $errors[] = 'שם הסדנה חייב להיות בין 3-100 תווים';
    }
    
    // בדיקת תיאור
    if (!isset($data['description']) || !validateLength($data['description'], 10, 1000)) {
        $errors[] = 'תיאור הסדנה חייב להיות בין 10-1000 תווים';
    }
    
    // בדיקת תאריך
    if (!isset($data['date']) || !validateFutureDate($data['date'])) {
        $errors[] = 'תאריך הסדנה חייב להיות בעתיד';
    }
    
    // בדיקת מיקום
    if (!isset($data['location']) || !validateLength($data['location'], 3, 100)) {
        $errors[] = 'מיקום הסדנה חייב להיות בין 3-100 תווים';
    }
    
    // בדיקת מחיר
    if (!isset($data['price']) || !validatePrice($data['price'])) {
        $errors[] = 'מחיר הסדנה לא תקין';
    }
    
    // בדיקת מספר משתתפים
    if (!isset($data['maxParticipants']) || !validateParticipants($data['maxParticipants'])) {
        $errors[] = 'מספר המשתתפים חייב להיות בין 1-100';
    }
    
    // בדיקת תמונה
    if (!isset($data['img']) || !validateImageURL($data['img'])) {
        $errors[] = 'כתובת התמונה לא תקינה';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * בדיקה אם יש זמן מספיק לביטול (48 שעות)
 */
function canCancelRegistration($workshopDate) {
    $workshopDateTime = new DateTime($workshopDate);
    $currentDate = new DateTime();
    $interval = $currentDate->diff($workshopDateTime);
    $hoursRemaining = ($interval->days * 24) + $interval->h;
    
    return $hoursRemaining >= 48 && $workshopDateTime > $currentDate;
}

/**
 * בדיקה אם ההתראה עדיין תקפה (24 שעות)
 */
function isNotificationValid($createdAt) {
    $createdTime = new DateTime($createdAt);
    $currentTime = new DateTime();
    $interval = $createdTime->diff($currentTime);
    $hoursElapsed = ($interval->days * 24) + $interval->h;
    
    return $hoursElapsed < 24;
}
?>