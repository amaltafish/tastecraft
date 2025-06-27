<?php
// admin/admin_notifications.php - טאב התראות
?>

<!-- Notifications Tab -->
<div class="tab-content" id="notifications-tab">
    <div class="card">
        <div class="card-title">Send Notification to Users</div>
        <form method="post" id="notificationForm">
            <div class="form-group">
                <label for="workshopIdNotification">Workshop (Optional, leave empty to send to all users):</label>
                <select id="workshopIdNotification" name="workshopId">
                    <option value="">-- Select Workshop --</option>
                    <?php 
                    // Reset query results pointer
                    if ($workshopsResult->num_rows > 0) {
                        $workshopsResult->data_seek(0);
                        while ($workshop = $workshopsResult->fetch_assoc()): 
                    ?>
                    <option value="<?php echo $workshop['workshopId']; ?>">
                        <?php echo $workshop['workshopName']; ?>
                    </option>
                    <?php endwhile;
                    } ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="notificationType">Notification Type:</label>
                <select id="notificationType" name="type" required>
                    <option value="reminder">Reminder</option>
                    <option value="update">Update</option>
                    <option value="cancellation">Cancellation</option>
                    <option value="general">General</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="notificationMessage">Message:</label>
                <textarea id="notificationMessage" name="message" rows="3" required></textarea>
            </div>
            
            <button type="submit" name="sendNotification" class="btn">Send Notification</button>
        </form>
    </div>
    
    <div class="card">
        <div class="card-title">Recent Notifications</div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Workshop</th>
                    <th>Type</th>
                    <th>Message</th>
                    <th>Sent Date</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $notificationsResult = getRecentNotifications($con);
                
                if ($notificationsResult->num_rows > 0) {
                    while ($notification = $notificationsResult->fetch_assoc()): 
                        $workshopName = $notification['workshopName'] ? $notification['workshopName'] : 'All Workshops';
                        
                        // Translation of notification types
                        $typeTranslation = [
                            'reminder' => 'Reminder',
                            'update' => 'Update',
                            'cancellation' => 'Cancellation',
                            'general' => 'General'
                        ];
                        
                        $typeDisplay = isset($typeTranslation[$notification['type']]) ? 
                                    $typeTranslation[$notification['type']] : 
                                    $notification['type'];
                ?>
                <tr>
                    <td><?php echo $notification['notificationId']; ?></td>
                    <td><?php echo $workshopName; ?></td>
                    <td><?php echo $typeDisplay; ?></td>
                    <td><?php echo substr($notification['message'], 0, 50) . '...'; ?></td>
                    <td><?php echo $notification['createdAt']; ?></td>
                </tr>
                <?php 
                    endwhile;
                } else {
                    echo "<tr><td colspan='5'>No notifications to display</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>