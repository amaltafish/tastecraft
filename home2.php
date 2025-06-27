<?php
session_start();

// בדיקה אם המשתמש מחובר
if(!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// קריאת שם המשתמש מהסשן
$username = $_SESSION['Fname'];

// בדיקה אם המשתמש הוא מנהל
$isAdmin = isset($_SESSION['flag']) && $_SESSION['flag'] == 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Home - TasteCraft</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #FEFAF7;
      margin: 0;
      padding: 0;
    }

    /* סרגל עליון - שחור עם שינוי בגודל הטקסט */
    .navbar {
      background-color: transparent;
      color: black;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 30px;
      box-shadow: none;
    }

    .navbar a {
      color: black;
      text-decoration: none;
      margin: 0 15px;
      font-weight: bold;
      transition: 0.3s;
      font-size: 16px; /* גודל טקסט רגיל */
    }

    .navbar a:hover {
      color: black;
      font-size: 18px; /* גודל טקסט גדל בלחיצה */
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

    .left-image {
      flex: 1;
      background-image: url('home3.jpg');
      background-size: cover;
      background-position: center;
      border-top-right-radius: 20px;
      border-bottom-right-radius: 20px;
    }

    .right-content {
      flex: 1;
      background-color: rgba(0, 0, 0, 0.1); /* אפור שקוף */
      padding: 50px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .head {
      font-size: 36px;
      font-family: 'Cooper', sans-serif;
      margin-bottom: 20px;
      text-align: center;
    }

    .main {
      font-size: 20px;
      text-align: center;
      margin-bottom: 30px;
    }

    .interesting-content {
      background-color: #fff;
      padding: 20px;
      margin-top: 30px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .interesting-content h2 {
      font-size: 28px;
      margin-bottom: 10px;
    }

    .interesting-content p {
      font-size: 18px;
    }

    .social-icons {
      text-align: center;
      margin-top: 40px;
    }

    .social-icons img {
      width: 40px;
      margin: 0 10px;
      border-radius: 10px;
      transition: transform 0.2s;
    }

    .social-icons img:hover {
      transform: scale(1.1);
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
      <a href="about.php">About</a>
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
    <div class="left-image"></div>

    <div class="right-content">
      <h1 class="head">Welcome to TasteCraft, <?php echo $username; ?>!</h1>
      <div class="main">
        Join our cooking workshops and learn from the best chefs! Whether you're a beginner or a pro, we have something for everyone.
      </div>

      <!-- תוכן מעניין על האתר -->
      <div class="interesting-content">
        <h2>Why TasteCraft?</h2>
        <p>At TasteCraft, we offer a wide range of cooking workshops tailored to your needs and preferences. Whether you're interested in healthy cooking, vegan recipes, or mastering gourmet dishes, we have something special for you. All our workshops are hosted by professional chefs who are passionate about food and sharing their knowledge with others.</p>
        <p>Sign up today and discover the joy of cooking with TasteCraft. Explore various cuisines and techniques that will help you expand your culinary skills and impress your friends and family.</p>
      </div>

      <!-- אייקונים חברתיים -->
      <div class="social-icons">
        <a href="https://www.instagram.com" target="_blank"><img src="InstagramLogo.jpg" alt="Instagram"></a>
        <a href="https://www.facebook.com" target="_blank"><img src="FacebookLogo2.jpg" alt="Facebook"></a>
        <a href="https://www.twitter.com" target="_blank"><img src="TwitterLogo.jpg" alt="Twitter"></a>
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