<?php
// simple_cleanup.php - סקריפט חד פעמי לניקוי התראות

session_start();

// בדיקת הרשאות אדמין
if (!isset($_SESSION['id']) || !isset($_SESSION['flag']) || $_SESSION['flag'] != 1) {
    die("Access denied - Admin only");
}

$con = new mysqli("localhost", "root", "", "tastecraft");

if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

if (isset($_POST['cleanup_now'])) {
    $results = [];
    
    // 1. מחיקת התראות כפולות של 24 שעות
    $sql1 = "DELETE n1 FROM notifications n1
             INNER JOIN notifications n2 
             WHERE n1.id = n2.id 
             AND n1.workshopId = n2.workshopId 
             AND n1.type = 'spot_available_24h'
             AND n1.notificationId < n2.notificationId";
    
    $result1 = $con->query($sql1);
    $results[] = "מחיקת התראות כפולות: " . $con->affected_rows . " שורות נמחקו";
    
    // 2. עדכון התראות שפג תוקפן
    $sql2 = "UPDATE notifications 
             SET status = 'expired' 
             WHERE type = 'spot_available_24h' 
             AND status = 'unread' 
             AND createdAt < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    
    $result2 = $con->query($sql2);
    $results[] = "עדכון התראות שפג תוקפן: " . $con->affected_rows . " שורות עודכנו";
    
    // 3. איפוס משתמשים תקועים ברשימות המתנה
    $sql3 = "UPDATE notifications 
             SET status = 'waiting'
             WHERE type = 'waitlist' 
             AND status = 'notified'
             AND NOT EXISTS (
                 SELECT 1 FROM (SELECT * FROM notifications) n2 
                 WHERE n2.id = notifications.id 
                 AND n2.workshopId = notifications.workshopId 
                 AND n2.type = 'spot_available_24h' 
                 AND n2.status = 'unread'
                 AND n2.createdAt > DATE_SUB(NOW(), INTERVAL 24 HOUR)
             )";
    
    $result3 = $con->query($sql3);
    $results[] = "איפוס משתמשים תקועים: " . $con->affected_rows . " שורות עודכנו";
    
    // 4. ניקוי רשימות המתנה לסדנאות שעברו
    $sql4 = "UPDATE notifications n
             JOIN workshops w ON n.workshopId = w.workshopId
             SET n.status = 'workshop_completed'
             WHERE w.date < NOW() 
             AND n.type = 'waitlist' 
             AND n.status IN ('waiting', 'notified')";
    
    $result4 = $con->query($sql4);
    $results[] = "ניקוי רשימות המתנה ישנות: " . $con->affected_rows . " שורות עודכנו";
    
    $cleanupDone = true;
}

// שאילתה לספירת התראות בעייתיות
$duplicatesSql = "SELECT COUNT(*) as total FROM (
    SELECT id, workshopId, type, COUNT(*) as count
    FROM notifications 
    WHERE type = 'spot_available_24h'
    GROUP BY id, workshopId, type
    HAVING count > 1
) as duplicates";
$duplicatesResult = $con->query($duplicatesSql);
$duplicatesCount = $duplicatesResult->fetch_assoc()['total'];

$expiredSql = "SELECT COUNT(*) as count 
               FROM notifications 
               WHERE type = 'spot_available_24h' 
               AND status = 'unread' 
               AND createdAt < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
$expiredResult = $con->query($expiredSql);
$expiredCount = $expiredResult->fetch_assoc()['count'];

$stuckSql = "SELECT COUNT(*) as count
             FROM notifications n1
             WHERE n1.type = 'waitlist' 
             AND n1.status = 'notified'
             AND NOT EXISTS (
                 SELECT 1 FROM notifications n2 
                 WHERE n2.id = n1.id 
                 AND n2.workshopId = n1.workshopId 
                 AND n2.type = 'spot_available_24h' 
                 AND n2.status = 'unread'
                 AND n2.createdAt > DATE_SUB(NOW(), INTERVAL 24 HOUR)
             )";
$stuckResult = $con->query($stuckSql);
$stuckCount = $stuckResult->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ניקוי התראות חד פעמי - TasteCraft</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            margin: -30px -30px 30px -30px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .stats {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .stat-item {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 10px;
            background-color: white;
            border-radius: 4px;
        }
        .count {
            font-weight: bold;
            font-size: 1.2em;
        }
        .count.problem {
            color: #dc3545;
        }
        .count.ok {
            color: #28a745;
        }
        .cleanup-button {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 1.2em;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            margin: 20px 0;
        }
        .cleanup-button:hover {
            background-color: #c82333;
        }
        .cleanup-button:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        .results {
            background-color: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #ffeaa7;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧹 ניקוי התראות חד פעמי</h1>
            <p>כלי לניקוי כל ההתראות הבעייתיות במערכת</p>
        </div>
        
        <a href="admin.php" class="back-link">← חזרה לממשק האדמין</a>
        
        <?php if (!isset($cleanupDone)): ?>
        
        <div class="warning">
            <h3>⚠️ לפני התחלה</h3>
            <p><strong>זה סקריפט חד פעמי!</strong> הוא ינקה את כל ההתראות הבעייתיות במערכת.</p>
            <p>מומלץ לבצע גיבוי של בסיס הנתונים לפני ביצוע הניקוי.</p>
        </div>
        
        <div class="stats">
            <h3>📊 מצב נוכחי של ההתראות:</h3>
            
            <div class="stat-item">
                <span>התראות כפולות (אותו משתמש + סדנה):</span>
                <span class="count <?php echo $duplicatesCount > 0 ? 'problem' : 'ok'; ?>">
                    <?php echo $duplicatesCount; ?>
                </span>
            </div>
            
            <div class="stat-item">
                <span>התראות 24h שפג תוקפן:</span>
                <span class="count <?php echo $expiredCount > 0 ? 'problem' : 'ok'; ?>">
                    <?php echo $expiredCount; ?>
                </span>
            </div>
            
            <div class="stat-item">
                <span>משתמשים תקועים ברשימות המתנה:</span>
                <span class="count <?php echo $stuckCount > 0 ? 'problem' : 'ok'; ?>">
                    <?php echo $stuckCount; ?>
                </span>
            </div>
        </div>
        
        <?php 
        $totalProblems = $duplicatesCount + $expiredCount + $stuckCount;
        if ($totalProblems > 0): 
        ?>
        
        <h3>🔧 פעולות שיבוצעו:</h3>
        <ul>
            <li>מחיקת <?php echo $duplicatesCount; ?> התראות כפולות</li>
            <li>עדכון <?php echo $expiredCount; ?> התראות שפג תוקפן לסטטוס 'expired'</li>
            <li>איפוס <?php echo $stuckCount; ?> משתמשים תקועים לסטטוס 'waiting'</li>
            <li>ניקוי רשימות המתנה לסדנאות שכבר עברו</li>
        </ul>
        
        <form method="post">
            <button type="submit" name="cleanup_now" class="cleanup-button"
                    onclick="return confirm('האם אתה בטוח שברצונך לנקות את כל ההתראות הבעייתיות?\n\nפעולה זו בלתי הפיכה!')">
                🚀 בצע ניקוי עכשיו
            </button>
        </form>
        
        <?php else: ?>
        
        <div class="stats">
            <h3>✅ אין בעיות במערכת!</h3>
            <p>כל ההתראות במצב תקין. אין צורך בניקוי כרגע.</p>
        </div>
        
        <?php endif; ?>
        
        <?php else: ?>
        
        <div class="results">
            <h3>✅ ניקוי הושלם בהצלחה!</h3>
            <?php foreach ($results as $result): ?>
                <p>• <?php echo $result; ?></p>
            <?php endforeach; ?>
            
            <p><strong>המערכת נוקתה בהצלחה!</strong></p>
            <p>כעת כדאי לבדוק שהתיקון בקוד עובד כמו שצריך.</p>
        </div>
        
        <a href="admin.php" class="back-link">← חזרה לממשק האדמין</a>
        
        <?php endif; ?>
        
    </div>
</body>
</html>