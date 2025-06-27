<?php 
    session_start();
    $con = new mysqli("localhost", "root", "", "tastecraft");

    if ($con->connect_error) {
        die("Connection failed: " . $con->connect_error);
    }

    if (!isset($_SESSION['id'])) {
        header("Location: login.php");
        exit();
    }
    
    // בדיקה אם המשתמש הוא מנהל
    $isAdmin = isset($_SESSION['flag']) && $_SESSION['flag'] == 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>About - TasteCraft</title>
  <link rel="icon" type="image/png" href="about.jpg">
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #FEFAF7;
      margin: 0;
      padding: 0;
    }

    /* סרגל עליון */
    .navbar {
      background-color: white;
      color: black;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 30px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .navbar a {
      color: black;
      text-decoration: none;
      margin: 0 15px;
      font-weight: bold;
      font-size: 16px; 
    }

    .navbar a:hover {
      color: #f4b400;
      font-size: 18px;
    }

    .navbar .icons img {
      width: 30px;
      margin-left: 15px;
      cursor: pointer;
      filter: grayscale(100%);
      border-radius: 50%;
    }

    .navbar .icons img:hover {
      background-color: #BAB3AE;
    }
    
    /* סגנון כפתור אדמין מושבת */
    .admin-disabled {
      color: #999 !important;
      pointer-events: none;
      cursor: default;
    }

    .main-container {
      display: flex;
      min-height: calc(100vh - 60px);
    }

    /* צד שמאל - תמונה */
    .left-image {
      flex: 1;
      background-color: #ddd;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .left-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    /* צד ימין - תוכן */
    .right-content {
      flex: 1;
      background-color: #e0e0e0;
      padding: 40px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .head {
      font-size: 42px;
      margin-bottom: 20px;
      text-align: center;
      color: #333;
    }

    .main {
      font-size: 22px;
      text-align: center;
      margin-bottom: 30px;
      color: #444;
      line-height: 1.6;
    }

    .about-content h2 {
      font-size: 28px;
      margin-bottom: 10px;
      color: #222;
      text-align: center;
    }

    .about-content p {
      font-size: 20px;
      color: #333;
      margin-bottom: 15px;
      text-align: center;
    }

    .footer {
      text-align: center;
      padding: 20px;
      background-color: #f1f1f1;
    }

    .footer .pfooter {
      margin: 5px 0;
    }
  </style>
</head>
<body>

  <!-- סרגל עליון -->
  <div class="navbar">
    <div class="left-links">
      <a href="home2.php">Home</a>
      <a href="workshop.php">Book Workshop</a>
      <a href="profile.php">Profile</a>
      <!-- כפתור Admin - מוצג באופן שונה בהתאם להרשאות -->
      <?php if($isAdmin): ?>
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

  <!-- תוכן ראשי -->
  <div class="main-container">
    <!-- צד שמאל - תמונה -->
    <div class="left-image">
      <img src="about.jpg" alt="About TasteCraft">
    </div>

    <!-- צד ימין - תוכן -->
    <div class="right-content">
      <h1 class="head">About TasteCraft</h1>
      <div class="main">
        TasteCraft is a place where culinary dreams come true. We offer a variety of cooking workshops for all skill levels, from beginners to advanced chefs. Our goal is to make cooking fun, accessible, and inspiring for everyone!
      </div>

      <div class="about-content">
        <h2>Our Story</h2>
        <p>TasteCraft was founded with the mission of bringing people together through the art of cooking.</p>
        <p>Whether you're learning to cook for the first time or you're looking to refine your skills, TasteCraft is here to support your culinary journey.</p>
      </div>
    </div>
  </div>

  <!-- תחתית -->
  <div class="footer">
    <p class="pfooter">RAFEEK KABLAN ©</p>
    <p class="pfooter">AMAL TAFISH ©</p>
  </div>

</body>
</html>