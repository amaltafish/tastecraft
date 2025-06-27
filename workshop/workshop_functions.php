<?php
// workshop/workshop_functions.php - פונקציות סדנה

function getWorkshopData($con, $workshopId, $userId) {
    // שליפת פרטי הסדנה עם מקומות נעולים
    $sql = "SELECT w.*, 
            COUNT(DISTINCT r.registrationId) AS registeredCount,
            COUNT(DISTINCT CASE 
                WHEN n.status = 'notified' AND n.type = 'waitlist' 
                AND n.createdAt > DATE_SUB(NOW(), INTERVAL 24 HOUR) 
                THEN n.id END) AS lockedSeats
            FROM workshops w
            LEFT JOIN registration r ON w.workshopId = r.workshopId
            LEFT JOIN notifications n ON w.workshopId = n.workshopId
            WHERE w.workshopId = ?
            GROUP BY w.workshopId";

    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $workshopId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        return false;
    }

    $workshop = $result->fetch_assoc();
    
    // חישוב מקומות פנויים עם מקומות נעולים
    $availableSeats = $workshop['maxParticipants'] - $workshop['registeredCount'] - $workshop['lockedSeats'];

    // שליפת האלרגיות של הסדנה
    $allergies = getWorkshopAllergies($con, $workshopId);
    
    // שליפת ביקורות ודירוגים
    $reviewsData = getWorkshopReviews($con, $workshopId);
    
    // בדיקות משתמש
    $userChecks = getUserWorkshopStatus($con, $userId, $workshopId);
    
    // בדיקה אם הסדנה כבר התקיימה
    $workshopDate = new DateTime($workshop['date']);
    $currentDate = new DateTime();
    $isPastWorkshop = $currentDate > $workshopDate;
    
    // בדיקת סטטוס רשימת המתנה
    $waitlistData = getUserWaitlistStatus($con, $userId, $workshopId);
    
    // בדיקה אם המשתמש מקבל התראה של 24 שעות
    $notification24hData = getUser24hNotification($con, $userId, $workshopId);
    
    return [
        'workshop' => $workshop,
        'availableSeats' => $availableSeats,
        'allergies' => $allergies,
        'reviews' => $reviewsData['reviews'],
        'avgRating' => $reviewsData['avgRating'],
        'isRegistered' => $userChecks['isRegistered'],
        'isInCart' => $userChecks['isInCart'],
        'hasReview' => $userChecks['hasReview'],
        'userReview' => $userChecks['userReview'],
        'isPastWorkshop' => $isPastWorkshop,
        'isInWaitlist' => $waitlistData['isInWaitlist'],
        'waitlistData' => $waitlistData['waitlistData'],
        'waitlistStatus' => $waitlistData['waitlistStatus'],
        'hoursRemaining' => $waitlistData['hoursRemaining'],
        'minutesRemaining' => $waitlistData['minutesRemaining'],
        'waitlistPosition' => $waitlistData['waitlistPosition'],
        'userHas24hNotification' => $notification24hData['hasNotification'],
        'notification24hData' => $notification24hData['data'],
        'hoursRemaining24h' => $notification24hData['hoursRemaining']
    ];
}

function getWorkshopAllergies($con, $workshopId) {
    $allergiesSql = "SELECT o.optionName 
                    FROM workshopOptions wo
                    JOIN options o ON wo.optionId = o.optionId
                    WHERE wo.workshopId = ?";

    $allergiesStmt = $con->prepare($allergiesSql);
    $allergiesStmt->bind_param("i", $workshopId);
    $allergiesStmt->execute();
    $allergiesResult = $allergiesStmt->get_result();

    $allergies = [];
    while ($row = $allergiesResult->fetch_assoc()) {
        $allergies[] = $row['optionName'];
    }
    
    return $allergies;
}

function getWorkshopReviews($con, $workshopId) {
    // שליפת ביקורות
    $reviewsSql = "SELECT r.*, u.Fname, u.Lname
                  FROM reviews r
                  JOIN users u ON r.id = u.id
                  WHERE r.workshopId = ?
                  ORDER BY r.createdAt DESC";

    $reviewsStmt = $con->prepare($reviewsSql);
    $reviewsStmt->bind_param("i", $workshopId);
    $reviewsStmt->execute();
    $reviewsResult = $reviewsStmt->get_result();

    // חישוב דירוג ממוצע
    $averageRatingSql = "SELECT AVG(rating) as avgRating FROM reviews WHERE workshopId = ?";
    $avgRatingStmt = $con->prepare($averageRatingSql);
    $avgRatingStmt->bind_param("i", $workshopId);
    $avgRatingStmt->execute();
    $avgRatingResult = $avgRatingStmt->get_result();
    $avgRating = $avgRatingResult->fetch_assoc()['avgRating'] ?? 0;
    $avgRating = round($avgRating, 1);
    
    return [
        'reviews' => $reviewsResult,
        'avgRating' => $avgRating
    ];
}

function getUserWorkshopStatus($con, $userId, $workshopId) {
    // בדיקה אם המשתמש כבר נרשם לסדנה זו
    $isRegisteredSql = "SELECT * FROM registration WHERE id = ? AND workshopId = ?";
    $isRegisteredStmt = $con->prepare($isRegisteredSql);
    $isRegisteredStmt->bind_param("ii", $userId, $workshopId);
    $isRegisteredStmt->execute();
    $isRegisteredResult = $isRegisteredStmt->get_result();
    $isRegistered = $isRegisteredResult->num_rows > 0;

    // בדיקה אם הסדנה כבר בסל הקניות
    $isInCart = false;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        $isInCart = in_array($workshopId, $_SESSION['cart']);
    }

    // בדיקה אם המשתמש כבר כתב חוות דעת לסדנה זו
    $hasReviewSql = "SELECT * FROM reviews WHERE id = ? AND workshopId = ?";
    $hasReviewStmt = $con->prepare($hasReviewSql);
    $hasReviewStmt->bind_param("ii", $userId, $workshopId);
    $hasReviewStmt->execute();
    $hasReviewResult = $hasReviewStmt->get_result();
    $hasReview = $hasReviewResult->num_rows > 0;
    $userReview = $hasReview ? $hasReviewResult->fetch_assoc() : null;
    
    return [
        'isRegistered' => $isRegistered,
        'isInCart' => $isInCart,
        'hasReview' => $hasReview,
        'userReview' => $userReview
    ];
}

function getUserWaitlistStatus($con, $userId, $workshopId) {
    $isInWaitlistSql = "SELECT n.*, 
                        CASE 
                            WHEN n.status = 'notified' THEN 
                                GREATEST(0, 24 - TIMESTAMPDIFF(HOUR, n.createdAt, NOW()))
                            ELSE NULL
                        END as hours_remaining,
                        CASE 
                            WHEN n.status = 'notified' THEN 
                                GREATEST(0, (24 * 60) - TIMESTAMPDIFF(MINUTE, n.createdAt, NOW()))
                            ELSE NULL
                        END as minutes_remaining
                        FROM notifications n
                        WHERE n.id = ? AND n.workshopId = ? AND n.type = 'waitlist' 
                        AND n.status IN ('waiting', 'notified', 'declined')";
    $isInWaitlistStmt = $con->prepare($isInWaitlistSql);
    $isInWaitlistStmt->bind_param("ii", $userId, $workshopId);
    $isInWaitlistStmt->execute();
    $isInWaitlistResult = $isInWaitlistStmt->get_result();
    $isInWaitlist = $isInWaitlistResult->num_rows > 0;
    $waitlistData = $isInWaitlist ? $isInWaitlistResult->fetch_assoc() : null;
    $waitlistStatus = $waitlistData ? $waitlistData['status'] : null;
    $hoursRemaining = $waitlistData ? $waitlistData['hours_remaining'] : null;
    $minutesRemaining = $waitlistData ? $waitlistData['minutes_remaining'] : null;

    // קבלת מיקום ברשימת המתנה
    $waitlistPosition = 0;
    if ($isInWaitlist && $waitlistStatus === 'waiting') {
        $positionSql = "SELECT COUNT(*) + 1 as position 
                       FROM notifications 
                       WHERE workshopId = ? AND type = 'waitlist' AND status = 'waiting'
                       AND createdAt < (SELECT createdAt FROM notifications WHERE id = ? AND workshopId = ? AND type = 'waitlist')";
        $positionStmt = $con->prepare($positionSql);
        $positionStmt->bind_param("iii", $workshopId, $userId, $workshopId);
        $positionStmt->execute();
        $positionResult = $positionStmt->get_result();
        if ($positionResult->num_rows > 0) {
            $waitlistPosition = $positionResult->fetch_assoc()['position'];
        }
    }
    
    return [
        'isInWaitlist' => $isInWaitlist,
        'waitlistData' => $waitlistData,
        'waitlistStatus' => $waitlistStatus,
        'hoursRemaining' => $hoursRemaining,
        'minutesRemaining' => $minutesRemaining,
        'waitlistPosition' => $waitlistPosition
    ];
}

function getUser24hNotification($con, $userId, $workshopId) {
    $userHas24hNotificationSql = "SELECT * FROM notifications 
                                 WHERE id = ? AND workshopId = ? AND type = 'spot_available_24h' 
                                 AND status = 'unread' 
                                 AND createdAt > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $userHas24hStmt = $con->prepare($userHas24hNotificationSql);
    $userHas24hStmt->bind_param("ii", $userId, $workshopId);
    $userHas24hStmt->execute();
    $userHas24hResult = $userHas24hStmt->get_result();
    $userHas24hNotification = $userHas24hResult->num_rows > 0;

    // אם יש התראה 24 שעות, קבל את הפרטים
    $notification24hData = null;
    $hoursRemaining24h = null;
    if ($userHas24hNotification) {
        $notification24hData = $userHas24hResult->fetch_assoc();
        // חישוב זמן נותר
        $createdTime = new DateTime($notification24hData['createdAt']);
        $currentTime = new DateTime();
        $interval = $createdTime->diff($currentTime);
        $hoursElapsed = ($interval->days * 24) + $interval->h + ($interval->i / 60);
        $hoursRemaining24h = max(0, 24 - $hoursElapsed);
    }
    
    return [
        'hasNotification' => $userHas24hNotification,
        'data' => $notification24hData,
        'hoursRemaining' => $hoursRemaining24h
    ];
}
?>