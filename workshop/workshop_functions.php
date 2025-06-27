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
    $seats = getAvailableSeats($con, $workshopId);
    $workshop = array_merge($workshop, $seats); // Add seats data to workshop array

    // Get allergies
    $allergies = getWorkshopAllergies($con, $workshopId);

    // Get user-specific data
    $isRegistered = checkRegistration($con, $userId, $workshopId);
    $isInCart = checkInCart($con, $userId, $workshopId);
    $hasReview = false;
    $userReview = null;
    $isPastWorkshop = strtotime($workshop['date']) < time();
    
    // Get waitlist data
    $waitlistData = getWaitlistData($con, $userId, $workshopId);
    $isInWaitlist = $waitlistData !== null;
    $waitlistStatus = $isInWaitlist ? $waitlistData['status'] : null;
    $waitlistPosition = $isInWaitlist && $waitlistStatus === 'waiting' ? getWaitlistPosition($con, $userId, $workshopId) : null;
    
    // Get 24h notification data
    $notification24hData = get24hNotificationData($con, $userId, $workshopId);
    $userHas24hNotification = $notification24hData !== null;
    $hoursRemaining24h = $userHas24hNotification ? getHoursRemaining($notification24hData['createdAt']) : null;
    
    // Reviews data
    if ($isRegistered && $isPastWorkshop) {
        $userReview = getUserReview($con, $userId, $workshopId);
        $hasReview = $userReview !== null;
    }
    
    return [
        'workshop' => $workshop,
        'allergies' => $allergies,
        'isRegistered' => $isRegistered,
        'isInCart' => $isInCart,
        'hasReview' => $hasReview,
        'userReview' => $userReview,
        'isPastWorkshop' => $isPastWorkshop,
        'reviews' => getWorkshopReviews($con, $workshopId),
        'avgRating' => calculateAverageRating($con, $workshopId),
        'isInWaitlist' => $isInWaitlist,
        'waitlistData' => $waitlistData,
        'waitlistStatus' => $waitlistStatus,
        'waitlistPosition' => $waitlistPosition,
        'userHas24hNotification' => $userHas24hNotification,
        'notification24hData' => $notification24hData,
        'hoursRemaining24h' => $hoursRemaining24h
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
    $sql = "SELECT r.*, u.Fname, u.Lname 
            FROM reviews r
            JOIN users u ON r.id = u.id
            WHERE r.workshopId = ?
            ORDER BY r.createdAt DESC";

    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $workshopId);
    $stmt->execute();
    return $stmt->get_result();
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

function checkRegistration($con, $userId, $workshopId) {
    $sql = "SELECT * FROM registration WHERE id = ? AND workshopId = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ii", $userId, $workshopId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

function checkInCart($con, $userId, $workshopId) {
    return isset($_SESSION['cart']) && is_array($_SESSION['cart']) && in_array($workshopId, $_SESSION['cart']);
}

function getWaitlistData($con, $userId, $workshopId) {
    $sql = "SELECT n.*, 
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
            WHERE n.id = ? AND n.workshopId = ? 
            AND n.type = 'waitlist' 
            AND n.status IN ('waiting', 'notified', 'declined')";

    $stmt = $con->prepare($sql);
    $stmt->bind_param("ii", $userId, $workshopId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

function getWaitlistPosition($con, $userId, $workshopId) {
    $sql = "SELECT COUNT(*) + 1 as position 
            FROM notifications 
            WHERE workshopId = ? 
            AND type = 'waitlist' 
            AND status = 'waiting'
            AND createdAt < (
                SELECT createdAt 
                FROM notifications 
                WHERE id = ? 
                AND workshopId = ? 
                AND type = 'waitlist'
                AND status = 'waiting'
            )";
    
    $stmt = $con->prepare($sql);
    $stmt->bind_param("iii", $workshopId, $userId, $workshopId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0 ? $result->fetch_assoc()['position'] : null;
}

function get24hNotificationData($con, $userId, $workshopId) {
    $sql = "SELECT * FROM notifications 
            WHERE id = ? AND workshopId = ? 
            AND type = 'spot_available_24h' 
            AND status = 'unread' 
            AND createdAt > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ii", $userId, $workshopId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

function getHoursRemaining($createdAt) {
    $created = new DateTime($createdAt);
    $current = new DateTime();
    $interval = $created->diff($current);
    $hoursElapsed = ($interval->days * 24) + $interval->h + ($interval->i / 60);
    return max(0, 24 - $hoursElapsed);
}

function getUserReview($con, $userId, $workshopId) {
    $sql = "SELECT * FROM reviews WHERE id = ? AND workshopId = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ii", $userId, $workshopId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

function calculateAverageRating($con, $workshopId) {
    $sql = "SELECT AVG(rating) as avgRating FROM reviews WHERE workshopId = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $workshopId);
    $stmt->execute();
    $result = $stmt->get_result();
    $avgRating = $result->fetch_assoc()['avgRating'] ?? 0;
    return round($avgRating, 1);
}
?>