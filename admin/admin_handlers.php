<?php
// admin/admin_handlers.php - טיפול בפעולות POST של אדמין - מעודכן עם פתיחה מחדש

function handleAdminActions($con) {
    $result = ['redirect' => false, 'params' => []];
    
    if (isset($_POST['addWorkshop'])) {
        $result = handleAddWorkshop($con, $_POST);
    }
    elseif (isset($_POST['updateWorkshop'])) {
        $result = handleUpdateWorkshop($con, $_POST);
    }
    elseif (isset($_POST['deleteWorkshop'])) {
        $result = handleDeleteWorkshop($con, $_POST);
    }
    elseif (isset($_POST['sendNotification'])) {
        $result = handleSendNotification($con, $_POST);
    }
    elseif (isset($_POST['updateWaitlist'])) {
        $result = handleUpdateWaitlist($con, $_POST);
    }
    // פעולות מעודכנות:
    elseif (isset($_POST['changeStatus'])) {
        $result = handleStatusChange($con, $_POST);
    }
    elseif (isset($_POST['reopenWorkshop'])) {
        $result = handleReopenWorkshop($con, $_POST); // *** עודכן ***
    }
    // הסרנו את archiveRegistrations - לא נחוץ יותר
    
    return $result;
}

function handleAddWorkshop($con, $postData) {
    if (
        !empty($postData['workshopName']) && 
        !empty($postData['description']) && 
        !empty($postData['date']) && 
        !empty($postData['location']) && 
        !empty($postData['price']) && 
        !empty($postData['maxParticipants']) && 
        !empty($postData['img'])
    ) {
        $workshopName = $postData['workshopName'];
        $description = $postData['description'];
        $date = $postData['date'];
        $location = $postData['location'];
        $price = $postData['price'];
        $maxParticipants = $postData['maxParticipants'];
        $img = $postData['img'];
        $status = 'upcoming'; // סדנה חדשה תמיד מתחילה כעתידית
        
        $sql = "INSERT INTO workshops (workshopName, description, date, location, price, maxParticipants, img, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $con->prepare($sql);
        $stmt->bind_param("ssssdiss", $workshopName, $description, $date, $location, $price, $maxParticipants, $img, $status);
        
        if ($stmt->execute()) {
            $workshopId = $con->insert_id;
            
            // If allergy options defined for workshop
            if (isset($postData['options']) && is_array($postData['options'])) {
                foreach ($postData['options'] as $optionId) {
                    $sql = "INSERT INTO workshopOptions (workshopId, optionId) VALUES (?, ?)";
                    $stmt = $con->prepare($sql);
                    $stmt->bind_param("ii", $workshopId, $optionId);
                    $stmt->execute();
                }
            }
            
            // שליחת הודעה למשתמשים שסירבו לסדנה זהה בעבר
            notifyDeclinedUsersAboutNewWorkshop($con, $workshopName);
            
            return [
                'redirect' => true,
                'params' => ['success' => 'Workshop added successfully']
            ];
        } else {
            return [
                'redirect' => true,
                'params' => ['error' => 'Error adding workshop: ' . $stmt->error]
            ];
        }
    } else {
        return [
            'redirect' => true,
            'params' => ['error' => 'Please fill all fields']
        ];
    }
}

function handleUpdateWorkshop($con, $postData) {
    if (!empty($postData['workshopId'])) {
        $workshopId = $postData['workshopId'];
        
        // Check if workshop exists
        $checkSql = "SELECT * FROM workshops WHERE workshopId = ?";
        $checkStmt = $con->prepare($checkSql);
        $checkStmt->bind_param("i", $workshopId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            $workshopName = !empty($postData['workshopName']) ? $postData['workshopName'] : $row['workshopName'];
            $description = !empty($postData['description']) ? $postData['description'] : $row['description'];
            $date = !empty($postData['date']) ? $postData['date'] : $row['date'];
            $location = !empty($postData['location']) ? $postData['location'] : $row['location'];
            $price = !empty($postData['price']) ? $postData['price'] : $row['price'];
            $maxParticipants = !empty($postData['maxParticipants']) ? $postData['maxParticipants'] : $row['maxParticipants'];
            $img = !empty($postData['img']) ? $postData['img'] : $row['img'];
            
            $sql = "UPDATE workshops 
                    SET workshopName = ?, description = ?, date = ?, location = ?, price = ?, maxParticipants = ?, img = ? 
                    WHERE workshopId = ?";
            
            $stmt = $con->prepare($sql);
            $stmt->bind_param("ssssdisi", $workshopName, $description, $date, $location, $price, $maxParticipants, $img, $workshopId);
            
            if ($stmt->execute()) {
                // If new allergy options defined, delete existing and add new ones
                if (isset($postData['options']) && is_array($postData['options'])) {
                    // Delete existing options
                    $deleteSql = "DELETE FROM workshopOptions WHERE workshopId = ?";
                    $deleteStmt = $con->prepare($deleteSql);
                    $deleteStmt->bind_param("i", $workshopId);
                    $deleteStmt->execute();
                    
                    // Add new options
                    foreach ($postData['options'] as $optionId) {
                        $insertSql = "INSERT INTO workshopOptions (workshopId, optionId) VALUES (?, ?)";
                        $insertStmt = $con->prepare($insertSql);
                        $insertStmt->bind_param("ii", $workshopId, $optionId);
                        $insertStmt->execute();
                    }
                }
                
                return [
                    'redirect' => true,
                    'params' => ['success' => 'Workshop updated successfully']
                ];
            } else {
                return [
                    'redirect' => true,
                    'params' => ['error' => 'Error updating workshop: ' . $stmt->error]
                ];
            }
        } else {
            return [
                'redirect' => true,
                'params' => ['error' => 'Workshop not found']
            ];
        }
    } else {
        return [
            'redirect' => true,
            'params' => ['error' => 'Workshop ID is required']
        ];
    }
}

function handleDeleteWorkshop($con, $postData) {
    if (!empty($postData['workshopId'])) {
        $workshopId = $postData['workshopId'];
        
        // Check if workshop exists
        $checkSql = "SELECT * FROM workshops WHERE workshopId = ?";
        $checkStmt = $con->prepare($checkSql);
        $checkStmt->bind_param("i", $workshopId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            // Delete workshop (foreign key constraints will handle automatically with ON DELETE CASCADE)
            $sql = "DELETE FROM workshops WHERE workshopId = ?";
            $stmt = $con->prepare($sql);
            $stmt->bind_param("i", $workshopId);
            
            if ($stmt->execute()) {
                return [
                    'redirect' => true,
                    'params' => ['success' => 'Workshop deleted successfully']
                ];
            } else {
                return [
                    'redirect' => true,
                    'params' => ['error' => 'Error deleting workshop: ' . $stmt->error]
                ];
            }
        } else {
            return [
                'redirect' => true,
                'params' => ['error' => 'Workshop not found']
            ];
        }
    } else {
        return [
            'redirect' => true,
            'params' => ['error' => 'Workshop ID is required']
        ];
    }
}

function handleSendNotification($con, $postData) {
    if (!empty($postData['message']) && !empty($postData['type'])) {
        $message = $postData['message'];
        $type = $postData['type'];
        
        // If specific workshop selected
        if (!empty($postData['workshopId'])) {
            $workshopId = $postData['workshopId'];
            
            // Send to all users registered for this workshop
            $sql = "INSERT INTO notifications (id, workshopId, message, type, status, createdAt) 
                    SELECT r.id, r.workshopId, ?, ?, 'unread', NOW()
                    FROM registration r 
                    WHERE r.workshopId = ?";
            
            $stmt = $con->prepare($sql);
            $stmt->bind_param("ssi", $message, $type, $workshopId);
            
            if ($stmt->execute()) {
                return [
                    'redirect' => true,
                    'params' => ['success' => 'Notification sent to workshop participants']
                ];
            } else {
                return [
                    'redirect' => true,
                    'params' => ['error' => 'Error sending notification: ' . $stmt->error]
                ];
            }
        } 
        // If no specific workshop selected, send to all users
        else {
            $sql = "INSERT INTO notifications (id, message, type, status, createdAt) 
                    SELECT id, ?, ?, 'unread', NOW()
                    FROM users 
                    WHERE flag = 0"; // Send only to regular users, not admins
            
            $stmt = $con->prepare($sql);
            $stmt->bind_param("ss", $message, $type);
            
            if ($stmt->execute()) {
                return [
                    'redirect' => true,
                    'params' => ['success' => 'Notification sent to all users']
                ];
            } else {
                return [
                    'redirect' => true,
                    'params' => ['error' => 'Error sending notification: ' . $stmt->error]
                ];
            }
        }
    } else {
        return [
            'redirect' => true,
            'params' => ['error' => 'Message and notification type are required']
        ];
    }
}

function handleUpdateWaitlist($con, $postData) {
    $notificationId = $postData['notificationId'];
    $action = $postData['waitlistAction'];
    
    if ($action === 'notify') {
        // קבלת פרטי המשתמש המחכה
        $notifSql = "SELECT n.workshopId 
                    FROM notifications n
                    WHERE n.notificationId = ? AND n.type = 'waitlist'";
        $notifStmt = $con->prepare($notifSql);
        $notifStmt->bind_param("i", $notificationId);
        $notifStmt->execute();
        $notifResult = $notifStmt->get_result();
        
        if ($notifResult->num_rows > 0) {
            $waitingUser = $notifResult->fetch_assoc();
            $workshopId = $waitingUser['workshopId'];
            
            // שימוש בפונקציה הפשוטה
            if (automaticNotifyNextInWaitlist($con, $workshopId)) {
                return [
                    'redirect' => true,
                    'params' => ['success' => 'נשלחה התראה בהצלחה למשתמש ברשימת ההמתנה']
                ];
            } else {
                return [
                    'redirect' => true,
                    'params' => ['error' => 'לא ניתן לשלוח התראה - אין מקומות פנויים או אין משתמשים ברשימה']
                ];
            }
        } else {
            return [
                'redirect' => true,
                'params' => ['error' => 'לא נמצא משתמש ברשימת ההמתנה']
            ];
        }
    } else if ($action === 'remove') {
        // הסרת משתמש מרשימת המתנה
        $removeSql = "UPDATE notifications SET status = 'removed_by_admin' WHERE notificationId = ? AND type = 'waitlist'";
        $removeStmt = $con->prepare($removeSql);
        $removeStmt->bind_param("i", $notificationId);
        
        if ($removeStmt->execute()) {
            return [
                'redirect' => true,
                'params' => ['success' => 'המשתמש הוסר בהצלחה מרשימת ההמתנה']
            ];
        } else {
            return [
                'redirect' => true,
                'params' => ['error' => 'שגיאה בהסרת המשתמש מרשימת ההמתנה']
            ];
        }
    }
    
    return [
        'redirect' => true,
        'params' => ['error' => 'פעולה לא חוקית']
    ];
}

// פונקציה לשינוי סטטוס
function handleStatusChange($con, $postData) {
    $workshopId = $postData['workshopId'];
    $newStatus = $postData['newStatus'];
    
    if (updateWorkshopStatus($con, $workshopId, $newStatus)) {
        return [
            'redirect' => true,
            'params' => ['success' => 'סטטוס הסדנה עודכן בהצלחה']
        ];
    } else {
        return [
            'redirect' => true,
            'params' => ['error' => 'שגיאה בעדכון סטטוס הסדנה']
        ];
    }
}

// *** פונקציה מעודכנת לפתיחת סדנה מחדש עם תאריך חדש ***
function handleReopenWorkshop($con, $postData) {
    $workshopId = intval($postData['workshopId']);
    $newDate = trim($postData['newDate'] ?? '');
    
    // בדיקה שהתאריך החדש סופק
    if (empty($newDate)) {
        return [
            'redirect' => true,
            'params' => ['error' => 'חובה לבחור תאריך חדש לסדנה']
        ];
    }
    
    // בדיקה שהתאריך החדש הוא בעתיד
    $newDateTime = new DateTime($newDate);
    $currentDate = new DateTime();
    
    if ($newDateTime <= $currentDate) {
        return [
            'redirect' => true,
            'params' => ['error' => 'התאריך החדש חייב להיות בעתיד']
        ];
    }
    
    // בדיקה שהסדנה קיימת
    $checkSql = "SELECT workshopName, status FROM workshops WHERE workshopId = ?";
    $checkStmt = $con->prepare($checkSql);
    $checkStmt->bind_param("i", $workshopId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        return [
            'redirect' => true,
            'params' => ['error' => 'סדנה לא נמצאה']
        ];
    }
    
    $workshopData = $checkResult->fetch_assoc();
    
    // התחלת transaction
    $con->begin_transaction();
    
    try {
        // שלב 1: העברת הרשמות ישנות לארכיון
        $archiveSuccess = archiveWorkshopRegistrations($con, $workshopId, 'workshop_reopened_' . date('Y-m-d'));
        
        if (!$archiveSuccess) {
            throw new Exception("Failed to archive old registrations");
        }
        
        // שלב 2: עדכון תאריך וסטטוס של הסדנה
        $updateSql = "UPDATE workshops SET date = ?, status = 'upcoming' WHERE workshopId = ?";
        $updateStmt = $con->prepare($updateSql);
        $updateStmt->bind_param("si", $newDate, $workshopId);
        
        if (!$updateStmt->execute()) {
            throw new Exception("Failed to update workshop date and status");
        }
        
        // שלב 3: ניקוי התראות רשימת המתנה ישנות
        $clearNotifSql = "UPDATE notifications 
                         SET status = 'expired', 
                             message = CONCAT(message, ' [הסדנה נפתחה מחדש בתאריך ', ?, ']')
                         WHERE workshopId = ? 
                         AND type IN ('waitlist', 'spot_available_24h') 
                         AND status IN ('waiting', 'notified', 'unread')";
        $clearStmt = $con->prepare($clearNotifSql);
        $clearStmt->bind_param("si", $newDate, $workshopId);
        $clearStmt->execute();
        
        // שלב 4: יצירת התראה למנהל על הפתיחה מחדש
        $adminMessage = "הסדנה '{$workshopData['workshopName']}' נפתחה מחדש בתאריך " . date('d/m/Y H:i', strtotime($newDate));
        $adminNotifSql = "INSERT INTO notifications (id, workshopId, message, type, status, createdAt) 
                         VALUES (1, ?, ?, 'admin_workshop_reopened', 'unread', NOW())";
        $adminNotifStmt = $con->prepare($adminNotifSql);
        $adminNotifStmt->bind_param("is", $workshopId, $adminMessage);
        $adminNotifStmt->execute();
        
        // אישור כל השינויים
        $con->commit();
        
        error_log("SUCCESS: Workshop $workshopId reopened with new date: $newDate");
        
        return [
            'redirect' => true,
            'params' => ['success' => 'הסדנה נפתחה מחדש בהצלחה! תאריך חדש: ' . date('d/m/Y H:i', strtotime($newDate)) . '. ההרשמות הישנות הועברו לארכיון.']
        ];
        
    } catch (Exception $e) {
        $con->rollback();
        error_log("ERROR in handleReopenWorkshop: " . $e->getMessage());
        
        return [
            'redirect' => true,
            'params' => ['error' => 'שגיאה בפתיחת הסדנה מחדש: ' . $e->getMessage()]
        ];
    }
}

// הערה: הפונקציה archiveWorkshopRegistrations() כבר מוגדרת ב-admin_functions.php

// הערה: הפונקציה updateWorkshopStatus() כבר מוגדרת ב-admin_functions.php

// פונקציה חדשה: שליחת הודעה למשתמשים שסירבו בעבר לסדנה דומה
function notifyDeclinedUsersAboutNewWorkshop($con, $workshopName) {
    // מציאת משתמשים שסירבו לסדנה באותו שם
    $declinedUsersSql = "SELECT DISTINCT n.id, u.Email, u.Fname 
                        FROM notifications n
                        JOIN users u ON n.id = u.id
                        JOIN workshops w ON n.workshopId = w.workshopId
                        WHERE n.type = 'declined_waitlist' 
                        AND n.status = 'waiting'
                        AND w.workshopName = ?";
    
    $declinedUsersStmt = $con->prepare($declinedUsersSql);
    $declinedUsersStmt->bind_param("s", $workshopName);
    $declinedUsersStmt->execute();
    $declinedUsersResult = $declinedUsersStmt->get_result();
    
    if ($declinedUsersResult->num_rows > 0) {
        while ($user = $declinedUsersResult->fetch_assoc()) {
            // שליחת הודעה על סדנה חדשה
            $message = "🎉 סדנה חדשה פתוחה להרשמה: " . $workshopName . " - סדנה שהתעניינת בה בעבר!";
            
            $notifSql = "INSERT INTO notifications (id, message, type, status, createdAt) 
                        VALUES (?, ?, 'new_workshop_notification', 'unread', NOW())";
            $notifStmt = $con->prepare($notifSql);
            $notifStmt->bind_param("is", $user['id'], $message);
            $notifStmt->execute();
            
            // שליחת מייל (משתמש בפונקציית המייל החדשה)
            if (function_exists('sendEmail')) {
                $emailData = [
                    'userName' => $user['Fname'],
                    'workshopName' => $workshopName,
                    'message' => "פתחנו סדנה חדשה שהתעניינת בה בעבר: " . $workshopName . "\n\nכנס לאתר כדי להירשם!"
                ];
                $template = getEmailTemplate('default', $emailData);
                sendEmail($user['Email'], $template['subject'], $template['body']);
            }
        }
        
        // הסרת המשתמשים מרשימת "סירבנים" אחרי שליחת ההודעה
        $removeDeclinedSql = "DELETE FROM notifications 
                             WHERE type = 'declined_waitlist' 
                             AND status = 'waiting'
                             AND workshopId IN (SELECT workshopId FROM workshops WHERE workshopName = ?)";
        $removeDeclinedStmt = $con->prepare($removeDeclinedSql);
        $removeDeclinedStmt->bind_param("s", $workshopName);
        $removeDeclinedStmt->execute();
        
        error_log("DEBUG: Notified " . $declinedUsersResult->num_rows . " users about new workshop: " . $workshopName);
    }
}
?>