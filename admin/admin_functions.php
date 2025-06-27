<?php
// admin/admin_functions.php - פונקציות אדמין פשוטות ומעודכנות

function getAdminData($con) {
    $data = [];
    
    // שליפת אפשרויות (אלרגיות)
    $data['options'] = getOptions($con);
    
    // שליפת סדנאות
    $data['workshops'] = getWorkshops($con);
    
    // שליפת אפשרויות סדנאות
    $data['workshopOptionsMap'] = getWorkshopOptionsMap($con);
    
    // שליפת רשימות המתנה
    $data['waitlists'] = getWaitlists($con);
    
    // שליפת החזרים
    $data['refunds'] = getRefunds($con);
    
    // שליפת סטטיסטיקות
    $data['dashboardStats'] = getDashboardStats($con);
    
    return $data;
}

function getOptions($con) {
    $optionsSql = "SELECT * FROM options";
    $optionsResult = $con->query($optionsSql);
    
    $options = [];
    while ($row = $optionsResult->fetch_assoc()) {
        $options[] = $row;
    }
    
    return $options;
}

function getWorkshops($con) {
    // עדכון חשוב: הוספת עמודת status והצגה בעברית
    $workshopsSql = "SELECT w.*, COUNT(r.registrationId) AS registeredCount,
                    CASE 
                        WHEN w.status = 'upcoming' THEN 'עתידית'
                        WHEN w.status = 'completed' THEN 'הושלמה'
                        WHEN w.status = 'cancelled' THEN 'בוטלה'
                        ELSE w.status
                    END as status_display
                    FROM workshops w
                    LEFT JOIN registration r ON w.workshopId = r.workshopId
                    GROUP BY w.workshopId
                    ORDER BY w.status ASC, w.date ASC";
    return $con->query($workshopsSql);
}

function getWorkshopOptionsMap($con) {
    $workshopOptionsMap = [];
    $workshopOptionsSql = "SELECT workshopId, optionId FROM workshopOptions";
    $workshopOptionsResult = $con->query($workshopOptionsSql);
    
    while ($option = $workshopOptionsResult->fetch_assoc()) {
        if (!isset($workshopOptionsMap[$option['workshopId']])) {
            $workshopOptionsMap[$option['workshopId']] = [];
        }
        $workshopOptionsMap[$option['workshopId']][] = $option['optionId'];
    }
    
    return $workshopOptionsMap;
}

function getWaitlists($con) {
    $waitlistSql = "SELECT n.*, w.workshopName, u.Fname, u.Lname, u.Email,
                    CASE 
                        WHEN n.status = 'waiting' THEN 'ממתין פעיל'
                        WHEN n.status = 'notified' THEN 'נשלחה התראה (24 שעות)'
                        WHEN n.status = 'expired' THEN 'פג תוקף - לא פעיל'
                        WHEN n.status = 'declined' THEN 'דחה - יקבל עדכונים עתידיים'
                        WHEN n.status = 'removed_by_admin' THEN 'הוסר על ידי אדמין'
                        ELSE n.status
                    END as status_display,
                    CASE 
                        WHEN n.status = 'notified' THEN 
                            GREATEST(0, 24 - TIMESTAMPDIFF(HOUR, n.createdAt, NOW()))
                        ELSE NULL
                    END as hours_remaining,
                    CASE 
                        WHEN n.status = 'waiting' THEN 
                            ROW_NUMBER() OVER (PARTITION BY n.workshopId, n.status ORDER BY n.createdAt ASC)
                        ELSE NULL
                    END as queue_position
                    FROM notifications n
                    JOIN workshops w ON n.workshopId = w.workshopId
                    JOIN users u ON n.id = u.id
                    WHERE n.type = 'waitlist' 
                    AND n.status IN ('waiting', 'notified', 'expired', 'declined', 'removed_by_admin')
                    ORDER BY n.workshopId, 
                             CASE n.status 
                                 WHEN 'waiting' THEN 1 
                                 WHEN 'notified' THEN 2 
                                 ELSE 3 
                             END, 
                             n.createdAt ASC";
    return $con->query($waitlistSql);
}

function getRefunds($con) {
    $refundsSql = "SELECT n.*, w.workshopName, u.Fname, u.Lname, u.Email
                  FROM notifications n
                  JOIN workshops w ON n.workshopId = w.workshopId
                  JOIN users u ON n.id = u.id
                  WHERE n.type = 'refund'
                  ORDER BY n.createdAt DESC";
    return $con->query($refundsSql);
}

function getDashboardStats($con) {
    // חישוב סה"כ הכנסות מהרשמות לסדנאות
    $incomeSql = "SELECT SUM(amountPaid) as totalIncome FROM registration";
    $incomeResult = $con->query($incomeSql);
    $totalIncome = $incomeResult->fetch_assoc()['totalIncome'] ?? 0;

    // חישוב סה"כ החזרים
    $totalRefundsSql = "SELECT SUM(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(message, '₪', -1), ' ', 1) AS DECIMAL(10,2))) as totalRefunds 
                       FROM notifications WHERE type = 'refund'";
    $totalRefundsResult = $con->query($totalRefundsSql);
    $totalRefunds = $totalRefundsResult->fetch_assoc()['totalRefunds'] ?? 0;

    // הכנסה נטו
    $netIncome = $totalIncome - $totalRefunds;

    // שליפת מספר המשתמשים ברשימות המתנה
    $waitlistCountSql = "SELECT COUNT(DISTINCT id) as waitlistCount FROM notifications WHERE type = 'waitlist' AND status IN ('waiting', 'notified')";
    $waitlistCountResult = $con->query($waitlistCountSql);
    $waitlistCount = $waitlistCountResult->fetch_assoc()['waitlistCount'] ?? 0;
    
    return [
        'totalIncome' => $totalIncome,
        'totalRefunds' => $totalRefunds,
        'netIncome' => $netIncome,
        'waitlistCount' => $waitlistCount
    ];
}

function getAdminStatistics($con) {
    // Count workshops
    $workshopCountSql = "SELECT COUNT(*) AS count FROM workshops";
    $workshopCountResult = $con->query($workshopCountSql);
    $workshopCount = $workshopCountResult->fetch_assoc()['count'];
    
    // Count users
    $userCountSql = "SELECT COUNT(*) AS count FROM users WHERE flag = 0";
    $userCountResult = $con->query($userCountSql);
    $userCount = $userCountResult->fetch_assoc()['count'];
    
    // Count registrations
    $registrationCountSql = "SELECT COUNT(*) AS count FROM registration";
    $registrationCountResult = $con->query($registrationCountSql);
    $registrationCount = $registrationCountResult->fetch_assoc()['count'];
    
    // Calculate revenue
    $revenueSql = "SELECT SUM(amountPaid) AS total FROM registration";
    $revenueResult = $con->query($revenueSql);
    $revenue = $revenueResult->fetch_assoc()['total'];
    
    return [
        'workshopCount' => $workshopCount,
        'userCount' => $userCount,
        'registrationCount' => $registrationCount,
        'revenue' => $revenue
    ];
}

function getPopularWorkshops($con) {
    $popularWorkshopsSql = "SELECT w.workshopId, w.workshopName, w.maxParticipants, 
                          COUNT(r.registrationId) AS registeredCount,
                          SUM(r.amountPaid) AS revenue
                          FROM workshops w
                          LEFT JOIN registration r ON w.workshopId = r.workshopId
                          GROUP BY w.workshopId
                          ORDER BY registeredCount DESC
                          LIMIT 5";
    return $con->query($popularWorkshopsSql);
}

function getUsers($con) {
    $usersSql = "SELECT * FROM users";
    return $con->query($usersSql);
}

function getRegistrations($con) {
    $registrationsSql = "SELECT r.*, u.Fname, u.Lname, w.workshopName 
                        FROM registration r
                        JOIN users u ON r.id = u.id
                        JOIN workshops w ON r.workshopId = w.workshopId
                        ORDER BY r.registrationDate DESC";
    return $con->query($registrationsSql);
}

function getRecentNotifications($con) {
    $notificationsSql = "SELECT n.*, w.workshopName 
                        FROM notifications n
                        LEFT JOIN workshops w ON n.workshopId = w.workshopId
                        WHERE n.type NOT IN ('waitlist', 'spot_available_24h', 'waitlist_expired')
                        GROUP BY n.notificationId
                        ORDER BY n.createdAt DESC 
                        LIMIT 10";
    return $con->query($notificationsSql);
}

// פונקציות חדשות פשוטות לניהול סטטוס

function archiveWorkshopRegistrations($con, $workshopId, $reason = 'workshop_reopened') {
    // העבר הרשמות לארכיון
    $archiveSql = "INSERT INTO registration_archive 
                   (originalRegistrationId, workshopId, id, amountPaid, registrationDate, reason)
                   SELECT registrationId, workshopId, id, amountPaid, registrationDate, ?
                   FROM registration WHERE workshopId = ?";
    
    $archiveStmt = $con->prepare($archiveSql);
    $archiveStmt->bind_param("si", $reason, $workshopId);
    $success = $archiveStmt->execute();
    
    if ($success) {
        // מחק הרשמות מטבלה הראשית
        $deleteSql = "DELETE FROM registration WHERE workshopId = ?";
        $deleteStmt = $con->prepare($deleteSql);
        $deleteStmt->bind_param("i", $workshopId);
        $deleteStmt->execute();
        
        // נקה התראות של רשימת המתנה
        $clearNotifSql = "UPDATE notifications SET status = 'expired' 
                         WHERE workshopId = ? AND type = 'waitlist' AND status != 'expired'";
        $clearStmt = $con->prepare($clearNotifSql);
        $clearStmt->bind_param("i", $workshopId);
        $clearStmt->execute();
    }
    
    return $success;
}

function updateWorkshopStatus($con, $workshopId, $status) {
    $sql = "UPDATE workshops SET status = ? WHERE workshopId = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("si", $status, $workshopId);
    return $stmt->execute();
}
?>