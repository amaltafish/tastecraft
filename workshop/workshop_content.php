<?php
// workshop/workshop_content.php - תוכן HTML של פרטי הסדנה
?>

<div class="workshop-header">
    <div class="workshop-image">
        <img src="<?php echo $workshop['img']; ?>" alt="<?php echo $workshop['workshopName']; ?>">
    </div>
    <div class="workshop-info">
        <h1 class="workshop-title"><?php echo $workshop['workshopName']; ?></h1>
        
        <div class="workshop-meta">
            <p><span class="meta-icon">📅</span> תאריך: <?php echo date('d/m/Y', strtotime($workshop['date'])); ?></p>
            <p><span class="meta-icon">🕒</span> שעה: <?php echo date('H:i', strtotime($workshop['date'])); ?></p>
            <p><span class="meta-icon">📍</span> מיקום: <?php echo $workshop['location']; ?></p>
            <p><span class="meta-icon">💰</span> מחיר: ₪<?php echo $workshop['price']; ?></p>
            <p><span class="meta-icon">👥</span> נרשמו: <?php echo $seats['registered']; ?> / <?php echo $seats['max']; ?></p>
            
            <?php if ($seats['locked'] > 0): ?>
                <p><span class="meta-icon">🔒</span> מקומות שמורים: <?php echo $seats['locked']; ?> (ממתינים לאישור)</p>
            <?php endif; ?>
            
            <div class="availability">
                <?php 
                if ($isPastWorkshop): ?>
                    <span class="sold-out">הסדנה כבר התקיימה</span>
                <?php elseif ($userHas24hNotification): ?>
                    <span class="special-offer">⭐ זמין עבורך במיוחד! (יש לך 24 שעות לאישור)</span>
                <?php elseif ($seats['available'] > 0 && $seats['locked'] == 0): ?>
                    <?php if ($seats['available'] > 5): ?>
                        <span class="available">זמין (<?php echo $seats['available']; ?> מקומות נותרו)</span>
                    <?php else: ?>
                        <span class="limited">זמינות מוגבלת (רק <?php echo $seats['available']; ?> מקומות נותרו)</span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="sold-out">הסדנה מלאה</span>
                    <?php if ($seats['locked'] > 0): ?>
                        <div class="locked-seats-info">
                            🔒 <?php echo $seats['locked']; ?> מקומות נעולים לאישור (24 שעות)
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="workshop-description">
            <p><?php echo $workshop['description']; ?></p>
        </div>
        
        <?php if (!empty($allergies)): ?>
        <div class="workshop-allergens">
            <h3>מידע על אלרגיות:</h3>
            <?php foreach ($allergies as $allergen): ?>
                <span class="allergen-tag"><?php echo $allergen; ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- הצגת התראה דחופה אם קיימת -->
        <?php if ($userHas24hNotification && isset($hoursRemaining24h) && $hoursRemaining24h > 0): ?>
        <div class="urgent-timer" id="urgent-countdown" data-hours="<?php echo $hoursRemaining24h; ?>">
            🔔 יש לך התראה דחופה! נותרו <span class="countdown-timer"><?php echo floor($hoursRemaining24h); ?> שעות</span> לאישור!
        </div>
        <?php endif; ?>
        
        <div class="action-buttons">
            <?php if ($isRegistered): ?>
                <button class="btn btn-disabled">כבר רשום</button>
                <a href="profile.php" class="btn btn-primary">צפה בפרופיל שלי</a>
            <?php elseif ($isInCart): ?>
                <button class="btn btn-disabled">כבר בסל הקניות</button>
                <a href="cart.php" class="btn btn-primary">צפה בסל</a>
            <?php elseif ($isPastWorkshop): ?>
                <button class="btn btn-disabled">הסדנה כבר התקיימה</button>
            <?php elseif ($userHas24hNotification): ?>
                <form method="post" action="profile.php" style="display: inline;">
                    <input type="hidden" name="notificationId" value="<?php echo $notification24hData['notificationId']; ?>">
                    <input type="hidden" name="workshopId" value="<?php echo $workshopId; ?>">
                    <button type="submit" name="confirmWaitlistSpot" class="btn btn-urgent">⏰ אשר והמשך לתשלום</button>
                </form>
                <a href="workshop.php" class="btn btn-secondary">חזרה לסדנאות</a>
            <?php elseif ($seats['available'] > 0 && $seats['locked'] == 0 && !$isInWaitlist): ?>
                <a href="cart.php?action=add&workshopId=<?php echo $workshopId; ?>" class="btn btn-primary">הוסף לסל</a>
                <a href="workshop.php" class="btn btn-secondary">חזרה לסדנאות</a>
            <?php elseif ($isInWaitlist): ?>
                <button class="btn btn-disabled">
                    <?php if ($waitlistStatus === 'notified'): ?>
                        נשלחה התראה - בדוק את הפרופיל שלך!
                    <?php else: ?>
                        ברשימת המתנה (מיקום <?php echo $waitlistPosition; ?>)
                    <?php endif; ?>
                </button>
                <a href="profile.php" class="btn btn-secondary">צפה בהתראות</a>
            <?php else: ?>
                <form method="post" action="" style="display: inline;">
                    <button type="submit" name="joinWaitlist" class="btn btn-primary">הצטרף לרשימת המתנה</button>
                </form>
                <a href="workshop.php" class="btn btn-secondary">חזרה לסדנאות</a>
            <?php endif; ?>
        </div>
        
        <!-- הצגת מידע על רשימת המתנה -->
        <?php if ($isInWaitlist): ?>
        <div class="waitlist-info <?php echo $waitlistStatus === 'notified' ? 'urgent' : ''; ?>">
            <p><strong>
                <?php if ($waitlistStatus === 'notified'): ?>
                    <?php if (strpos($waitlistData['message'], 'הוארכה תקופת ההרשמה') !== false): ?>
                        🔄 הוארכה תקופת ההרשמה שלך! קיבלת 24 שעות נוספות.
                    <?php else: ?>
                        🔔 נשלחה לך התראה לאישור השתתפות!
                    <?php endif; ?>
                <?php elseif ($waitlistStatus === 'declined'): ?>
                    📋 דחית הזדמנות קודמת אך אתה עדיין ברשימת ההמתנה.
                <?php else: ?>
                    📋 אתה במיקום <?php echo $waitlistPosition; ?> ברשימת ההמתנה לסדנה זו.
                    <?php if ($waitlistPosition == 1 && $seats['available'] > 0): ?>
                        <br>✨ אתה הבא בתור! תקבל התראה ברגע שתוכל להירשם.
                    <?php endif; ?>
                <?php endif; ?>
            </strong></p>
            
            <?php if ($waitlistStatus === 'notified' && $hoursRemaining > 0): ?>
                <div class="urgent-timer">
                    ⏰ נותרו <?php echo $hoursRemaining; ?> שעות לאישור!
                </div>
                <p style="color: #721c24; font-weight: bold;">
                    ⚠️ יש לך 24 שעות לאשר את השתתפותך דרך הפרופיל שלך!
                    <?php if ($waitlistPosition == 1 && !isset($waitlistData['other_waitlist_users'])): ?>
                        <br>💡 אם לא תספיק לאשר, תקבל אוטומטית 24 שעות נוספות כי אתה היחיד ברשימה.
                    <?php endif; ?>
                </p>
                <div style="margin-top: 15px;">
                    <a href="profile.php" class="btn btn-primary">עבור לפרופיל לאישור מיידי</a>
                </div>
            <?php elseif ($waitlistStatus === 'waiting'): ?>
                <p>💡 נשלח לך אימייל ותקבל התראה באתר ברגע שיתפנה מקום.<br>
                יהיו לך 24 שעות בדיוק לאשר את השתתפותך.</p>
                <p><strong>🕐 זמן התגובה חשוב!</strong> 
                <?php if ($waitlistPosition == 1): ?>
                    אם לא תגיב תוך 24 שעות ואתה היחיד ברשימה, תקבל אוטומטית הארכה של 24 שעות נוספות.
                <?php else: ?>
                    אם לא תגיב תוך 24 שעות, המקום יועבר למשתמש הבא ברשימה.
                <?php endif; ?>
                </p>
            <?php elseif ($waitlistStatus === 'declined'): ?>
                <p>💡 דחית הזדמנות קודמת אך אתה עדיין ברשימת ההמתנה.<br>
                תקבל התראה על הזדמנויות חדשות שיתפנו.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- אזור חוות דעת אישית (אם המשתמש השתתף בסדנה) -->
<?php if ($isRegistered && $isPastWorkshop): ?>
<div class="your-review">
    <h3><?php echo $hasReview ? 'חוות הדעת שלך' : 'שתף את חוות דעתך'; ?></h3>
    
    <?php if ($hasReview): ?>
        <div class="review-header">
            <div class="reviewer-name">הדירוג שלך:</div>
            <div class="review-date">
                <?php echo date('d/m/Y', strtotime($userReview['createdAt'])); ?>
            </div>
        </div>
        <div class="review-rating">
            <?php
            for ($i = 1; $i <= 5; $i++) {
                echo ($i <= $userReview['rating']) ? "★" : "☆";
            }
            ?>
        </div>
        <div class="review-content">
            <?php echo $userReview['comment']; ?>
        </div>
        <button class="btn btn-secondary" style="margin-top: 15px;" onclick="showEditReviewForm()">ערוך חוות דעת</button>
        
        <!-- טופס עריכת חוות דעת (מוסתר כברירת מחדל) -->
        <div id="edit-review-form" class="review-form" style="display: none;">
            <form method="post" action="">
                <div class="form-group">
                    <label>דירוג:</label>
                    <div class="stars">
                        <input type="radio" id="star5" name="rating" value="5" <?php echo $userReview['rating'] == 5 ? 'checked' : ''; ?>>
                        <label for="star5">★</label>
                        <input type="radio" id="star4" name="rating" value="4" <?php echo $userReview['rating'] == 4 ? 'checked' : ''; ?>>
                        <label for="star4">★</label>
                        <input type="radio" id="star3" name="rating" value="3" <?php echo $userReview['rating'] == 3 ? 'checked' : ''; ?>>
                        <label for="star3">★</label>
                        <input type="radio" id="star2" name="rating" value="2" <?php echo $userReview['rating'] == 2 ? 'checked' : ''; ?>>
                        <label for="star2">★</label>
                        <input type="radio" id="star1" name="rating" value="1" <?php echo $userReview['rating'] == 1 ? 'checked' : ''; ?>>
                        <label for="star1">★</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="comment">התגובה שלך:</label>
                    <textarea id="comment" name="comment" rows="4" required><?php echo $userReview['comment']; ?></textarea>
                </div>
                
                <button type="submit" name="submitReview" class="btn btn-primary">עדכן חוות דעת</button>
                <button type="button" class="btn btn-secondary" onclick="hideEditReviewForm()">ביטול</button>
            </form>
        </div>
    <?php else: ?>
        <!-- טופס הוספת חוות דעת חדשה -->
        <form method="post" action="">
            <div class="form-group">
                <label>דירוג:</label>
                <div class="stars">
                    <input type="radio" id="star5" name="rating" value="5" required>
                    <label for="star5">★</label>
                    <input type="radio" id="star4" name="rating" value="4">
                    <label for="star4">★</label>
                    <input type="radio" id="star3" name="rating" value="3">
                    <label for="star3">★</label>
                    <input type="radio" id="star2" name="rating" value="2">
                    <label for="star2">★</label>
                    <input type="radio" id="star1" name="rating" value="1">
                    <label for="star1">★</label>
                </div>
            </div>
            
            <div class="form-group">
                <label for="comment">התגובה שלך:</label>
                <textarea id="comment" name="comment" rows="4" required></textarea>
            </div>
            
            <button type="submit" name="submitReview" class="btn btn-primary">שלח חוות דעת</button>
        </form>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="reviews-section">
    <div class="reviews-header">
        <h2 class="section-title">ביקורות ודירוגים</h2>
        <div class="avg-rating">
            <span>דירוג ממוצע:</span>
            <span class="rating-stars">
                <?php
                // Display stars for average rating
                for ($i = 1; $i <= 5; $i++) {
                    if ($i <= $avgRating) {
                        echo "★"; // Full star
                    } elseif ($i - 0.5 <= $avgRating) {
                        echo "☆"; // Empty star (could use a half star if available)
                    } else {
                        echo "☆"; // Empty star
                    }
                }
                ?>
                (<?php echo $avgRating; ?>)
            </span>
        </div>
    </div>
    
    <div class="review-list">
        <?php 
        if ($reviewsResult->num_rows > 0) {
            while ($review = $reviewsResult->fetch_assoc()): 
                // דילוג על חוות הדעת של המשתמש הנוכחי כי כבר מוצגת למעלה
                if ($review['id'] == $userId && $isRegistered && $isPastWorkshop) {
                    continue;
                }
        ?>
            <div class="review-card">
                <div class="review-header">
                    <div class="reviewer-name"><?php echo $review['Fname'] . ' ' . $review['Lname']; ?></div>
                    <div class="review-date"><?php echo date('d/m/Y', strtotime($review['createdAt'])); ?></div>
                </div>
                <div class="review-rating">
                    <?php
                    // Display stars for this review
                    for ($i = 1; $i <= 5; $i++) {
                        echo ($i <= $review['rating']) ? "★" : "☆";
                    }
                    ?>
                </div>
                <div class="review-content">
                    <?php echo $review['comment']; ?>
                </div>
            </div>
        <?php 
            endwhile;
        } else {
            echo "<p>אין ביקורות עדיין. היה הראשון לכתוב ביקורת אחרי שתשתתף בסדנה זו!</p>";
        }
        ?>
    </div>
</div>