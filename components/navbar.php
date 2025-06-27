<?php
// components/navbar.php - תפריט עליון משותף
?>
<div class="navbar">
    <div class="left-links">
        <a href="home2.php">Home</a>
        <a href="about.php">About</a>
        <a href="workshop.php">Book Workshop</a>
        <a href="profile.php">Profile</a>
        <!-- כפתור Admin - מוצג באופן שונה בהתאם להרשאות -->
        <?php if(isset($_SESSION['flag']) && $_SESSION['flag'] == 1): ?>
            <a href="admin.php">Admin</a>
        <?php else: ?>
            <a href="#" class="admin-disabled">Admin</a>
        <?php endif; ?>
    </div>
    <div class="icons">
        <a href="cart.php" title="Cart">
            <img src="cart.jpg" alt="Cart">
        </a>
        <a href="logout.php" title="Logout">
            <img src="logout.jpg" alt="Logout">
        </a>
    </div>
</div>