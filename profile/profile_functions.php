<?php
// profile/profile_functions.php - פונקציות פרופיל

function getProfileData($con, $userId) {
    // הסרת הניקוי האוטומטי - יתבצע רק בדף האדמין
    
    $data = [];
    
    // שליפת פרטי המשתמש
    $data['user'] = getUserDetails($con, $userId);
    
    // שליפת הרשמות
    $data['registrations'] = getUserRegistrations($con, $userId);
    
    // שליפת רשימות המתנה
    $data['waitlists'] = getUserWaitlists($con, $userId);
    
    // שליפת התראות
    $notificationsData = getUserNotifications($con, $userId);
    $data['notifications'] = $notificationsData['notifications'];
    $data['urgentCount'] = $notificationsData['urgentCount'];
    
    // שליפת ביקורות
    $data['reviews'] = getUserReviews($con, $userId);
    
    return $data;
}

function getUserDetails($con, $userId) {
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getUserRegistrations($con, $userId) {
    $registerSql = "SELECT r.*, w.workshopName, w.date, w.img, w.location, 
                   (SELECT COUNT(*) FROM reviews rev WHERE rev.id = r.id AND rev.workshopId = r.workshopId) AS hasReview
                   FROM registration r
                   JOIN workshops w ON r.workshopId = w.workshopId
                   WHERE r.id = ?
                   ORDER BY w.date DESC";

    $registerStmt = $con->prepare($registerSql);
    $registerStmt->bind_param("i", $userId);
    $registerStmt->execute();
    return $registerStmt->get_result();
}

function getUserWaitlists($con, $userId) {
    $waitlistSql = "SELECT n.*, w.workshopName, w.date, w.img, w.location, w.price,
                   CASE 
                       WHEN n.status = 'waiting' THEN 'ממתין ברשימה'
                       WHEN n.status = 'notified' THEN 'נשלחה התראה - יש לך 24 שעות!'
                       WHEN n.status = 'expired' THEN 'פג תוקף - חזר לרשימה'
                       WHEN n.status = 'declined' THEN 'דחה - ממתין לעדכונים חדשים'
                       ELSE n.status
                   END as status_display,
                   CASE 
                       WHEN n.status = 'notified' THEN 
                           GREATEST(0, 24 - TIMESTAMPDIFF(HOUR, n.createdAt, NOW()))
                       ELSE NULL
                   END as hours_remaining,
                   CASE 
                       WHEN n.status = 'notified' THEN 
                           GREATEST(0, (24 * 60) - TIMESTAMPDIFF(MINUTE, n.createdAt, NOW()))
                       ELSE NULL
                   END as minutes_remaining,
                   CASE 
                       WHEN n.status = 'waiting' THEN 
                           (SELECT COUNT(*) + 1 
                            FROM notifications n2 
                            WHERE n2.workshopId = n.workshopId 
                            AND n2.type = 'waitlist' 
                            AND n2.status = 'waiting'
                            AND n2.createdAt < n.createdAt)
                       ELSE NULL
                   END as queue_position
                   FROM notifications n
                   JOIN workshops w ON n.workshopId = w.workshopId
                   WHERE n.id = ? AND n.type = 'waitlist' 
                   AND n.status IN ('waiting', 'notified', 'expired', 'declined')
                   ORDER BY w.date ASC";

    $waitlistStmt = $con->prepare($waitlistSql);
    $waitlistStmt->bind_param("i", $userId);
    $waitlistStmt->execute();
    return $waitlistStmt->get_result();
}

function getUserNotifications($con, $userId) {
    // Start transaction to ensure consistency
    $con->begin_transaction();
    
    try {
        $notificationsSql = "SELECT n.*, w.workshopName,
                            CASE 
                                WHEN n.type = 'spot_available_24h' AND n.status = 'unread' 
                                AND n.createdAt > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1
                                ELSE 0
                            END as is_urgent,
                            CASE 
                                WHEN n.type = 'spot_available_24h' AND n.status = 'unread' THEN 
                                    GREATEST(0, 24 - TIMESTAMPDIFF(HOUR, n.createdAt, NOW()))
                                ELSE NULL
                            END as hours_remaining
                            FROM notifications n
                            LEFT JOIN workshops w ON n.workshopId = w.workshopId
                            WHERE n.id = ? 
                            AND n.type IN ('spot_available', 'waitlist', 'spot_available_24h', 'waitlist_expired', 'refund')
                            ORDER BY 
                                CASE 
                                    WHEN n.type = 'spot_available_24h' AND n.createdAt > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND n.status = 'unread' THEN 0
                                    ELSE 1
                                END ASC, 
                                n.createdAt DESC";
        
        $notificationsStmt = $con->prepare($notificationsSql);
        $notificationsStmt->bind_param("i", $userId);
        $notificationsStmt->execute();
        $notifications = $notificationsStmt->get_result();

        // Mark spot_available_24h notifications as seen
        $updateSql = "UPDATE notifications 
                     SET status = 'seen'
                     WHERE id = ? 
                     AND type = 'spot_available_24h' 
                     AND status = 'unread'
                     AND createdAt <= NOW()";
        
        $updateStmt = $con->prepare($updateSql);
        $updateStmt->bind_param("i", $userId);
        $updateStmt->execute();

        // Calculate urgent notifications and build array
        $urgentCount = 0;
        $notificationsArray = [];
        while ($notification = $notifications->fetch_assoc()) {
            if ($notification['is_urgent'] == 1) {
                $urgentCount++;
            }
            $notificationsArray[] = $notification;
        }

        $con->commit();
        
        return [
            'notifications' => $notificationsArray,
            'urgentCount' => $urgentCount
        ];
    } catch (Exception $e) {
        $con->rollback();
        error_log("Error in getUserNotifications: " . $e->getMessage());
        return [
            'notifications' => [],
            'urgentCount' => 0
        ];
    }
}

function getUserReviews($con, $userId) {
    $reviewsSql = "SELECT r.*, w.workshopName, w.date, w.img
                  FROM reviews r
                  JOIN workshops w ON r.workshopId = w.workshopId
                  WHERE r.id = ?
                  ORDER BY r.createdAt DESC";

    $reviewsStmt = $con->prepare($reviewsSql);
    $reviewsStmt->bind_param("i", $userId);
    $reviewsStmt->execute();
    return $reviewsStmt->get_result();
}
?>