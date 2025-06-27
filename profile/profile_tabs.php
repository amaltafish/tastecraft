<?php
// profile/profile_tabs.php - תוכן הטאבים
?>

<div class="tabs">
    <div class="tab active" data-tab="profile">פרטים אישיים</div>
    <div class="tab" data-tab="registrations">הסדנאות שלי</div>
    <div class="tab" data-tab="waitlists">רשימות המתנה שלי</div>
    <div class="tab" data-tab="reviews">חוות דעת</div>
    <div class="tab" data-tab="notifications">
        התראות
        <?php if ($urgentCount > 0): ?>
            <span class="notification-badge"><?php echo $urgentCount; ?></span>
        <?php endif; ?>
    </div>
</div>

<!-- פרטים אישיים -->
<div class="tab-content active" id="profile-tab">
    <div class="card">
        <div class="card-title">עדכון פרטים אישיים</div>
        <form method="post" action="">
            <div class="form-group">
                <label for="id">ת.ז:</label>
                <input type="text" id="id" value="<?php echo $userData['id']; ?>" disabled>
            </div>
            <div class="form-group">
                <label for="Fname">שם פרטי:</label>
                <input type="text" id="Fname" name="Fname" value="<?php echo $userData['Fname']; ?>" required>
            </div>
            <div class="form-group">
                <label for="Lname">שם משפחה:</label>
                <input type="text" id="Lname" name="Lname" value="<?php echo $userData['Lname']; ?>" required>
            </div>
            <div class="form-group">
                <label for="Email">אימייל:</label>
                <input type="email" id="Email" name="Email" value="<?php echo $userData['Email']; ?>" required>
            </div>
            <div class="form-group">
                <label for="password">סיסמה חדשה (השאר ריק כדי לא לשנות):</label>
                <input type="password" id="password" name="password">
            </div>
            <button type="submit" name="updateProfile">עדכן פרטים</button>
        </form>
    </div>
</div>

<!-- הסדנאות שלי -->
<div class="tab-content" id="registrations-tab">
    <div class="card">
        <div class="card-title">הסדנאות שנרשמתי אליהן</div>
        <?php if ($registrations->num_rows > 0): ?>
            <?php while ($reg = $registrations->fetch_assoc()): 
                $workshopDate = new DateTime($reg['date']);
                $currentDate = new DateTime();
                $isPast = $workshopDate < $currentDate;
                $interval = $currentDate->diff($workshopDate);
                $hoursRemaining = ($interval->days * 24) + $interval->h;
                $canCancel = $hoursRemaining >= 48 && $workshopDate > $currentDate;
            ?>
                <div class="workshop-card <?php echo $isPast ? 'past-workshop' : ''; ?>">
                    <img src="<?php echo $reg['img']; ?>" alt="<?php echo $reg['workshopName']; ?>" class="workshop-image">
                    <div class="workshop-details">
                        <div class="workshop-name">
                            <?php echo $reg['workshopName']; ?>
                            <?php if ($isPast): ?>
                                <span class="status-tag status-completed">הושלם</span>
                            <?php else: ?>
                                <span class="status-tag status-upcoming">עתידי</span>
                            <?php endif; ?>
                        </div>
                        <div class="workshop-info">תאריך: <?php echo date('d/m/Y', strtotime($reg['date'])); ?></div>
                        <div class="workshop-info">שעה: <?php echo date('H:i', strtotime($reg['date'])); ?></div>
                        <div class="workshop-info">מיקום: <?php echo $reg['location']; ?></div>
                        <div class="workshop-info">סכום ששולם: ₪<?php echo $reg['amountPaid']; ?></div>
                        
                        <div class="workshop-actions">
                            <?php if ($canCancel): ?>
                                <form method="post" action="" style="display: inline-block;">
                                    <input type="hidden" name="registrationId" value="<?php echo $reg['registrationId']; ?>">
                                    <input type="hidden" name="workshopId" value="<?php echo $reg['workshopId']; ?>">
                                    <input type="hidden" name="amountPaid" value="<?php echo $reg['amountPaid']; ?>">
                                    <button type="submit" name="cancelRegistration" class="btn-cancel" onclick="return confirm('האם אתה בטוח שברצונך לבטל את ההרשמה? תקבל החזר של 80% מהסכום.')">בטל הרשמה</button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if ($isPast && $reg['hasReview'] == 0): ?>
                                <button class="btn-review" onclick="showReviewForm(<?php echo $reg['workshopId']; ?>)">כתוב חוות דעת</button>
                            <?php elseif ($isPast && $reg['hasReview'] > 0): ?>
                                <button class="btn-review" onclick="showReviewForm(<?php echo $reg['workshopId']; ?>)">ערוך חוות דעת</button>
                            <?php endif; ?>
                        </div>
                        
                        <!-- טופס חוות דעת -->
                        <div id="review-form-<?php echo $reg['workshopId']; ?>" class="review-form">
                            <h3>חוות דעת על <?php echo $reg['workshopName']; ?></h3>
                            <form method="post" action="">
                                <input type="hidden" name="workshopId" value="<?php echo $reg['workshopId']; ?>">
                                
                                <div class="form-group">
                                    <label>דירוג:</label>
                                    <div class="stars">
                                        <input type="radio" id="star5-<?php echo $reg['workshopId']; ?>" name="rating" value="5" required>
                                        <label for="star5-<?php echo $reg['workshopId']; ?>">★</label>
                                        <input type="radio" id="star4-<?php echo $reg['workshopId']; ?>" name="rating" value="4">
                                        <label for="star4-<?php echo $reg['workshopId']; ?>">★</label>
                                        <input type="radio" id="star3-<?php echo $reg['workshopId']; ?>" name="rating" value="3">
                                        <label for="star3-<?php echo $reg['workshopId']; ?>">★</label>
                                        <input type="radio" id="star2-<?php echo $reg['workshopId']; ?>" name="rating" value="2">
                                        <label for="star2-<?php echo $reg['workshopId']; ?>">★</label>
                                        <input type="radio" id="star1-<?php echo $reg['workshopId']; ?>" name="rating" value="1">
                                        <label for="star1-<?php echo $reg['workshopId']; ?>">★</label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="comment-<?php echo $reg['workshopId']; ?>">תגובה:</label>
                                    <textarea id="comment-<?php echo $reg['workshopId']; ?>" name="comment" rows="3" required></textarea>
                                </div>
                                
                                <button type="submit" name="submitReview">שלח חוות דעת</button>
                                <button type="button" onclick="hideReviewForm(<?php echo $reg['workshopId']; ?>)">ביטול</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>לא נרשמת לסדנאות עדיין. <a href="workshop.php">לצפייה בסדנאות זמינות</a></p>
        <?php endif; ?>
    </div>
</div>

<!-- רשימות המתנה -->
<div class="tab-content" id="waitlists-tab">
    <div class="card">
        <div class="card-title">רשימות המתנה שלי</div>
        <?php if ($waitlistResult->num_rows > 0): ?>
            <?php while ($waitlist = $waitlistResult->fetch_assoc()): ?>
                <div class="workshop-card">
                    <img src="<?php echo $waitlist['img']; ?>" alt="<?php echo $waitlist['workshopName']; ?>" class="workshop-image">
                    <div class="workshop-details">
                        <div class="workshop-name">
                            <?php echo $waitlist['workshopName']; ?>
                            <span class="status-tag <?php echo $waitlist['status'] == 'notified' ? 'status-urgent' : 'status-waiting'; ?>">
                                <?php echo $waitlist['status_display']; ?>
                            </span>
                            <?php if ($waitlist['status'] == 'waiting' && $waitlist['queue_position']): ?>
                                <span class="status-tag" style="background-color: #e2e3e5; color: #383d41;">
                                    מיקום: <?php echo $waitlist['queue_position']; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="workshop-info">תאריך: <?php echo date('d/m/Y H:i', strtotime($waitlist['date'])); ?></div>
                        <div class="workshop-info">מיקום: <?php echo $waitlist['location']; ?></div>
                        <div class="workshop-info">מחיר: ₪<?php echo $waitlist['price']; ?></div>
                        
                        <?php if ($waitlist['status'] == 'notified' && $waitlist['hours_remaining'] > 0): ?>
                            <div class="urgent-timer" id="countdown-<?php echo $waitlist['notificationId']; ?>" 
                                 data-hours="<?php echo $waitlist['hours_remaining']; ?>"
                                 data-minutes="<?php echo $waitlist['minutes_remaining']; ?>">
                                ⏰ נותרו <span class="time-display"></span>
                            </div>
                        <?php elseif ($waitlist['status'] == 'notified'): ?>
                            <div class="urgent-timer" style="background-color: #6c757d;">
                                ⏰ פג תוקף! הזמן להגיב עבר
                            </div>
                        <?php endif; ?>
                        
                        <div class="workshop-actions">
                            <?php if ($waitlist['status'] == 'notified' && $waitlist['hours_remaining'] > 0): ?>
                                <form method="post" action="" style="display: inline-block;">
                                    <input type="hidden" name="notificationId" value="<?php echo $waitlist['notificationId']; ?>">
                                    <input type="hidden" name="workshopId" value="<?php echo $waitlist['workshopId']; ?>">
                                    <button type="submit" name="confirmWaitlistSpot" class="btn-small btn-confirm">✅ אשר השתתפות</button>
                                </form>
                                <form method="post" action="" style="display: inline-block;">
                                    <input type="hidden" name="notificationId" value="<?php echo $waitlist['notificationId']; ?>">
                                    <input type="hidden" name="workshopId" value="<?php echo $waitlist['workshopId']; ?>">
                                    <button type="submit" name="declineWaitlistSpot" class="btn-small btn-decline">❌ דחה</button>
                                </form>
                            <?php endif; ?>
                            
                            <form method="post" action="" style="display: inline-block;">
                                <input type="hidden" name="notificationId" value="<?php echo $waitlist['notificationId']; ?>">
                                <input type="hidden" name="workshopId" value="<?php echo $waitlist['workshopId']; ?>">
                                <button type="submit" name="cancelWaitlist" class="btn-cancel" onclick="return confirm('האם אתה בטוח שברצונך להסיר את עצמך מרשימת ההמתנה?')">🗑️ בטל רשימת המתנה</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>אינך נמצא ברשימות המתנה כרגע. <a href="workshop.php">לחזרה לסדנאות</a></p>
        <?php endif; ?>
    </div>
</div>

<!-- חוות דעת -->
<div class="tab-content" id="reviews-tab">
    <div class="card">
        <div class="card-title">חוות הדעת שלי</div>
        <?php if ($reviews->num_rows > 0): ?>
            <?php while ($review = $reviews->fetch_assoc()): ?>
                <div class="workshop-card">
                    <img src="<?php echo $review['img']; ?>" alt="<?php echo $review['workshopName']; ?>" class="workshop-image">
                    <div class="workshop-details">
                        <div class="workshop-name"><?php echo $review['workshopName']; ?></div>
                        <div class="workshop-info">תאריך הסדנה: <?php echo date('d/m/Y', strtotime($review['date'])); ?></div>
                        <div class="workshop-info">תאריך חוות הדעת: <?php echo date('d/m/Y', strtotime($review['createdAt'])); ?></div>
                        <div class="workshop-info">
                            דירוג: 
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= $review['rating']): ?>
                                    <span style="color: #f4b400;">★</span>
                                <?php else: ?>
                                    <span style="color: #ccc;">☆</span>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <div class="workshop-info">
                            <strong>חוות דעת:</strong><br>
                            <?php echo $review['comment']; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>לא כתבת חוות דעת על סדנאות עדיין.</p>
        <?php endif; ?>
    </div>
</div>

<!-- התראות -->
<div class="tab-content" id="notifications-tab">
    <div class="card">
        <div class="card-title">התראות</div>
        <?php if (!empty($notificationsArray)): ?>
            <?php foreach ($notificationsArray as $notification): ?>
                <div class="notification-item <?php echo $notification['status'] == 'unread' ? 'notification-new' : ''; ?> <?php echo $notification['is_urgent'] == 1 ? 'notification-urgent' : ''; ?>">
                    
                    <?php if ($notification['is_urgent'] == 1 && $notification['hours_remaining'] > 0): ?>
                        <div class="urgent-timer">
                            ⏰ נותרו <?php echo $notification['hours_remaining']; ?> שעות לאישור!
                        </div>
                    <?php endif; ?>
                    
                    <div class="notification-date"><?php echo date('d/m/Y H:i', strtotime($notification['createdAt'])); ?></div>
                    <div class="notification-text">
                        <strong><?php echo $notification['workshopName'] ? $notification['workshopName'] . ': ' : ''; ?></strong>
                        <?php echo $notification['message']; ?>
                    </div>
                    
                    <?php if ($notification['type'] == 'spot_available_24h' && $notification['status'] == 'unread' && $notification['hours_remaining'] > 0): ?>
                        <div class="notification-actions">
                            <form method="post" action="" style="display: inline;">
                                <input type="hidden" name="notificationId" value="<?php echo $notification['notificationId']; ?>">
                                <input type="hidden" name="workshopId" value="<?php echo $notification['workshopId']; ?>">
                                <button type="submit" name="confirmWaitlistSpot" class="btn-small btn-confirm">✅ אשר השתתפות</button>
                            </form>
                            <form method="post" action="" style="display: inline;">
                                <input type="hidden" name="notificationId" value="<?php echo $notification['notificationId']; ?>">
                                <input type="hidden" name="workshopId" value="<?php echo $notification['workshopId']; ?>">
                                <button type="submit" name="declineWaitlistSpot" class="btn-small btn-decline">❌ דחה</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>אין לך התראות כרגע.</p>
        <?php endif; ?>
    </div>
</div>