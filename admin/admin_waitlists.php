<?php
// admin/admin_waitlists.php - רשימות המתנה + משתמשים + סטטיסטיקות
?>

<!-- Users Tab -->
<div class="tab-content" id="users-tab">
    <div class="card">
        <div class="card-title">User List</div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>User Type</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $usersResult = getUsers($con);
                
                if ($usersResult->num_rows > 0) {
                    while ($user = $usersResult->fetch_assoc()): 
                        $userType = ($user['flag'] == 1) ? 'Admin' : 'Regular User';
                ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo $user['Fname']; ?></td>
                    <td><?php echo $user['Lname']; ?></td>
                    <td><?php echo $user['Email']; ?></td>
                    <td><?php echo $userType; ?></td>
                </tr>
                <?php 
                    endwhile;
                } else {
                    echo "<tr><td colspan='5'>No users to display</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <div class="card">
        <div class="card-title">Workshop Registrations</div>
        <table>
            <thead>
                <tr>
                    <th>Registration ID</th>
                    <th>User</th>
                    <th>Workshop</th>
                    <th>Amount Paid</th>
                    <th>Registration Date</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $registrationsResult = getRegistrations($con);
                
                if ($registrationsResult->num_rows > 0) {
                    while ($registration = $registrationsResult->fetch_assoc()): 
                ?>
                <tr>
                    <td><?php echo $registration['registrationId']; ?></td>
                    <td><?php echo $registration['Fname'] . ' ' . $registration['Lname']; ?></td>
                    <td><?php echo $registration['workshopName']; ?></td>
                    <td>$<?php echo $registration['amountPaid']; ?></td>
                    <td><?php echo $registration['registrationDate']; ?></td>
                </tr>
                <?php 
                    endwhile;
                } else {
                    echo "<tr><td colspan='5'>No registrations to display</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- טאב רשימות המתנה מתקדמות -->
<div class="tab-content" id="waitlist-tab">
    <div class="dashboard-cards">
        <div class="dashboard-card income">
            <h3>סה"כ הכנסות</h3>
            <div class="value">₪<?php echo number_format($dashboardStats['totalIncome'], 2); ?></div>
            <div class="subtitle">מהרשמות לסדנאות</div>
        </div>
        
        <div class="dashboard-card refunds">
            <h3>סה"כ החזרים</h3>
            <div class="value">₪<?php echo number_format($dashboardStats['totalRefunds'], 2); ?></div>
            <div class="subtitle">מביטולי הרשמות</div>
        </div>
        
        <div class="dashboard-card net">
            <h3>הכנסה נטו</h3>
            <div class="value">₪<?php echo number_format($dashboardStats['netIncome'], 2); ?></div>
            <div class="subtitle">לאחר החזרים</div>
        </div>
        
        <div class="dashboard-card">
            <h3>רשימות המתנה פעילות</h3>
            <div class="value"><?php echo $dashboardStats['waitlistCount']; ?></div>
            <div class="subtitle">משתמשים ממתינים או בהליך אישור</div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-title">רשימות המתנה - ניהול מתקדם</div>
        <p><strong>הסבר:</strong> המערכת מנהלת רשימות המתנה אוטומטית. כשמשתמש מבטל הרשמה או כשמתוסף מקום, נשלחת התראה למשתמש הראשון ברשימה עם 24 שעות לאשר.</p>
        
        <table>
            <thead>
                <tr>
                    <th>סדנה</th>
                    <th>משתמש</th>
                    <th>אימייל</th>
                    <th>תאריך הרשמה להמתנה</th>
                    <th>סטטוס</th>
                    <th>זמן נותר לאישור</th>
                    <th>פעולות</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($waitlistResult->num_rows > 0): ?>
                    <?php while ($waitlist = $waitlistResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $waitlist['workshopName']; ?></td>
                            <td><?php echo $waitlist['Fname'] . ' ' . $waitlist['Lname']; ?></td>
                            <td><?php echo $waitlist['Email']; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($waitlist['createdAt'])); ?></td>
                            <td>
                                <span class="waitlist-status <?php echo $waitlist['status'] === 'waiting' ? 'pending' : ($waitlist['status'] === 'notified' ? 'notified' : 'expired'); ?>">
                                    <?php echo $waitlist['status_display']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($waitlist['status'] === 'notified' && $waitlist['hours_remaining'] !== null): ?>
                                    <span class="time-remaining">
                                        <?php echo max(0, $waitlist['hours_remaining']); ?> שעות
                                    </span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="waitlist-actions">
                                <?php if ($waitlist['status'] === 'waiting'): ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="notificationId" value="<?php echo $waitlist['notificationId']; ?>">
                                        <button type="submit" name="updateWaitlist" class="btn btn-sm">📧 שלח התראה</button>
                                        <input type="hidden" name="waitlistAction" value="notify">
                                    </form>
                                <?php endif; ?>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="notificationId" value="<?php echo $waitlist['notificationId']; ?>">
                                    <button type="submit" name="updateWaitlist" class="btn btn-sm btn-danger">🗑️ הסר</button>
                                    <input type="hidden" name="waitlistAction" value="remove">
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">אין משתמשים ברשימות המתנה כרגע.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-title">החזרים כספיים</div>
        <button id="exportRefundsBtn" class="export-btn">ייצא דוח החזרים לאקסל</button>
        
        <table id="refunds-table">
            <thead>
                <tr>
                    <th>תאריך</th>
                    <th>משתמש</th>
                    <th>אימייל</th>
                    <th>סדנה</th>
                    <th>פרטי החזר</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($refundsResult->num_rows > 0): ?>
                    <?php while ($refund = $refundsResult->fetch_assoc()): 
                        preg_match('/₪([0-9]+(?:\.[0-9]+)?)/', $refund['message'], $matches);
                        $refundAmount = isset($matches[1]) ? $matches[1] : '?';
                    ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($refund['createdAt'])); ?></td>
                            <td><?php echo $refund['Fname'] . ' ' . $refund['Lname']; ?></td>
                            <td><?php echo $refund['Email']; ?></td>
                            <td><?php echo $refund['workshopName']; ?></td>
                            <td><?php echo $refund['message']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">אין החזרים כספיים לתצוגה.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Statistics Tab -->
<div class="tab-content" id="stats-tab">
    <div class="card">
        <div class="card-title">Summary</div>
        <div class="form-row">
            <?php
            $adminStats = getAdminStatistics($con);
            ?>
            
            <div class="form-group">
                <h3>Active Workshops</h3>
                <p style="font-size: 2rem; text-align: center;"><?php echo $adminStats['workshopCount']; ?></p>
            </div>
            
            <div class="form-group">
                <h3>Registered Users</h3>
                <p style="font-size: 2rem; text-align: center;"><?php echo $adminStats['userCount']; ?></p>
            </div>
            
            <div class="form-group">
                <h3>Total Registrations</h3>
                <p style="font-size: 2rem; text-align: center;"><?php echo $adminStats['registrationCount']; ?></p>
            </div>
            
            <div class="form-group">
                <h3>Total Revenue</h3>
                <p style="font-size: 2rem; text-align: center;">$<?php echo number_format($adminStats['revenue'], 2); ?></p>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-title">Popular Workshops</div>
        <table>
            <thead>
                <tr>
                    <th>Workshop Name</th>
                    <th>Registrations</th>
                    <th>Occupancy Rate</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $popularWorkshopsResult = getPopularWorkshops($con);
                
                if ($popularWorkshopsResult->num_rows > 0) {
                    while ($workshop = $popularWorkshopsResult->fetch_assoc()): 
                        $occupancyRate = ($workshop['maxParticipants'] > 0) ? 
                                      round(($workshop['registeredCount'] / $workshop['maxParticipants']) * 100, 2) : 
                                      0;
                ?>
                <tr>
                    <td><?php echo $workshop['workshopName']; ?></td>
                    <td><?php echo $workshop['registeredCount']; ?></td>
                    <td><?php echo $occupancyRate; ?>%</td>
                    <td>$<?php echo number_format($workshop['revenue'], 2); ?></td>
                </tr>
                <?php 
                    endwhile;
                } else {
                    echo "<tr><td colspan='4'>No data to display</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>