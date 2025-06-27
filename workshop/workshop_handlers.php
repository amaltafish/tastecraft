<?php
// workshop/workshop_handlers.php - טיפול בפעולות POST

function handleWorkshopActions($con, $userId, $workshopId) {
    $result = ['redirect' => false, 'params' => []];
    
    if (isset($_POST['joinWaitlist'])) {
        $result = handleJoinWaitlist($con, $userId, $workshopId);
    }
    elseif (isset($_POST['submitReview'])) {
        $result = handleSubmitReview($con, $userId, $workshopId, $_POST);
    }
    
    return $result;
}

function handleJoinWaitlist($con, $userId, $workshopId) {
    error_log("DEBUG: User $userId trying to join waitlist for workshop $workshopId");
    
    // בדיקה אם המשתמש כבר ברשימה (כל הסטטוסים)
    $checkWaitlistSql = "SELECT * FROM notifications WHERE id = ? AND workshopId = ? AND type = 'waitlist'";
    $checkWaitlistStmt = $con->prepare($checkWaitlistSql);
    $checkWaitlistStmt->bind_param("ii", $userId, $workshopId);
    $checkWaitlistStmt->execute();
    $checkResult = $checkWaitlistStmt->get_result();
    
    if ($checkResult->num_rows == 0) {
        // שליפת שם הסדנה
        $workshopNameSql = "SELECT workshopName FROM workshops WHERE workshopId = ?";
        $workshopNameStmt = $con->prepare($workshopNameSql);
        $workshopNameStmt->bind_param("i", $workshopId);
        $workshopNameStmt->execute();
        $workshopNameResult = $workshopNameStmt->get_result();
        
        if ($workshopNameResult->num_rows > 0) {
            $workshopInfo = $workshopNameResult->fetch_assoc();
            $waitlistMessage = "נרשמת לרשימת המתנה לסדנה: " . $workshopInfo['workshopName'];
            
            $addWaitlistSql = "INSERT INTO notifications (id, workshopId, message, type, status, createdAt) VALUES (?, ?, ?, 'waitlist', 'waiting', NOW())";
            $addWaitlistStmt = $con->prepare($addWaitlistSql);
            $addWaitlistStmt->bind_param("iis", $userId, $workshopId, $waitlistMessage);
            
            if ($addWaitlistStmt->execute()) {
                error_log("DEBUG: Successfully added user $userId to waitlist for workshop $workshopId");
                return [
                    'redirect' => true,
                    'params' => ['success' => 'נוספת בהצלחה לרשימת ההמתנה!']
                ];
            } else {
                error_log("ERROR: Failed to add user $userId to waitlist: " . $addWaitlistStmt->error);
                return [
                    'redirect' => true,
                    'params' => ['error' => 'שגיאה בהוספה לרשימת המתנה: ' . $addWaitlistStmt->error]
                ];
            }
        } else {
            return [
                'redirect' => true,
                'params' => ['error' => 'לא נמצאה סדנה.']
            ];
        }
    } else {
        $existingRecord = $checkResult->fetch_assoc();
        return [
            'redirect' => true,
            'params' => ['error' => 'אתה כבר רשום לרשימת ההמתנה לסדנה זו. סטטוס: ' . $existingRecord['status']]
        ];
    }
}

function handleSubmitReview($con, $userId, $workshopId, $postData) {
    $rating = $postData['rating'];
    $comment = $postData['comment'];
    
    // בדיקה אם כבר קיימת חוות דעת של המשתמש לסדנה זו
    $checkReviewSql = "SELECT * FROM reviews WHERE id = ? AND workshopId = ?";
    $checkReviewStmt = $con->prepare($checkReviewSql);
    $checkReviewStmt->bind_param("ii", $userId, $workshopId);
    $checkReviewStmt->execute();
    $checkReviewResult = $checkReviewStmt->get_result();
    
    if ($checkReviewResult->num_rows > 0) {
        // עדכון חוות דעת קיימת
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
        // הוספת חוות דעת חדשה
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