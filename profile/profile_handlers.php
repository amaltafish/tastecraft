<?php
// profile/profile_handlers.php - טיפול בפעולות POST עם תיקוני אבטחה

function handleProfileActions($con, $userId) {
    $result = ['redirect' => false, 'params' => []];
    
    if (isset($_POST['confirmWaitlistSpot'])) {
        $result = handleConfirmWaitlistSpot($con, $userId, $_POST);
    }
    elseif (isset($_POST['declineWaitlistSpot'])) {
        $result = handleDeclineWaitlistSpot($con, $userId, $_POST);
    }
    elseif (isset($_POST['cancelWaitlist'])) {
        $result = handleCancelWaitlist($con, $userId, $_POST);
    }
    elseif (isset($_POST['updateProfile'])) {
        $result = handleUpdateProfile($con, $userId, $_POST);
    }
    elseif (isset($_POST['cancelRegistration'])) {
        $result = handleCancelRegistration($con, $userId, $_POST);
    }
    elseif (isset($_POST['submitReview'])) {
        $result = handleSubmitReview($con, $userId, $_POST);
    }
    
    return $result;
}

function handleConfirmWaitlistSpot($con, $userId, $postData) {
    // 🔒 תיקון: וידוא שהנתונים הם מספרים
    $notificationId = intval($postData['notificationId']);
    $workshopId = intval($postData['workshopId']);
    
    // 🔒 תיקון: prepared statement
    $checkSql = "SELECT * FROM notifications 
                WHERE notificationId = ? AND id = ? AND type = 'spot_available_24h' 
                AND createdAt > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $checkStmt = $con->prepare($checkSql);
    $checkStmt->bind_param("ii", $notificationId, $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        // הוספה לסל הקניות
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        if (!in_array($workshopId, $_SESSION['cart'])) {
            $_SESSION['cart'][] = $workshopId;
        }
        
        // 🔒 תיקון: prepared statement לעדכון סטטוס
        $updateSql = "UPDATE notifications SET status = 'read' WHERE notificationId = ?";
        $updateStmt = $con->prepare($updateSql);
        $updateStmt->bind_param("i", $notificationId);
        $updateStmt->execute();
        
        // 🔒 תיקון: prepared statement למחיקה מרשימת המתנה
        $deleteWaitlistSql = "DELETE FROM notifications 
                             WHERE id = ? AND workshopId = ? AND type = 'waitlist'";
        $deleteWaitlistStmt = $con->prepare($deleteWaitlistSql);
        $deleteWaitlistStmt->bind_param("ii", $userId, $workshopId);
        $deleteWaitlistStmt->execute();
        
        error_log("DEBUG: User $userId confirmed - removed from waitlist completely");
        
        return [
            'redirect' => true,
            'location' => 'cart.php',
            'params' => ['success' => 'אישרת בהצלחה את השתתפותך! הסדנה נוספה לסל הקניות שלך.']
        ];
    } else {
        return [
            'redirect' => true,
            'location' => 'profile.php',
            'params' => ['error' => 'פג תוקף ההזדמנות או שההתראה לא נמצאה.']
        ];
    }
}

function handleDeclineWaitlistSpot($con, $userId, $postData) {
    // 🔒 תיקון: וידוא שהנתונים הם מספרים
    $notificationId = intval($postData['notificationId']);
    $workshopId = intval($postData['workshopId']);
    
    // 🔒 תיקון: prepared statement לעדכון ההתראה
    $updateSql = "UPDATE notifications SET status = 'read' WHERE notificationId = ?";
    $updateStmt = $con->prepare($updateSql);
    $updateStmt->bind_param("i", $notificationId);
    $updateStmt->execute();
    
    // 🔒 תיקון: prepared statement לשליפת שם הסדנה
    $workshopNameSql = "SELECT workshopName FROM workshops WHERE workshopId = ?";
    $workshopNameStmt = $con->prepare($workshopNameSql);
    $workshopNameStmt->bind_param("i", $workshopId);
    $workshopNameStmt->execute();
    $workshopNameResult = $workshopNameStmt->get_result();
    $workshopName = $workshopNameResult->fetch_assoc()['workshopName'];
    
    $declinedMessage = "רשום לעדכונים עתידיים על סדנה: " . $workshopName;
    
    // 🔒 תיקון: prepared statement להוספת declined
    $addDeclinedSql = "INSERT INTO notifications (id, workshopId, message, type, status, createdAt) 
                      VALUES (?, ?, ?, 'declined_waitlist', 'waiting', NOW())";
    $addDeclinedStmt = $con->prepare($addDeclinedSql);
    $addDeclinedStmt->bind_param("iis", $userId, $workshopId, $declinedMessage);
    $addDeclinedStmt->execute();
    
    // 🔒 תיקון: prepared statement למחיקה מרשימת המתנה
    $deleteWaitlistSql = "DELETE FROM notifications 
                         WHERE id = ? AND workshopId = ? AND type = 'waitlist'";
    $deleteWaitlistStmt = $con->prepare($deleteWaitlistSql);
    $deleteWaitlistStmt->bind_param("ii", $userId, $workshopId);
    $deleteWaitlistStmt->execute();
    
    error_log("DEBUG: User $userId declined - moved to declined_waitlist for future updates");
    
    // שליחת הודעה למשתמש הבא ברשימה
    automaticNotifyNextInWaitlist($con, $workshopId);
    
    return [
        'redirect' => true,
        'params' => ['success' => 'דחית את ההזדמנות. נעדכן אותך כשתהיה סדנה חדשה מאותו סוג.']
    ];
}

function handleCancelWaitlist($con, $userId, $postData) {
    // 🔒 תיקון: וידוא שהנתונים הם מספרים
    $notificationId = intval($postData['notificationId']);
    $workshopId = intval($postData['workshopId']);
    
    $con->begin_transaction();
    
    try {
        // 🔒 תיקון: prepared statement למחיקה מרשימת המתנה
        $deleteWaitlistSql = "DELETE FROM notifications 
                            WHERE notificationId = ? AND id = ? AND type = 'waitlist'";
        $deleteWaitlistStmt = $con->prepare($deleteWaitlistSql);
        $deleteWaitlistStmt->bind_param("ii", $notificationId, $userId);
        
        if ($deleteWaitlistStmt->execute()) {
            // 🔒 תיקון: prepared statement למחיקת התראה 24h
            $checkNotifiedSql = "DELETE FROM notifications 
                                WHERE id = ? AND workshopId = ? AND type = 'spot_available_24h' AND status = 'unread'";
            $checkNotifiedStmt = $con->prepare($checkNotifiedSql);
            $checkNotifiedStmt->bind_param("ii", $userId, $workshopId);
            $checkNotifiedStmt->execute();
            
            // Clean up statements
            $deleteWaitlistStmt->close();
            $checkNotifiedStmt->close();
            
            $con->commit();
            
            // Only notify next in waitlist after successful commit
            automaticNotifyNextInWaitlist($con, $workshopId);
            
            return [
                'redirect' => true,
                'params' => ['success' => 'הוסרת בהצלחה מרשימת ההמתנה. המקום הועבר למשתמש הבא ברשימה.']
            ];
        } else {
            $con->rollback();
            return [
                'redirect' => true,
                'params' => ['error' => 'שגיאה בהסרה מרשימת ההמתנה.']
            ];
        }
    } catch (Exception $e) {
        $con->rollback();
        error_log("Error in handleCancelWaitlist: " . $e->getMessage());
        return [
            'redirect' => true,
            'params' => ['error' => 'שגיאה בהסרה מרשימת ההמתנה.']
        ];
    }
}

function handleUpdateProfile($con, $userId, $postData) {
    // 🔒 תיקון: ניקוי וולידציה של נתונים
    $fname = trim($postData['Fname']);
    $lname = trim($postData['Lname']);
    $email = trim($postData['Email']);
    $password = $postData['password'];
    
    // בדיקת תקינות נתונים
    if (empty($fname) || empty($lname) || empty($email)) {
        return [
            'redirect' => true,
            'params' => ['error' => 'שם פרטי, שם משפחה ואימייל הם שדות חובה']
        ];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [
            'redirect' => true,
            'params' => ['error' => 'כתובת אימייל לא תקינה']
        ];
    }
    
    // 🔒 תיקון: prepared statement לעדכון
    if (!empty($password)) {
        // עדכון עם סיסמה מוצפנת
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $updateSql = "UPDATE users SET Fname = ?, Lname = ?, Email = ?, Password = ? WHERE id = ?";
        $updateStmt = $con->prepare($updateSql);
        $updateStmt->bind_param("ssssi", $fname, $lname, $email, $hashedPassword, $userId);
    } else {
        // עדכון ללא סיסמה
        $updateSql = "UPDATE users SET Fname = ?, Lname = ?, Email = ? WHERE id = ?";
        $updateStmt = $con->prepare($updateSql);
        $updateStmt->bind_param("sssi", $fname, $lname, $email, $userId);
    }
    
    if ($updateStmt->execute()) {
        // עדכון שם בסשן
        $_SESSION['Fname'] = $fname;
        return [
            'redirect' => true,
            'params' => ['success' => 'פרטי הפרופיל עודכנו בהצלחה']
        ];
    } else {
        return [
            'redirect' => true,
            'params' => ['error' => 'שגיאה בעדכון הפרופיל: ' . $updateStmt->error]
        ];
    }
}

function handleCancelRegistration($con, $userId, $postData) {
    // 🔒 תיקון: וידוא שהנתונים הם מספרים
    $registrationId = intval($postData['registrationId']);
    $workshopId = intval($postData['workshopId']);
    $amountPaid = floatval($postData['amountPaid']);
    $cancellationType = $postData['cancellationType'] ?? 'with_refund';
    
    error_log("DEBUG: Starting cancellation for registration $registrationId, workshop $workshopId, user $userId");
    
    // 🔒 תיקון: prepared statement לבדיקת תאריך הסדנה
    $checkSql = "SELECT w.date, w.workshopName FROM workshops w WHERE w.workshopId = ?";
    $checkStmt = $con->prepare($checkSql);
    $checkStmt->bind_param("i", $workshopId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $workshop = $checkResult->fetch_assoc();
    
    error_log("DEBUG: Workshop details - Name: {$workshop['workshopName']}, Date: {$workshop['date']}");
    
    $workshopDate = new DateTime($workshop['date']);
    $currentDate = new DateTime();
    $interval = $currentDate->diff($workshopDate);
    $hoursRemaining = ($interval->days * 24) + $interval->h;
    
    error_log("DEBUG: Hours remaining until workshop: $hoursRemaining");
    
    // וידוא שהסדנה עוד לא התחילה
    if ($workshopDate > $currentDate) {
        // קביעת סכום ההחזר לפי סוג הביטול
        $refundAmount = 0;
        if ($cancellationType === 'with_refund' && $hoursRemaining >= 48) {
            $refundAmount = $amountPaid * 0.8;
        }
        
        error_log("DEBUG: Cancellation type: $cancellationType, Refund amount: $refundAmount");
        
        // Start transaction
        $con->begin_transaction();
        
        try {
            // 🔒 תיקון: prepared statement למחיקת הרשומה
            $deleteSql = "DELETE FROM registration WHERE registrationId = ? AND id = ?";
            $deleteStmt = $con->prepare($deleteSql);
            $deleteStmt->bind_param("ii", $registrationId, $userId);
            
            if ($deleteStmt->execute()) {
                error_log("DEBUG: Successfully deleted registration record");
                
                // הוספת התראה למנהל על החזר כספי אם נדרש
                if ($refundAmount > 0) {
                    $refundMessage = "החזר כספי בסך ₪" . $refundAmount . " עבור ביטול סדנה: " . $workshop['workshopName'];
                    $adminNotifSql = "INSERT INTO notifications (id, workshopId, message, type, status, createdAt) 
                                    VALUES (1, ?, ?, 'refund', 'unread', NOW())";
                    $adminNotifStmt = $con->prepare($adminNotifSql);
                    $adminNotifStmt->bind_param("is", $workshopId, $refundMessage);
                    $adminNotifStmt->execute();
                    error_log("DEBUG: Created refund notification for admin");
                }
                
                $con->commit();
                error_log("DEBUG: Transaction committed successfully");
                
                // Now notify next person in waitlist
                error_log("DEBUG: Attempting to notify next person in waitlist");
                automaticNotifyNextInWaitlist($con, $workshopId);
                
                $successMessage = $refundAmount > 0 ? 
                    "ההרשמה בוטלה בהצלחה. סכום החזר: ₪" . $refundAmount :
                    "ההרשמה בוטלה בהצלחה. לא ניתן החזר כספי בשל ביטול מאוחר.";
                
                return [
                    'redirect' => true,
                    'params' => ['success' => $successMessage]
                ];
            } else {
                $con->rollback();
                error_log("ERROR: Failed to delete registration: " . $deleteStmt->error);
                return [
                    'redirect' => true,
                    'params' => ['error' => 'שגיאה בביטול ההרשמה: ' . $deleteStmt->error]
                ];
            }
        } catch (Exception $e) {
            $con->rollback();
            error_log("ERROR in handleCancelRegistration: " . $e->getMessage());
            return [
                'redirect' => true,
                'params' => ['error' => 'שגיאה בביטול ההרשמה']
            ];
        }
    } else {
        error_log("DEBUG: Cannot cancel - workshop already started");
        return [
            'redirect' => true,
            'params' => ['error' => 'לא ניתן לבטל הרשמה לסדנה שכבר התחילה']
        ];
    }
}

function handleSubmitReview($con, $userId, $postData) {
    // 🔒 תיקון: ניקוי וולידציה של נתונים
    $rating = intval($postData['rating']);
    $comment = trim($postData['comment']);
    $workshopId = intval($postData['workshopId']);
    
    // בדיקת תקינות נתונים
    if ($rating < 1 || $rating > 5) {
        return [
            'redirect' => true,
            'params' => ['error' => 'דירוג חייב להיות בין 1 ל-5']
        ];
    }
    
    if (empty($comment)) {
        return [
            'redirect' => true,
            'params' => ['error' => 'תגובה היא שדה חובה']
        ];
    }
    
    // 🔒 תיקון: prepared statement לבדיקת חוות דעת קיימת
    $checkReviewSql = "SELECT * FROM reviews WHERE id = ? AND workshopId = ?";
    $checkReviewStmt = $con->prepare($checkReviewSql);
    $checkReviewStmt->bind_param("ii", $userId, $workshopId);
    $checkReviewStmt->execute();
    $checkReviewResult = $checkReviewStmt->get_result();
    
    if ($checkReviewResult->num_rows > 0) {
        // 🔒 תיקון: prepared statement לעדכון חוות דעת קיימת
        $updateReviewSql = "UPDATE reviews SET rating = ?, comment = ?, createdAt = NOW() WHERE id = ? AND workshopId = ?";
        $updateReviewStmt = $con->prepare($updateReviewSql);
        $updateReviewStmt->bind_param("isii", $rating, $comment, $userId, $workshopId);
        
        if ($updateReviewStmt->execute()) {
            return [
                'redirect' => true,
                'params' => ['success' => 'חוות הדעת עודכנה בהצלחה']
            ];
        } else {
            return [
                'redirect' => true,
                'params' => ['error' => 'שגיאה בעדכון חוות הדעת: ' . $updateReviewStmt->error]
            ];
        }
    } else {
        // 🔒 תיקון: prepared statement להוספת חוות דעת חדשה
        $addReviewSql = "INSERT INTO reviews (id, workshopId, rating, comment, createdAt) VALUES (?, ?, ?, ?, NOW())";
        $addReviewStmt = $con->prepare($addReviewSql);
        $addReviewStmt->bind_param("iiis", $userId, $workshopId, $rating, $comment);
        
        if ($addReviewStmt->execute()) {
            return [
                'redirect' => true,
                'params' => ['success' => 'חוות הדעת נוספה בהצלחה']
            ];
        } else {
            return [
                'redirect' => true,
                'params' => ['error' => 'שגיאה בהוספת חוות הדעת: ' . $addReviewStmt->error]
            ];
        }
    }
}
?>