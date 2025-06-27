<?php
// admin/admin_workshops.php - טאב ניהול סדנאות - מעודכן עם פתיחה מחדש
?>

<!-- Workshops Tab -->
<div class="tab-content active" id="workshops-tab">
    <div class="card">
        <div class="card-title">Add New Workshop</div>
        <form method="post" id="addWorkshopForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="workshopName">Workshop Name:</label>
                    <input type="text" id="workshopName" name="workshopName" required>
                </div>
                <div class="form-group">
                    <label for="location">Location:</label>
                    <input type="text" id="location" name="location" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="date">Date:</label>
                    <input type="datetime-local" id="date" name="date" required>
                </div>
                <div class="form-group">
                    <label for="price">Price:</label>
                    <input type="number" id="price" name="price" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="maxParticipants">Maximum Participants:</label>
                    <input type="number" id="maxParticipants" name="maxParticipants" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="3" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="img">Image URL:</label>
                <input type="text" id="img" name="img" required>
            </div>
            
            <div class="form-group">
                <label>Allergies/Sensitivities:</label>
                <div class="checkbox-group">
                    <?php foreach ($options as $option): ?>
                    <div class="checkbox-item">
                        <input type="checkbox" id="option-<?php echo $option['optionId']; ?>" name="options[]" value="<?php echo $option['optionId']; ?>">
                        <label for="option-<?php echo $option['optionId']; ?>"><?php echo $option['optionName']; ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <button type="submit" name="addWorkshop" class="btn">Add Workshop</button>
        </form>
    </div>
    
    <div class="card hidden" id="updateWorkshopCard">
        <div class="card-title">Update Workshop</div>
        <form method="post" id="updateWorkshopForm">
            <input type="hidden" id="workshopIdUpdate" name="workshopId">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="workshopNameUpdate">Workshop Name:</label>
                    <input type="text" id="workshopNameUpdate" name="workshopName">
                </div>
                <div class="form-group">
                    <label for="locationUpdate">Location:</label>
                    <input type="text" id="locationUpdate" name="location">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="dateUpdate">Date:</label>
                    <input type="datetime-local" id="dateUpdate" name="date">
                </div>
                <div class="form-group">
                    <label for="priceUpdate">Price:</label>
                    <input type="number" id="priceUpdate" name="price" step="0.01">
                </div>
                <div class="form-group">
                    <label for="maxParticipantsUpdate">Maximum Participants:</label>
                    <input type="number" id="maxParticipantsUpdate" name="maxParticipants">
                </div>
            </div>
            
            <div class="form-group">
                <label for="descriptionUpdate">Description:</label>
                <textarea id="descriptionUpdate" name="description" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label for="imgUpdate">Image URL:</label>
                <input type="text" id="imgUpdate" name="img">
            </div>
            
            <div class="form-group">
                <label>Allergies/Sensitivities:</label>
                <div class="checkbox-group" id="optionsUpdate">
                    <?php foreach ($options as $option): ?>
                    <div class="checkbox-item">
                        <input type="checkbox" id="option-update-<?php echo $option['optionId']; ?>" name="options[]" value="<?php echo $option['optionId']; ?>">
                        <label for="option-update-<?php echo $option['optionId']; ?>"><?php echo $option['optionName']; ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="form-row">
                <button type="submit" name="updateWorkshop" class="btn">Update Workshop</button>
                <button type="button" id="cancelUpdateBtn" class="btn btn-danger">Cancel</button>
            </div>
        </form>
    </div>
    
    <div class="card hidden" id="deleteWorkshopCard">
        <div class="card-title">Delete Workshop</div>
        <form method="post" id="deleteWorkshopForm">
            <input type="hidden" id="workshopIdDelete" name="workshopId">
            <p>Are you sure you want to delete the workshop "<span id="deleteWorkshopName"></span>"?</p>
            <div class="form-row">
                <button type="submit" name="deleteWorkshop" class="btn btn-danger">Delete Workshop</button>
                <button type="button" id="cancelDeleteBtn" class="btn">Cancel</button>
            </div>
        </form>
    </div>

    <!-- *** חלון פתיחה מחדש חדש *** -->
    <div class="card hidden" id="reopenWorkshopCard">
        <div class="card-title">🔄 פתיחת סדנה מחדש</div>
        <form method="post" id="reopenWorkshopForm">
            <input type="hidden" id="workshopIdReopen" name="workshopId">
            
            <div class="form-group">
                <h4>סדנה: <span id="reopenWorkshopName"></span></h4>
                <p><strong>תאריך נוכחי:</strong> <span id="currentWorkshopDate"></span></p>
            </div>
            
            <div class="form-group">
                <div style="background: #fff3cd; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 5px solid #ffc107;">
                    <h4>⚠️ שים לב:</h4>
                    <ul>
                        <li><strong>ההרשמות הישנות</strong> יועברו לארכיון אוטומטית</li>
                        <li><strong>רשימות ההמתנה</strong> ינוקו</li>
                        <li><strong>התאריך החדש</strong> חייב להיות בעתיד</li>
                        <li><strong>הסדנה תחזור לסטטוס "עתידית"</strong></li>
                    </ul>
                </div>
            </div>
            
            <div class="form-group">
                <label for="newDate"><strong>תאריך ושעה חדשים לסדנה:</strong></label>
                <input type="datetime-local" id="newDate" name="newDate" required 
                       style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                <small style="color: #666;">חובה לבחור תאריך ושעה בעתיד</small>
            </div>
            
            <div class="form-row">
                <button type="submit" name="reopenWorkshop" class="btn btn-success">
                    🔄 פתח מחדש עם תאריך חדש
                </button>
                <button type="button" id="cancelReopenBtn" class="btn btn-danger">
                    ❌ ביטול
                </button>
            </div>
        </form>
    </div>
    
    <div class="card">
        <div class="card-title">Workshop List</div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Workshop Name</th>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Price</th>
                    <th>Capacity</th>
                    <th>Registered</th>
                    <th>Available</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($workshopsResult->num_rows > 0) {
                    $workshopsResult->data_seek(0);
                    while ($workshop = $workshopsResult->fetch_assoc()): 
                        $availableSeats = $workshop['maxParticipants'] - $workshop['registeredCount'];
                        $workshopJson = json_encode($workshop);
                        $workshopOptions = isset($workshopOptionsMap[$workshop['workshopId']]) ? 
                                         json_encode($workshopOptionsMap[$workshop['workshopId']]) : 
                                         '[]';
                ?>
                <tr data-workshop='<?php echo htmlspecialchars($workshopJson); ?>' data-options='<?php echo $workshopOptions; ?>'>
                    <td><?php echo $workshop['workshopId']; ?></td>
                    <td><?php echo $workshop['workshopName']; ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($workshop['date'])); ?></td>
                    <td><?php echo $workshop['location']; ?></td>
                    <td>₪<?php echo $workshop['price']; ?></td>
                    <td><?php echo $workshop['maxParticipants']; ?></td>
                    <td><?php echo $workshop['registeredCount']; ?></td>
                    <td><?php echo $availableSeats; ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $workshop['status']; ?>">
                            <?php echo $workshop['status_display']; ?>
                        </span>
                    </td>
                    <td class="actions">
                        <button class="btn btn-sm edit-btn" data-id="<?php echo $workshop['workshopId']; ?>">✏️ Edit</button>
                        
                        <!-- *** כפתורי סטטוס מעודכנים *** -->
                        <?php if ($workshop['status'] === 'upcoming'): ?>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="workshopId" value="<?php echo $workshop['workshopId']; ?>">
                                <input type="hidden" name="newStatus" value="completed">
                                <button type="submit" name="changeStatus" class="btn btn-sm btn-warning" 
                                        onclick="return confirm('לסמן סדנה כהושלמה?')">
                                    ✅ השלם
                                </button>
                            </form>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="workshopId" value="<?php echo $workshop['workshopId']; ?>">
                                <input type="hidden" name="newStatus" value="cancelled">
                                <button type="submit" name="changeStatus" class="btn btn-sm btn-danger" 
                                        onclick="return confirm('לבטל את הסדנה?')">
                                    ❌ בטל
                                </button>
                            </form>
                        <?php elseif ($workshop['status'] === 'completed'): ?>
                            <button class="btn btn-sm btn-success reopen-btn" 
                                    data-id="<?php echo $workshop['workshopId']; ?>"
                                    data-name="<?php echo $workshop['workshopName']; ?>"
                                    data-current-date="<?php echo $workshop['date']; ?>">
                                🔄 פתח מחדש
                            </button>
                        <?php elseif ($workshop['status'] === 'cancelled'): ?>
                            <button class="btn btn-sm btn-success reopen-btn" 
                                    data-id="<?php echo $workshop['workshopId']; ?>"
                                    data-name="<?php echo $workshop['workshopName']; ?>"
                                    data-current-date="<?php echo $workshop['date']; ?>">
                                🔄 פתח מחדש
                            </button>
                        <?php endif; ?>
                        
                        <!-- הסרנו את כפתור הארכיון הנפרד -->
                        
                        <button class="btn btn-sm btn-danger delete-btn" 
                                data-id="<?php echo $workshop['workshopId']; ?>" 
                                data-name="<?php echo $workshop['workshopName']; ?>">🗑️ Delete</button>
                    </td>
                </tr>
                <?php 
                    endwhile;
                } else {
                    echo "<tr><td colspan='10'>No workshops to display</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>