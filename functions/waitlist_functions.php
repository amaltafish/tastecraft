<?php
// functions/waitlist_functions.php - פונקציות רשימת המתנה עם תיקון Race Conditions

function automaticNotifyNextInWaitlist($con, $workshopId) {
    error_log("DEBUG: AUTO - Looking for next user in waitlist for workshop $workshopId");
    
    // 🔒 תיקון Race Condition: התחלת transaction
    $con->begin_transaction();
    
    try {
        // 🔒 נעילת השורה למניעת הפרעות
        $lockSql = "SELECT workshopId FROM workshops WHERE workshopId = ? FOR UPDATE";
        $lockStmt = $con->prepare($lockSql);
        $lockStmt->bind_param("i", $workshopId);
        $lockStmt->execute();
        
        // בדיקה שלא קיימת התראה פעילה כבר לסדנה זו
        $existingActiveNotificationSql = "SELECT COUNT(*) as count 
                                         FROM notifications 
                                         WHERE workshopId = ? 
                                         AND type = 'spot_available_24h' 
                                         AND status = 'unread' 
                                         AND createdAt > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                                         FOR UPDATE";
        $existingStmt = $con->prepare($existingActiveNotificationSql);
        $existingStmt->bind_param("i", $workshopId);
        $existingStmt->execute();
        $existingResult = $existingStmt->get_result();
        $existingCount = $existingResult->fetch_assoc()['count'];
        
        if ($existingCount > 0) {
            error_log("DEBUG: AUTO - Already has active 24h notification for workshop $workshopId, skipping");
            $con->rollback();
            return false;
        }
        
        // בדיקת קיבולת - ספירת מקומות רגילים + מקומות נעולים
        $capacitySql = "SELECT w.maxParticipants, 
                        COUNT(DISTINCT r.registrationId) AS registeredCount,
                        COUNT(DISTINCT CASE 
                            WHEN n.status = 'notified' AND n.type = 'waitlist' 
                            AND n.createdAt > DATE_SUB(NOW(), INTERVAL 24 HOUR) 
                            THEN n.id END) AS lockedSeats
                        FROM workshops w
                        LEFT JOIN registration r ON w.workshopId = r.workshopId
                        LEFT JOIN notifications n ON w.workshopId = n.workshopId
                        WHERE w.workshopId = ?
                        GROUP BY w.workshopId
                        FOR UPDATE";
        
        $capacityStmt = $con->prepare($capacitySql);
        $capacityStmt->bind_param("i", $workshopId);
        $capacityStmt->execute();
        $capacityResult = $capacityStmt->get_result();
        
        if ($capacityResult->num_rows > 0) {
            $workshop = $capacityResult->fetch_assoc();
            $totalOccupied = $workshop['registeredCount'] + $workshop['lockedSeats'];
            $availableSeats = $workshop['maxParticipants'] - $totalOccupied;
            
            error_log("DEBUG: Workshop $workshopId - Max: {$workshop['maxParticipants']}, Registered: {$workshop['registeredCount']}, Locked: {$workshop['lockedSeats']}, Available: $availableSeats");
            
            if ($availableSeats <= 0) {
                error_log("DEBUG: AUTO - No available seats for workshop $workshopId");
                $con->rollback();
                return false;
            }
        } else {
            error_log("DEBUG: AUTO - Workshop $workshopId not found");
            $con->rollback();
            return false;
        }
        
        // מציאת המשתמש הבא ברשימת המתנה - רק סטטוס 'waiting'
        $nextUserSql = "SELECT n.*, u.Fname, u.Email, w.workshopName 
                       FROM notifications n
                       JOIN users u ON n.id = u.id
                       JOIN workshops w ON n.workshopId = w.workshopId
                       WHERE n.workshopId = ? 
                       AND n.type = 'waitlist' 
                       AND n.status = 'waiting'
                       ORDER BY n.createdAt ASC
                       LIMIT 1
                       FOR UPDATE";
        
        $nextUserStmt = $con->prepare($nextUserSql);
        $nextUserStmt->bind_param("i", $workshopId);
        $nextUserStmt->execute();
        $nextResult = $nextUserStmt->get_result();
        
        if ($nextResult->num_rows > 0) {
            $nextUser = $nextResult->fetch_assoc();
            error_log("DEBUG: AUTO - Found next user: " . $nextUser['Fname'] . " (ID: " . $nextUser['id'] . ")");
            
            // בדיקה כפולה שהמשתמש לא קיבל כבר התראה
            $userExistingNotificationSql = "SELECT COUNT(*) as count 
                                           FROM notifications 
                                           WHERE id = ? 
                                           AND workshopId = ? 
                                           AND type = 'spot_available_24h' 
                                           AND status = 'unread' 
                                           AND createdAt > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                                           FOR UPDATE";
            $userExistingStmt = $con->prepare($userExistingNotificationSql);
            $userExistingStmt->bind_param("ii", $nextUser['id'], $workshopId);
            $userExistingStmt->execute();
            $userExistingResult = $userExistingStmt->get_result();
            $userExistingCount = $userExistingResult->fetch_assoc()['count'];
            
            if ($userExistingCount > 0) {
                error_log("DEBUG: AUTO - User {$nextUser['id']} already has active notification for workshop $workshopId, skipping");
                $con->rollback();
                return false;
            }
            
            // נעילת המקום על ידי עדכון הסטטוס ל'notified'
            $updateSql = "UPDATE notifications 
                         SET status = 'notified', 
                             message = CONCAT(message, ' - נשלחה הזדמנות ב: ', NOW()),
                             createdAt = NOW()
                         WHERE notificationId = ?";
            $updateStmt = $con->prepare($updateSql);
            $updateStmt->bind_param("i", $nextUser['notificationId']);
            
            if ($updateStmt->execute()) {
                // שליחת התראה 24h חדשה
                $notificationMessage = "🎉 התפנה מקום בסדנה: " . $nextUser['workshopName'] . "! יש לך 24 שעות לאשר השתתפותך.";
                $notifSql = "INSERT INTO notifications (id, workshopId, message, type, status, createdAt) 
                            VALUES (?, ?, ?, 'spot_available_24h', 'unread', NOW())";
                $notifStmt = $con->prepare($notifSql);
                $notifStmt->bind_param("iis", $nextUser['id'], $workshopId, $notificationMessage);
                
                if ($notifStmt->execute()) {
                    error_log("DEBUG: AUTO - LOCKED seat and sent 24h notification to user " . $nextUser['id']);
                    
                    // שליחת אימייל - רק פעם אחת!
                    if (function_exists('mail')) {
                        $subject = "🎉 התפנה מקום בסדנה - יש לך 24 שעות! - TasteCraft";
                        $emailMessage = "שלום {$nextUser['Fname']}, התפנה מקום בסדנה {$nextUser['workshopName']}. יש לך 24 שעות לאשר דרך הפרופיל שלך.";
                        $headers = "From: noreply@tastecraft.com\r\n";
                        @mail($nextUser['Email'], $subject, $emailMessage, $headers);
                        error_log("DEBUG: AUTO - Email sent to " . $nextUser['Email']);
                    }
                    
                    // 🔒 אישור Transaction
                    $con->commit();
                    return true;
                } else {
                    error_log("ERROR: Failed to create 24h notification");
                    $con->rollback();
                    return false;
                }
            } else {
                error_log("ERROR: Failed to update waitlist status");
                $con->rollback();
                return false;
            }
        } else {
            error_log("DEBUG: AUTO - No waiting users found for workshop $workshopId");
            $con->rollback();
            return false;
        }
        
    } catch (Exception $e) {
        error_log("ERROR in automaticNotifyNextInWaitlist: " . $e->getMessage());
        $con->rollback();
        return false;
    }
}

function cleanExpiredWaitlists($con) {
    // 🔒 תיקון: prepared statements
    
    // ניקוי רשימות המתנה של סדנאות שכבר עברו
    $pastWorkshopsSql = "UPDATE notifications n
                        JOIN workshops w ON n.workshopId = w.workshopId
                        SET n.status = 'workshop_completed', 
                            n.message = CONCAT(n.message, ' - הסדנה הושלמה ב: ', NOW())
                        WHERE w.date < NOW() 
                        AND n.type IN ('waitlist', 'declined_waitlist') 
                        AND n.status IN ('waiting', 'notified')";
    $con->query($pastWorkshopsSql);
    
    // ניקוי התראות 24h שפג תוקפן
    $expiredNotificationsSql = "UPDATE notifications 
                               SET status = 'expired', 
                                   message = CONCAT(message, ' - פג תוקף ב: ', NOW())
                               WHERE type = 'spot_available_24h' 
                               AND status = 'unread' 
                               AND createdAt < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $con->query($expiredNotificationsSql);
}

function handleExpiredNotificationsAutomatically($con) {
    error_log("DEBUG: AUTO - Checking for expired notifications");
    
    // מציאת התראות 24h שפג תוקפן
    $expiredSql = "SELECT n.*, w.workshopName, u.Fname, u.Email
                  FROM notifications n
                  JOIN workshops w ON n.workshopId = w.workshopId
                  JOIN users u ON n.id = u.id
                  WHERE n.type = 'spot_available_24h' 
                  AND n.status = 'unread'
                  AND n.createdAt < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    
    $expiredResult = $con->query($expiredSql);
    
    if ($expiredResult && $expiredResult->num_rows > 0) {
        while ($expired = $expiredResult->fetch_assoc()) {
            error_log("DEBUG: AUTO - Processing expired notification for user " . $expired['id']);
            
            // 🔒 תיקון: transaction לטיפול באפס נתונים
            $con->begin_transaction();
            
            try {
                // עדכון ההתראה לפג תוקף
                $updateExpiredSql = "UPDATE notifications 
                                    SET status = 'expired' 
                                    WHERE notificationId = ?";
                $updateExpiredStmt = $con->prepare($updateExpiredSql);
                $updateExpiredStmt->bind_param("i", $expired['notificationId']);
                $updateExpiredStmt->execute();
                
                // שחרור המקום הנעול - החזרת המשתמש לסטטוס 'waiting'
                $backToWaitingSql = "UPDATE notifications 
                                    SET status = 'waiting',
                                        message = CONCAT(message, ' - חזר לרשימה כי פג תוקף ב: ', NOW()),
                                        createdAt = NOW()
                                    WHERE id = ? AND workshopId = ? AND type = 'waitlist'";
                $backToWaitingStmt = $con->prepare($backToWaitingSql);
                $backToWaitingStmt->bind_param("ii", $expired['id'], $expired['workshopId']);
                $backToWaitingStmt->execute();
                
                $con->commit();
                
                // שליחת אימייל על פקיעת תוקף - רק אם המייל זמין
                if (function_exists('mail')) {
                    $subject = "פג תוקף ההזדמנות - TasteCraft";
                    $emailMessage = "שלום {$expired['Fname']}, למרבה הצער פג תוקף הזמן לאישור השתתפותך בסדנה '{$expired['workshopName']}'. חזרת לרשימת ההמתנה ונעדכן אותך על הזדמנויות חדשות.";
                    $headers = "From: noreply@tastecraft.com\r\n";
                    @mail($expired['Email'], $subject, $emailMessage, $headers);
                }
                
                // כעת המקום משוחרר - שליחת הודעה למשתמש הבא ברשימה
                automaticNotifyNextInWaitlist($con, $expired['workshopId']);
                
            } catch (Exception $e) {
                error_log("ERROR in handleExpiredNotificationsAutomatically: " . $e->getMessage());
                $con->rollback();
            }
        }
    }
}

// פונקציה חדשה: הסרת משתמש מרשימת "סירבנים" כשהוא נרשם שוב
function removeUserFromDeclinedList($con, $userId, $workshopId) {
    $removeDeclinedSql = "DELETE FROM notifications 
                         WHERE id = ? 
                         AND workshopId = ? 
                         AND type = 'declined_waitlist'";
    $removeDeclinedStmt = $con->prepare($removeDeclinedSql);
    $removeDeclinedStmt->bind_param("ii", $userId, $workshopId);
    $removeDeclinedStmt->execute();
    
    if ($removeDeclinedStmt->affected_rows > 0) {
        error_log("DEBUG: Removed user $userId from declined list for workshop $workshopId");
    }
}

// פונקציה חדשה: טיפול בהרשמה חדשה (לוודא שמסירים מרשימת סירבנים)
function handleNewRegistration($con, $userId, $workshopId) {
    // 🔒 תיקון: transaction לוידוא עקביות
    $con->begin_transaction();
    
    try {
        // הסרה מרשימת "סירבנים" אם קיים
        removeUserFromDeclinedList($con, $userId, $workshopId);
        
        // הסרה מרשימת המתנה הרגילה אם קיים
        $removeWaitlistSql = "DELETE FROM notifications 
                             WHERE id = ? 
                             AND workshopId = ? 
                             AND type = 'waitlist'";
        $removeWaitlistStmt = $con->prepare($removeWaitlistSql);
        $removeWaitlistStmt->bind_param("ii", $userId, $workshopId);
        $removeWaitlistStmt->execute();
        
        if ($removeWaitlistStmt->affected_rows > 0) {
            error_log("DEBUG: Removed user $userId from regular waitlist for workshop $workshopId");
        }
        
        $con->commit();
        
    } catch (Exception $e) {
        error_log("ERROR in handleNewRegistration: " . $e->getMessage());
        $con->rollback();
    }
}

// 🔒 פונקציה חדשה: ניקוי בטוח של נתונים שגויים
function safeCleanupNotifications($con) {
    error_log("DEBUG: Starting safe cleanup of notifications");
    
    $con->begin_transaction();
    
    try {
        // מחיקת התראות כפולות של 24 שעות
        $duplicatesSql = "DELETE n1 FROM notifications n1
                         INNER JOIN notifications n2 
                         WHERE n1.id = n2.id 
                         AND n1.workshopId = n2.workshopId 
                         AND n1.type = 'spot_available_24h'
                         AND n1.notificationId < n2.notificationId";
        $con->query($duplicatesSql);
        
        // עדכון התראות שפג תוקפן
        $expiredSql = "UPDATE notifications 
                      SET status = 'expired' 
                      WHERE type = 'spot_available_24h' 
                      AND status = 'unread' 
                      AND createdAt < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $con->query($expiredSql);
        
        $con->commit();
        error_log("DEBUG: Safe cleanup completed successfully");
        
    } catch (Exception $e) {
        error_log("ERROR in safeCleanupNotifications: " . $e->getMessage());
        $con->rollback();
    }
}
?>